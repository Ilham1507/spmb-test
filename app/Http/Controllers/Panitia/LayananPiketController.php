<?php

namespace App\Http\Controllers\Panitia;

use App\Http\Controllers\Controller;
use App\Models\KunjunganPendaftar;
use App\Models\ReferensiSekolah;
use App\Models\Jurusan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Support\FullNameNormalizer;
use App\Support\Pagination;
use App\Http\Controllers\Peserta\SekolahAsalController;

class LayananPiketController extends Controller
{
    public function index(Request $request)
    {
        $visits = KunjunganPendaftar::with(['penerima.role', 'pendaftar'])
            ->when($request->filled('search'), fn ($query) => $query->where(fn ($inner) => $inner
                ->where('full_name', 'like', '%'.$request->search.'%')
                ->orWhere('visitor_phone', 'like', '%'.$request->search.'%')
                ->orWhere('origin_school', 'like', '%'.$request->search.'%')))
            ->latest('visited_at')->paginate(Pagination::perPage())->withQueryString();
        $jurusans = Jurusan::where('status', 'aktif')->orderBy('name')->get();
        $visitsToday = KunjunganPendaftar::whereDate('visited_at', today())->count();
        $unregisteredCount = KunjunganPendaftar::whereNull('applicant_id')->count();

        $schools = ReferensiSekolah::query()
            ->where(fn ($query) => $query->whereNull('status')->orWhere('status', '!=', 'nonaktif'))
            ->orderBy('nama')->get(['id', 'nama', 'npsn', 'kecamatan', 'kabupaten_kota']);

        return view('panitia.kunjungan.index', compact('visits', 'jurusans', 'schools', 'visitsToday', 'unregisteredCount'));
    }

    public function searchSchool(Request $request)
    {
        $request->merge(['junior_high_only' => true]);

        return app(SekolahAsalController::class)->search($request);
    }

    public function store(Request $request)
    {
        $validated = $this->visitData($request);
        if (KunjunganPendaftar::where('visitor_phone', $validated['visitor_phone'])
            ->whereDate('visited_at', today())->exists()) {
            return back()->withInput()->withErrors(['visitor_phone' => 'Kunjungan dengan nomor WhatsApp ini sudah tercatat hari ini. Periksa daftar kunjungan untuk mengedit data yang ada.']);
        }

        KunjunganPendaftar::create($validated + [
            'applicant_id' => null,
            'visited_at' => now(),
            'received_by' => Auth::id(),
        ]);

        return back()->with('success', 'Data calon siswa berhasil dicatat. Guru penerima: '.Auth::user()->name.'. Siswa dapat melakukan registrasi biasa dari perangkatnya.');
    }

    public function update(Request $request, KunjunganPendaftar $kunjungan)
    {
        $kunjungan->update($this->visitData($request));

        return back()->with('success', 'Data kunjungan '.$kunjungan->full_name.' berhasil diperbarui.');
    }

    public function destroy(KunjunganPendaftar $kunjungan)
    {
        if ($kunjungan->applicant_id) {
            return back()->with('warning', 'Kunjungan yang sudah terhubung ke siswa tidak dapat dihapus agar data pendaftaran dan pembayaran tetap aman.');
        }

        $kunjungan->delete();

        return back()->with('success', 'Data kunjungan berhasil dihapus.');
    }

    private function visitData(Request $request): array
    {
        $validated = $request->validate([
            'visit_purpose' => ['required', 'in:information,plan_to_register,direct_registration'],
            'full_name' => ['required', 'string', 'max:150'],
            'visitor_phone' => ['required', 'string', 'regex:/^08[0-9]{8,13}$/'],
            'parent_name' => ['nullable', 'string', 'max:150', 'required_without:parent_phone'],
            'parent_phone' => ['nullable', 'string', 'regex:/^08[0-9]{8,13}$/', 'required_without:parent_name'],
            'referensi_sekolah_id' => ['required', 'exists:referensi_sekolah,id'],
            'interested_major_id' => [
                'required',
                \Illuminate\Validation\Rule::exists('jurusan', 'id')->where('status', 'aktif'),
            ],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $reference = ReferensiSekolah::findOrFail($validated['referensi_sekolah_id']);

        $validated['school_reference_id'] = $reference?->id;
        $validated['visitor_phone'] = $this->normalizePhone($validated['visitor_phone']);
        $validated['parent_phone'] = filled($validated['parent_phone'] ?? null)
            ? $this->normalizePhone($validated['parent_phone'])
            : null;
        $validated['normalized_full_name'] = FullNameNormalizer::normalize($validated['full_name']);
        $validated['origin_school'] = $reference->nama;
        $validated['origin_school_npsn'] = $reference->npsn;
        $major = Jurusan::where('status', 'aktif')->findOrFail($validated['interested_major_id']);
        $validated['major_interest'] = $major->name;
        unset($validated['referensi_sekolah_id']);

        return $validated;
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        return str_starts_with($digits, '62') ? '0'.substr($digits, 2) : $digits;
    }
}
