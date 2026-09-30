<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Models\Pendaftar;
use App\Models\JenisDokumen;
use App\Models\DokumenPendaftar;
use App\Models\JadwalSpmb;
use App\Models\TesMasuk;
use App\Models\PesertaTes;
use App\Support\PendaftarSetup;
use App\Support\RegistrationFee;
use App\Support\RegistrationNumber;
use App\Support\KunjunganMatcher;
use App\Support\ReRegistrationFee;
use App\Support\FormFieldCatalog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Throwable;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $pendaftar = PendaftarSetup::getOrCreateFor($user);
        $pendaftar?->loadMissing([
            'biodata',
            'alamat',
            'dataAyah',
            'dataIbu',
            'dataWali',
            'sekolahAsal',
            'kontak',
        ]);

        $registrationFeeBill = RegistrationFee::ensureBill($pendaftar);
        $registrationFeePaid = RegistrationFee::isPaid($registrationFeeBill);
        $registrationFeePending = RegistrationFee::hasPendingVerification($registrationFeeBill);
        try {
            KunjunganMatcher::linkFor($pendaftar);
        } catch (Throwable $exception) {
            report($exception);
        }
        $forceTutorial = (bool) Cache::pull('participant-onboarding-force-'.$user->id, false);
        $registrationFeeOptions = ReRegistrationFee::optionsFor($pendaftar);

        if (!$pendaftar->registration_number && in_array($pendaftar->registration_status, ['submitted', 'verified', 'accepted', 're_registered'], true)) {
            RegistrationNumber::ensure($pendaftar);
        }

        // Check required documents
        $requiredDocs = JenisDokumen::where('is_required', true)->pluck('id');
        $documentFieldMap = [
            'foto_3x4' => fn ($name) => str_contains($name, 'foto') && str_contains($name, '3x4'),
            'skl_skhu_ijazah' => fn ($name) => str_contains($name, 'ijazah') || str_contains($name, 'skl'),
            'akta_kelahiran' => fn ($name) => str_contains($name, 'akta'),
            'kartu_keluarga' => fn ($name) => str_contains($name, 'keluarga') || str_contains($name, 'kk'),
        ];
        $activeDocumentIds = JenisDokumen::query()->get()->filter(function ($document) use ($documentFieldMap) {
            $name = strtolower($document->name);
            foreach ($documentFieldMap as $key => $matches) {
                if ($matches($name)) {
                    return FormFieldCatalog::isEnabled($key);
                }
            }
            return false;
        })->pluck('id');
        $requiredDocs = $requiredDocs->intersect($activeDocumentIds)->values();
        $uploadedWajib = $pendaftar
            ? DokumenPendaftar::where('applicant_id', $pendaftar->id)
                ->whereIn('document_type_id', $requiredDocs)
                ->count()
            : 0;
        $allDocsComplete = $requiredDocs->isEmpty() || $uploadedWajib >= $requiredDocs->count();

        $allFilled = static function ($model, array $fields): bool {
            return $model !== null && collect($fields)->every(
                fn (string $field) => filled($model->{$field})
            );
        };

        $requiredStatuses = FormFieldCatalog::requiredStatuses($pendaftar);
        $enabledFields = FormFieldCatalog::enabled();
        $fieldGroups = FormFieldCatalog::groups();
        $sectionComplete = static function (string $group) use ($pendaftar, $enabledFields, $fieldGroups): bool {
            $fields = collect($fieldGroups[$group] ?? [])
                ->keys()
                ->filter(fn ($key) => in_array($key, $enabledFields, true));

            return $fields->isEmpty() || $fields->every(
                fn ($key) => FormFieldCatalog::isCompleteValue(FormFieldCatalog::valueFor($pendaftar, $key))
            );
        };

        // Progress peserta harus mengikuti semua field yang diaktifkan admin,
        // bukan hanya field yang ditandai wajib. Dengan begitu data kosong tidak
        // bisa terlihat sebagai 100% lengkap.
        $sections = [
            'biodata'  => $sectionComplete('Biodata'),
            'alamat'   => $sectionComplete('Alamat'),
            'ayah'     => $sectionComplete('Ayah'),
            'ibu'      => $sectionComplete('Ibu'),
            'wali'     => $sectionComplete('Wali'),
            'sekolah'  => $sectionComplete('Sekolah Asal'),
            'jurusan'  => $sectionComplete('Pilihan Jurusan') && filled($pendaftar?->admission_path_id),
            'kontak'   => $sectionComplete('Kontak'),
            'dokumen'  => $allDocsComplete,
        ];

        // The dashboard action must resume the registration flow safely.  Do
        // not use the last page the participant happened to open: that can
        // skip a mandatory stage and immediately trigger the step-order
        // middleware.  Resume from the first unfinished stage instead.
        $stepOrder = [
            'biodata' => ['label' => 'Biodata diri', 'route' => 'peserta.biodata'],
            'alamat' => ['label' => 'Alamat domisili', 'route' => 'peserta.alamat'],
            'ayah' => ['label' => 'Data ayah', 'route' => 'peserta.ayah'],
            'ibu' => ['label' => 'Data ibu', 'route' => 'peserta.ibu'],
            'sekolah' => ['label' => 'Sekolah asal', 'route' => 'peserta.sekolah'],
            'jurusan' => ['label' => 'Pilihan jurusan', 'route' => 'peserta.jurusan'],
            'kontak' => ['label' => 'Data kontak', 'route' => 'peserta.kontak'],
            'dokumen' => ['label' => 'Dokumen', 'route' => 'peserta.dokumen'],
        ];
        $nextStep = ['route' => 'peserta.review', 'label' => 'Review & kirim pendaftaran'];
        foreach ($stepOrder as $key => $step) {
            if (! ($sections[$key] ?? false)) {
                $nextStep = ['route' => $step['route'], 'label' => 'Isi '.$step['label']];
                break;
            }
        }
        if (! $registrationFeePaid) {
            $nextStep = [
                'route' => 'peserta.pembayaran',
                'label' => $registrationFeePending ? 'Lihat status pembayaran' : 'Bayar formulir',
            ];
        }
        if ($pendaftar->registration_status === 'submitted') {
            $nextStep = [
                'route' => $pendaftar->verification_notes ? 'peserta.biodata' : 'peserta.formulir',
                'label' => $pendaftar->verification_notes ? 'Perbaiki formulir' : 'Lihat formulir pendaftaran',
            ];
        } elseif ($pendaftar->registration_status === 'verified') {
            $nextStep = ['route' => 'peserta.hasil-tes.index', 'label' => 'Lihat jadwal Tes SPMB'];
        } elseif ($pendaftar->registration_status === 'accepted') {
            $nextStep = ['route' => 'peserta.pembayaran', 'label' => 'Lanjutkan daftar ulang'];
        } elseif ($pendaftar->registration_status === 're_registered') {
            $nextStep = ['route' => 'peserta.formulir', 'label' => 'Lihat formulir pendaftaran'];
        }

        // Hitung progress dari 7 langkah wajib (wali opsional)
        $mandatory = collect($sections)->except('wali');
        $total     = $mandatory->count();
        $completed = $mandatory->filter()->count();
        $percent   = $total ? (int) round(($completed / $total) * 100) : 0;
        $jadwalSpmb = JadwalSpmb::where('tahun_ajaran_id', $pendaftar->academic_year_id)
            ->orderBy('tanggal_mulai')
            ->limit(6)
            ->get();
        $pendaftar->loadMissing('preferredTestSchedule');
        $jadwalTes = TesMasuk::orderBy('test_date')->limit(4)->get();
        $hasilTes = in_array($pendaftar->registration_status, ['accepted', 're_registered'], true)
            ? PesertaTes::with('tes')
                ->where('applicant_id', $pendaftar->id)
                ->where('attendance', true)
                ->orderBy('test_id')
                ->get()
            : collect();

        return view('peserta.dashboard.index', compact(
            'sections',
            'percent',
            'pendaftar',
            'registrationFeeBill',
            'registrationFeePaid',
            'registrationFeePending',
            'jadwalSpmb',
            'jadwalTes',
            'hasilTes'
            ,'forceTutorial'

            ,'requiredStatuses'
            ,'nextStep'
        ));
    }
}
