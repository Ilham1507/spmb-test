<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JadwalSpmb;
use App\Models\PengaturanSpmb;
use App\Models\SystemSetting;
use App\Models\TahunAjaran;
use App\Support\FormFieldCatalog;
use App\Support\PromotionEvent;
use App\Support\SpmbConfiguration;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PengaturanController extends Controller
{
    public function tahunAjaran(): View
    {
        return view('admin.tahun-ajaran.index', [
            'tahunAjarans' => TahunAjaran::query()->latest('start_date')->get(),
        ]);
    }

    public function storeTahunAjaran(Request $request): RedirectResponse
    {
        $data = $this->validateTahunAjaran($request);
        if (TahunAjaran::where('name', $data['name'])->exists()) return back()->withErrors(['name' => 'Tahun ajaran tersebut sudah tersedia.']);
        $data['is_active'] = $request->boolean('is_active');
        DB::transaction(function () use ($data) {
            if ($data['is_active']) TahunAjaran::query()->lockForUpdate()->update(['is_active' => false]);
            $tahun = TahunAjaran::create($data);
            if ($data['is_active']) PengaturanSpmb::query()->where('status', 'aktif')->update(['tahun_ajaran_id' => $tahun->id]);
        });
        return back()->with('success', 'Tahun ajaran berhasil ditambahkan.');
    }

    public function updateTahunAjaran(Request $request, TahunAjaran $tahunAjaran): RedirectResponse
    {
        $data = $this->validateTahunAjaran($request);
        if (TahunAjaran::where('name', $data['name'])->whereKeyNot($tahunAjaran->id)->exists()) return back()->withErrors(['name' => 'Tahun ajaran tersebut sudah tersedia.']);
        $data['is_active'] = $request->boolean('is_active');
        DB::transaction(function () use ($data, $tahunAjaran) {
            if ($data['is_active']) TahunAjaran::query()->whereKeyNot($tahunAjaran->id)->lockForUpdate()->update(['is_active' => false]);
            $tahunAjaran->update($data);
            if ($data['is_active']) PengaturanSpmb::query()->where('status', 'aktif')->update(['tahun_ajaran_id' => $tahunAjaran->id]);
        });
        return back()->with('success', 'Tahun ajaran berhasil diperbarui.');
    }

    private function validateTahunAjaran(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:20',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ], [
            'name.required' => 'Nama tahun ajaran wajib diisi.',
            'start_date.required' => 'Tanggal mulai wajib diisi.',
            'end_date.required' => 'Tanggal selesai wajib diisi.',
            'end_date.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
        ]);
    }

    public function destroyTahunAjaran(TahunAjaran $tahunAjaran): RedirectResponse
    {
        if ($tahunAjaran->is_active) return back()->with('warning', 'Tahun ajaran aktif tidak dapat dihapus.');
        if (PengaturanSpmb::where('tahun_ajaran_id', $tahunAjaran->id)->exists() || JadwalSpmb::where('tahun_ajaran_id', $tahunAjaran->id)->exists()) {
            return back()->with('warning', 'Tahun ajaran sudah memiliki konfigurasi atau jadwal. Nonaktifkan saja agar riwayat tetap tersimpan.');
        }
        $tahunAjaran->delete();
        return back()->with('success', 'Tahun ajaran berhasil dihapus.');
    }

    public function informasiTes(): View
    {
        $defaults = [
            ['title' => 'HP & internet', 'body' => 'Bawa HP yang cukup baterai dan pastikan internet dapat digunakan.'],
            ['title' => 'Berpakaian rapi', 'body' => 'Gunakan pakaian yang rapi dan sopan saat hadir di sekolah.'],
            ['title' => 'Bersama orang tua/wali', 'body' => 'Datang bersama orang tua atau wali untuk mengikuti proses tes.'],
            ['title' => 'Berkas pendukung', 'body' => 'Bawa Kartu Keluarga, Akta Kelahiran, dan sertifikat prestasi jika ada.'],
        ];
        $saved = json_decode(SystemSetting::values()['test_preparation_items'] ?? '', true);
        $testPreparation = is_array($saved) && $saved ? $saved : $defaults;

        return view('admin.informasi-tes.index', [
            'jadwalTes' => JadwalSpmb::with('tahunAjaran')->latest('tanggal_mulai')->get(),
            'tahunAjarans' => TahunAjaran::latest('start_date')->get(),
            'testPreparation' => $testPreparation,
        ]);
    }

    public function saveTestPreparation(Request $request): RedirectResponse
    {
        if ($request->has('items')) {
            $data = $request->validate([
                'items' => 'required|array|min:1|max:12',
                'items.*.title' => 'required|string|max:150',
                'items.*.body' => 'required|string|max:600',
                'items.*.icon' => ['nullable', \Illuminate\Validation\Rule::in(array_keys(\App\Support\TestPreparationIcons::OPTIONS))],
            ], ['items.*.title.required' => 'Isi judul pada setiap persiapan.', 'items.*.body.required' => 'Isi penjelasan pada setiap persiapan.']);
            $items = collect($data['items'])->values()->map(fn ($item, $index) => ['title' => trim($item['title']), 'body' => trim($item['body']), 'icon' => \App\Support\TestPreparationIcons::resolve($item['icon'] ?? null, $index)])->all();
            SystemSetting::putMany(['test_preparation_items' => json_encode($items, JSON_UNESCAPED_UNICODE)], 'test');
            return back()->with('success', 'Persiapan Tes SPMB untuk siswa berhasil diperbarui.');
        }
        $data = $request->validate(['items_text' => 'required|string|max:3000']);
        $items = collect(preg_split('/\r\n|\r|\n/', $data['items_text']))
            ->map(fn ($line) => array_map('trim', explode('|', $line, 2)))
            ->filter(fn ($item) => filled($item[0] ?? null) && filled($item[1] ?? null))
            ->map(fn ($item) => ['title' => $item[0], 'body' => $item[1]])
            ->values()->all();
        if (!$items) return back()->withErrors(['items_text' => 'Isi minimal satu persiapan dengan format Judul|Penjelasan.']);
        SystemSetting::putMany(['test_preparation_items' => json_encode($items, JSON_UNESCAPED_UNICODE)], 'test');
        return back()->with('success', 'Persiapan Tes SPMB untuk siswa berhasil diperbarui.');
    }

    public function storeInformasiTes(Request $request): RedirectResponse
    {
        JadwalSpmb::create($this->validateJadwal($request));
        return back()->with('success', 'Informasi tes berhasil ditambahkan.');
    }

    public function updateInformasiTes(Request $request, JadwalSpmb $jadwalSpmb, \App\Services\TestScheduleService $schedules): RedirectResponse
    {
        $schedules->update($jadwalSpmb, $this->validateJadwal($request));
        return back()->with('success', 'Jadwal diperbarui. Notifikasi WhatsApp dikirim kepada siswa yang memilih jadwal tersebut.');
    }

    public function moveInformasiTes(Request $request, JadwalSpmb $jadwalSpmb, \App\Services\TestScheduleService $schedules): RedirectResponse
    {
        $data = $request->validate(['replacement_schedule_id' => 'required|integer|exists:jadwal_spmb,id']);
        $count = $schedules->move($jadwalSpmb, JadwalSpmb::findOrFail($data['replacement_schedule_id']));
        return back()->with('success', "{$count} siswa dipindahkan ke jadwal pengganti. Jadwal lama ditutup untuk pilihan baru dan notifikasi WhatsApp dikirim.");
    }

    public function destroyInformasiTes(JadwalSpmb $jadwalSpmb): RedirectResponse
    {
        if (\App\Models\Pendaftar::where('preferred_test_schedule_id', $jadwalSpmb->id)->exists()) {
            return back()->with('warning', 'Jadwal masih dipilih siswa. Ubah tanggal atau pindahkan siswa ke jadwal pengganti terlebih dahulu.');
        }
        $jadwalSpmb->delete();
        return back()->with('success', 'Informasi tes berhasil dihapus.');
    }

    private function validateJadwal(Request $request): array
    {
        return $request->validate(['tahun_ajaran_id' => 'required|exists:tahun_ajaran,id', 'kegiatan' => 'required|string|max:150', 'tanggal_mulai' => 'required|date', 'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai', 'keterangan' => 'nullable|string|max:1000', 'available_for_student_selection' => 'nullable|boolean']) + ['available_for_student_selection' => $request->boolean('available_for_student_selection')];
    }

    public function konfigurasi(): View
    {
        return view('admin.konfigurasi.index', ['pengaturan' => SpmbConfiguration::forAcademicYear() ?? PengaturanSpmb::latest('id')->first(), 'tahunAjarans' => TahunAjaran::latest('start_date')->get()]);
    }

    public function saveKonfigurasi(Request $request): RedirectResponse
    {
        $data = $request->validate(['tahun_ajaran_id' => 'required|exists:tahun_ajaran,id', 'tanggal_buka' => 'required|date', 'tanggal_tutup' => 'required|date|after_or_equal:tanggal_buka', 'status' => 'required|in:draft,aktif,ditutup']);

        DB::transaction(function () use ($data) {
            // Tahun yang dipilih di halaman ini adalah sumber tahun aktif untuk seluruh portal.
            TahunAjaran::query()->lockForUpdate()->update(['is_active' => false]);
            TahunAjaran::query()->whereKey($data['tahun_ajaran_id'])->update(['is_active' => true]);

            $configuration = PengaturanSpmb::query()->firstOrNew(['tahun_ajaran_id' => $data['tahun_ajaran_id']]);
            $configuration->fill($data);
            $configuration->nama_spmb ??= SystemSetting::publicValues()['portal_name'] ?? 'SPMB';
            $configuration->biaya_pendaftaran ??= 0;
            $configuration->maksimal_pilihan_jurusan ??= 2;
            $configuration->save();

            $yearName = TahunAjaran::query()->find($data['tahun_ajaran_id'])?->name;
            SystemSetting::putMany(['academic_year' => $yearName ?? ''], 'spmb');
        });

        return back()->with('success', 'Tahun ajaran dan periode pendaftaran berhasil diperbarui.');
    }

    public function biayaPendaftaran(): View
    {
        return view('admin.keuangan.biaya-pendaftaran', [
            'pengaturan' => SpmbConfiguration::forAcademicYear() ?? PengaturanSpmb::latest('id')->first(),
            'tahunAjaran' => TahunAjaran::query()->where('is_active', true)->first(),
            'routePrefix' => request()->routeIs('admin.*') ? 'admin.' : 'bendahara.',
        ]);
    }

    public function saveBiayaPendaftaran(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'biaya_pendaftaran' => 'required|numeric|min:0',
        ]);
        $tahunAjaran = TahunAjaran::query()->where('is_active', true)->firstOrFail();
        $configuration = PengaturanSpmb::query()->firstOrNew(['tahun_ajaran_id' => $tahunAjaran->id]);
        $configuration->biaya_pendaftaran = $data['biaya_pendaftaran'];
        $configuration->nama_spmb ??= SystemSetting::publicValues()['portal_name'] ?? 'SPMB';
        $configuration->maksimal_pilihan_jurusan ??= 2;
        $configuration->tanggal_buka ??= now()->toDateString();
        $configuration->tanggal_tutup ??= now()->toDateString();
        $configuration->status ??= 'draft';
        $configuration->save();

        return back()->with('success', 'Biaya pendaftaran berhasil diperbarui untuk tahun ajaran aktif.');
    }

    public function identitas(): View
    {
        return view('admin.identitas.index', ['settings' => SystemSetting::publicValues()]);
    }

    public function formulir(): View
    {
        return view('admin.formulir.index', [
            'groups' => FormFieldCatalog::groups(),
            'enabledFields' => FormFieldCatalog::enabled(),
            'requiredFields' => FormFieldCatalog::required(),
            'customFields' => FormFieldCatalog::custom(),
        ]);
    }

    public function saveFormulir(Request $request): RedirectResponse
    {
        $allowed = FormFieldCatalog::keys();
        $fields = collect($request->input('fields', []))->filter(fn ($field) => in_array($field, $allowed, true))->values()->all();
        $required = collect($request->input('required_fields', []))
            ->filter(fn ($field) => in_array($field, $fields, true))
            ->values()->all();
        SystemSetting::putMany([
            'form_fields' => json_encode($fields, JSON_UNESCAPED_UNICODE),
            'form_required_fields' => json_encode($required, JSON_UNESCAPED_UNICODE),
        ], 'form');

        return back()->with('success', 'Pengaturan formulir berhasil disimpan.');
    }

    public function eventPromo(): View
    {
        $applicants = \App\Models\Pendaftar::studentApplicants()->with(['biodata', 'user'])
            ->orderBy('registration_number')
            ->get()
            ->map(fn ($applicant) => [
                'id' => $applicant->id,
                'label' => trim(($applicant->biodata?->full_name ?? $applicant->user?->name ?? 'Peserta').' · '.($applicant->registration_number ?? '-')),
            ]);

        $applicantNames = $applicants->pluck('label', 'id');
        $feeComponents = \App\Models\TagihanPendaftar::with('jenisTagihan')->get()
            ->filter(fn ($bill) => str_contains(strtolower((string) $bill->jenisTagihan?->name), 'daftar ulang'))
            ->flatMap(fn ($bill) => collect($bill->rincian_biaya ?? []))
            ->filter(fn ($item) => filled($item['name'] ?? null))
            ->groupBy(fn ($item) => (string) $item['name'])
            ->map(fn ($items, $name) => ['name' => $name, 'amount' => (float) ($items->first()['amount'] ?? 0)])
            ->sortBy('name')
            ->values();
        $events = collect(PromotionEvent::all())->map(function (array $event) use ($applicantNames) {
            $event['applicant_label'] = filled($event['applicant_id'] ?? null)
                ? ($applicantNames[(string) $event['applicant_id']] ?? 'Peserta tidak ditemukan')
                : null;
            return $event;
        })->all();

        return view('admin.event.index', compact('events', 'applicants', 'feeComponents'));
    }

    public function storeEventPromo(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:120', 'target' => 'required|in:formulir,daftar_ulang,semua',
            'discount_type' => 'required|in:percent,fixed',
            'discount_value' => 'required|numeric|min:0', 'starts_at' => 'required|date', 'ends_at' => 'required|date|after_or_equal:starts_at',
            'applicant_id' => 'nullable|integer|exists:pendaftar,id',
            'target_items' => 'nullable|array|required_if:target,daftar_ulang|min:1',
            'target_items.*' => 'string|max:120',
        ]);
        if ($data['discount_type'] === 'percent' && $data['discount_value'] > 100) return back()->withErrors(['discount_value' => 'Persentase maksimal 100%.']);
        $data['target_items'] = array_values($data['target_items'] ?? []);
        unset($data['target_item']);
        $events = PromotionEvent::all();
        $data['id'] = uniqid('event_', true); $data['is_active'] = true;
        $events[] = $data;
        SystemSetting::putMany(['promotion_events' => json_encode($events, JSON_UNESCAPED_UNICODE)], 'finance');
        return back()->with('success', 'Event dan promo berhasil ditambahkan.');
    }

    public function updateEventPromo(Request $request, string $id): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:120', 'target' => 'required|in:formulir,daftar_ulang,semua',
            'discount_type' => 'required|in:percent,fixed',
            'discount_value' => 'required|numeric|min:0', 'starts_at' => 'required|date', 'ends_at' => 'required|date|after_or_equal:starts_at',
            'applicant_id' => 'nullable|integer|exists:pendaftar,id',
            'target_items' => 'nullable|array|required_if:target,daftar_ulang|min:1',
            'target_items.*' => 'string|max:120',
        ]);
        $data['target_items'] = array_values($data['target_items'] ?? []);
        unset($data['target_item']);
        $events = collect(PromotionEvent::all())->map(function ($event) use ($id, $data, $request) {
            if (($event['id'] ?? '') === $id) $event = array_merge($event, $data, ['is_active' => true]);
            return $event;
        })->values()->all();
        SystemSetting::putMany(['promotion_events' => json_encode($events, JSON_UNESCAPED_UNICODE)], 'finance');
        return back()->with('success', 'Event dan promo berhasil diperbarui.');
    }

    public function destroyEventPromo(string $id): RedirectResponse
    {
        $events = collect(PromotionEvent::all())->reject(fn ($event) => ($event['id'] ?? '') === $id)->values()->all();
        SystemSetting::putMany(['promotion_events' => json_encode($events, JSON_UNESCAPED_UNICODE)], 'finance');
        return back()->with('success', 'Event dan promo berhasil dihapus.');
    }

    public function storeFormField(Request $request): RedirectResponse
    {
        $data = $request->validate(['label' => 'required|string|max:100', 'group' => 'required|string|max:60']);
        abort_unless(in_array($data['group'], array_keys(FormFieldCatalog::groups()), true), 422);
        $key = 'custom_'.\Illuminate\Support\Str::slug($data['label'], '_').'_'.\Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(5));
        $custom = FormFieldCatalog::custom();
        $custom[] = ['key' => $key, 'label' => $data['label'], 'group' => $data['group']];
        SystemSetting::putMany(['custom_form_fields' => json_encode($custom, JSON_UNESCAPED_UNICODE)], 'form');
        return back()->with('success', 'Field tambahan berhasil ditambahkan.');
    }

    public function updateFormField(Request $request, string $key): RedirectResponse
    {
        $data = $request->validate(['label' => 'required|string|max:100', 'group' => 'required|string|max:60']);
        $custom = collect(FormFieldCatalog::custom());
        if ($custom->contains('key', $key)) {
            $custom = $custom->map(function ($field) use ($key, $data) {
                if ($field['key'] === $key) { $field['label'] = $data['label']; $field['group'] = $data['group']; }
                return $field;
            })->values()->all();
            SystemSetting::putMany(['custom_form_fields' => json_encode($custom, JSON_UNESCAPED_UNICODE)], 'form');
        } else {
            $overrides = json_decode(SystemSetting::values()['form_field_overrides'] ?? '', true);
            $overrides = is_array($overrides) ? $overrides : [];
            $overrides[$key] = $data['label'];
            SystemSetting::putMany(['form_field_overrides' => json_encode($overrides, JSON_UNESCAPED_UNICODE)], 'form');
        }
        return back()->with('success', 'Field berhasil diperbarui.');
    }

    public function destroyFormField(string $key): RedirectResponse
    {
        $custom = collect(FormFieldCatalog::custom())->reject(fn ($field) => $field['key'] === $key)->values()->all();
        $deleted = json_decode(SystemSetting::values()['form_deleted_fields'] ?? '', true);
        $deleted = is_array($deleted) ? $deleted : [];
        if (!in_array($key, $deleted, true)) $deleted[] = $key;
        SystemSetting::putMany([
            'custom_form_fields' => json_encode($custom, JSON_UNESCAPED_UNICODE),
            'form_deleted_fields' => json_encode($deleted, JSON_UNESCAPED_UNICODE),
        ], 'form');
        return back()->with('success', 'Field berhasil dihapus.');
    }

    public function saveIdentitas(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'school_name' => 'required|string|max:150', 'school_short_name' => 'required|string|max:100',
            'portal_name' => 'required|string|max:80', 'brand_name' => 'required|string|max:50',
            'accreditation' => 'nullable|string|max:20', 'school_address' => 'required|string|max:500',
            'contact_phone' => ['required', 'regex:/^08[0-9]{8,13}$/'], 'operational_hours' => 'required|string|max:150',
            'tagline' => 'required|string|max:120', 'footer_description' => 'required|string|max:500',
            'school_logo' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            'letterhead' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:4096',
        ]);

        unset($data['school_logo'], $data['letterhead']);
        if ($request->hasFile('school_logo')) {
            $old = SystemSetting::publicValues()['school_logo'] ?? null;
            if ($old && str_starts_with($old, 'storage/')) Storage::disk('public')->delete(substr($old, 8));
            $data['school_logo'] = 'storage/'.$request->file('school_logo')->store('school', 'public');
        }
        if ($request->hasFile('letterhead')) {
            $oldLetterhead = SystemSetting::publicValues()['letterhead_path'] ?? null;
            if ($oldLetterhead && str_starts_with($oldLetterhead, 'storage/')) Storage::disk('public')->delete(substr($oldLetterhead, 8));
            $data['letterhead_path'] = 'storage/'.$request->file('letterhead')->store('school', 'public');
        }
        SystemSetting::putMany($data, 'identity');
        return back()->with('success', 'Identitas sekolah berhasil diperbarui di seluruh sistem.');
    }

    public function landing(): View
    {
        return view('admin.landing.index', ['settings' => SystemSetting::publicValues(), 'sections' => SystemSetting::landingSections()]);
    }

    public function saveLanding(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sections' => 'required|array|min:1|max:20',
            'sections.*.id' => ['required', 'string', 'max:80', 'regex:/^[a-z0-9\-]+$/'],
            'sections.*.type' => 'required|in:hero,highlights,jurusan,flow,contact,cta,custom',
            'sections.*.enabled' => 'nullable|boolean', 'sections.*.layout' => 'required|in:full,half',
            'sections.*.eyebrow' => 'nullable|string|max:60', 'sections.*.title' => 'required|string|max:160',
            'sections.*.body' => 'nullable|string|max:500', 'sections.*.items_text' => 'nullable|string|max:3000',
        ]);
        $sections = collect($validated['sections'])->map(function ($section) {
            $section['enabled'] = (bool)($section['enabled'] ?? false);
            $section['items'] = collect(preg_split('/\r\n|\r|\n/', $section['items_text'] ?? ''))->map(fn($line) => trim($line))->filter()->values()->all();
            unset($section['items_text']);
            return $section;
        })->values()->all();
        SystemSetting::putMany(['landing_sections' => json_encode($sections, JSON_UNESCAPED_UNICODE)], 'landing');
        return back()->with('success', 'Susunan landing page berhasil diterbitkan.');
    }
}
