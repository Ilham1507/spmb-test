<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Models\Pendaftar;
use App\Models\JenisDokumen;
use App\Models\DokumenPendaftar;
use App\Models\JadwalSpmb;
use App\Support\RegistrationNumber;
use App\Support\FormFieldCatalog;
use App\Services\WhatsappCloudApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class ReviewController extends Controller
{
    public function index()
    {
        $pendaftar = $this->getPendaftar();
        if (!$pendaftar) {
            return redirect()->route('peserta.biodata')
                             ->with('warning', 'Silakan lengkapi biodata terlebih dahulu.');
        }

        // Preload relationships
        $pendaftar->load(['biodata', 'alamat', 'dataAyah', 'dataIbu', 'dataWali', 'sekolahAsal', 'kontak', 'jurusan1', 'jurusan2', 'jalurPendaftaran', 'preferredTestSchedule']);

        $jenisDokumens = JenisDokumen::all();
        $uploadedDocs = DokumenPendaftar::where('applicant_id', $pendaftar->id)
            ->get()
            ->keyBy('document_type_id');

        $testSchedules = JadwalSpmb::query()
            ->where('tahun_ajaran_id', $pendaftar->academic_year_id)
            ->where('available_for_student_selection', true)
            ->where('tanggal_mulai', '>=', now()->startOfDay())
            ->orderBy('tanggal_mulai')
            ->get();

        return view('peserta.review.index', compact('pendaftar', 'jenisDokumens', 'uploadedDocs', 'testSchedules'));
    }

    public function submit(Request $request, WhatsappCloudApiService $whatsapp)
    {
        $pendaftar = $this->getPendaftar();
        if (!$pendaftar) {
            return redirect()->route('peserta.biodata')
                             ->with('warning', 'Silakan lengkapi biodata terlebih dahulu.');
        }

        // Validate only fields currently enabled and required by the administrator.
        $errors = collect(FormFieldCatalog::requiredStatuses($pendaftar))
            ->filter(fn ($complete) => ! $complete)
            ->keys()
            ->map(fn ($label) => "{$label} wajib diisi.")
            ->values()
            ->all();

        // Check required documents
        $requiredDocs = JenisDokumen::where('is_required', true)->get();
        foreach ($requiredDocs as $doc) {
            $uploaded = DokumenPendaftar::where('applicant_id', $pendaftar->id)
                ->where('document_type_id', $doc->id)
                ->exists();
            if (!$uploaded) {
                $errors[] = "Dokumen wajib '{$doc->name}' belum diunggah.";
            }
        }

        $selectedSchedule = JadwalSpmb::query()
            ->whereKey($request->integer('preferred_test_schedule_id'))
            ->where('tahun_ajaran_id', $pendaftar->academic_year_id)
            ->where('available_for_student_selection', true)
            ->where('tanggal_mulai', '>=', now()->startOfDay())
            ->first();
        if (! $selectedSchedule) {
            $errors[] = 'Pilih salah satu jadwal Tes SPMB yang tersedia.';
        }

        if (count($errors) > 0) {
            return redirect()->route('peserta.review')
                             ->withErrors($errors)
                             ->with('warning', 'Silakan lengkapi seluruh langkah wajib terlebih dahulu.');
        }

        // 2. Generate No Pendaftaran if not generated yet
        RegistrationNumber::ensure($pendaftar);

        $pendaftar->preferred_test_schedule_id = $selectedSchedule->id;
        $pendaftar->preferred_test_selected_at = now();

        // 3. Set status to submitted
        $isCorrection = in_array($pendaftar->correction_status, ['requested', 'resubmitted'], true)
            || filled($pendaftar->verification_notes);
        $pendaftar->registration_status = 'submitted';
        $pendaftar->correction_status = $isCorrection ? 'resubmitted' : null;
        $pendaftar->correction_submitted_at = $isCorrection ? now() : null;
        if (!$isCorrection) {
            $pendaftar->verification_notes = null;
        }
        $pendaftar->save();

        $name = $pendaftar->biodata?->full_name ?? Auth::user()->name;
        $pendaftar->loadMissing('kunjungan.penerima');
        $receivingVisit = $pendaftar->kunjunganPenerimaanUtama();
        $notificationTarget = $receivingVisit?->penerima?->phone
            ?: (string) config('services.panitia.whatsapp_number');

        $receiverName = $receivingVisit?->penerima?->name ?? 'Panitia SPMB';
        $scheduleDate = \Illuminate\Support\Carbon::parse($selectedSchedule->tanggal_mulai)->translatedFormat('l, d F Y · H:i') . ' WIB';
        $message = "Halo {$receiverName},

"
            ."Formulir pendaftaran baru perlu diperiksa.

"
            ."Nama calon siswa: {$name}
"
            ."No. pendaftaran: {$pendaftar->registration_number}
"
            ."Pilihan Tes SPMB: {$scheduleDate}
"
            ."Lokasi tes: Kampus E SMK Muhammadiyah 4 Cileungsi
"
            .($receivingVisit ? "Penerima kunjungan: {$receiverName}
" : '')
            ."
Silakan buka menu Pendaftar untuk memeriksa dan menyetujui formulir.";

        try {
            if (filled($notificationTarget)) {
                $whatsapp->send($notificationTarget, $message);
            }
        } catch (Throwable $exception) {
            Log::warning('Notifikasi persetujuan formulir ke petugas gagal dikirim melalui WhatsApp Business API.', [
                'applicant_id' => $pendaftar->id,
                'error' => $exception->getMessage(),
            ]);
        }

        return redirect()->route('peserta.dashboard')->with('success', $isCorrection
            ? 'Perbaikan formulir berhasil dikirim ulang. Kami akan memberi kabar setelah pemeriksaan selesai.'
            : 'Pendaftaran berhasil dikirim. Kami akan memberi kabar setelah pemeriksaan formulir selesai.');
    }

    private function getPendaftar(): ?Pendaftar
    {
        return Auth::user()->pendaftar;
    }
}

