<?php

namespace App\Http\Middleware;

use App\Models\DokumenPendaftar;
use App\Models\JenisDokumen;
use App\Support\FormFieldCatalog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePesertaStepOrder
{
    public function handle(Request $request, Closure $next): Response
    {
        $routeName = $request->route()?->getName();
        $pendaftar = $request->user()?->pendaftar;

        $routes = [
            'biodata' => 'peserta.biodata',
            'alamat' => 'peserta.alamat',
            'ayah' => 'peserta.ayah',
            'ibu' => 'peserta.ibu',
            'wali' => 'peserta.wali',
            'sekolah' => 'peserta.sekolah',
            'jurusan' => 'peserta.jurusan',
            'kontak' => 'peserta.kontak',
            'dokumen' => 'peserta.dokumen',
            'review' => 'peserta.review',
            'submit' => 'peserta.submit',
        ];

        $stepForRoute = array_search($routeName, $routes, true);

        if ($stepForRoute === false || $stepForRoute === 'biodata') {
            return $next($request);
        }

        $requiredDocs = JenisDokumen::where('is_required', true)->pluck('id');
        $uploadedWajib = $pendaftar
            ? DokumenPendaftar::where('applicant_id', $pendaftar->id)
                ->whereIn('document_type_id', $requiredDocs)
                ->count()
            : 0;

        $groupComplete = function (string $group) use ($pendaftar): bool {
            $requiredFields = collect(FormFieldCatalog::groups()[$group] ?? [])
                ->keys()
                ->filter(fn ($key) => FormFieldCatalog::isEnabled($key) && FormFieldCatalog::isRequired($key));

            return $requiredFields->isEmpty() || $requiredFields->every(
                fn ($key) => FormFieldCatalog::isCompleteValue(FormFieldCatalog::valueFor($pendaftar, $key))
            );
        };

        $completed = [
            'biodata' => $groupComplete('Biodata'),
            'alamat' => $groupComplete('Alamat'),
            'ayah' => $groupComplete('Ayah'),
            'ibu' => $groupComplete('Ibu'),
            'sekolah' => $groupComplete('Sekolah Asal'),
            'jurusan' => $groupComplete('Pilihan Jurusan'),
            'kontak' => $groupComplete('Kontak'),
            'dokumen' => $requiredDocs->isEmpty() || $uploadedWajib >= $requiredDocs->count(),
        ];

        $labels = [
            'biodata' => 'Biodata Diri',
            'alamat' => 'Alamat Domisili',
            'ayah' => 'Data Ayah',
            'ibu' => 'Data Ibu',
            'sekolah' => 'Sekolah Asal',
            'jurusan' => 'Pilihan Jurusan',
            'kontak' => 'Data Kontak',
            'dokumen' => 'Upload Dokumen',
        ];

        $requirements = [
            'alamat' => ['biodata'],
            'ayah' => ['biodata', 'alamat'],
            'ibu' => ['biodata', 'alamat', 'ayah'],
            'wali' => ['biodata', 'alamat', 'ayah', 'ibu'],
            'sekolah' => ['biodata', 'alamat', 'ayah', 'ibu'],
            'jurusan' => ['biodata', 'alamat', 'ayah', 'ibu', 'sekolah'],
            'kontak' => ['biodata', 'alamat', 'ayah', 'ibu', 'sekolah', 'jurusan'],
            'dokumen' => ['biodata', 'alamat', 'ayah', 'ibu', 'sekolah', 'jurusan', 'kontak'],
            'review' => ['biodata', 'alamat', 'ayah', 'ibu', 'sekolah', 'jurusan', 'dokumen'],
            'submit' => ['biodata', 'alamat', 'ayah', 'ibu', 'sekolah', 'jurusan', 'dokumen'],
        ];

        foreach ($requirements[$stepForRoute] ?? [] as $requiredStep) {
            if (!($completed[$requiredStep] ?? false)) {
                return redirect()
                    ->route($routes[$requiredStep])
                    ->with('warning', 'Lengkapi ' . $labels[$requiredStep] . ' dulu sebelum lanjut ke tahap berikutnya.');
            }
        }

        return $next($request);
    }
}
