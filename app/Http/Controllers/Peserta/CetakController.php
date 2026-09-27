<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Support\FormFieldCatalog;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class CetakController extends Controller
{
    public function index()
    {
        $pendaftar = Auth::user()->pendaftar;
        abort_unless($pendaftar, 404);

        $pendaftar->load([
            'biodata', 'alamat', 'dataAyah', 'dataIbu', 'dataWali', 'sekolahAsal',
            'jurusan1', 'jurusan2', 'jalurPendaftaran', 'kontak',
        ]);

        return view('peserta.cetak.index', [
            'pendaftar' => $pendaftar,
            'groups' => FormFieldCatalog::groups(),
            'enabledFields' => FormFieldCatalog::enabled(),
            'settings' => SystemSetting::publicValues(),
        ]);
    }

    public function pdf()
    {
        $view = $this->index();
        $data = $view->getData();
        $data['letterheadSrc'] = $this->letterheadSource($data['settings'] ?? []);
        return Pdf::loadHTML(view('peserta.cetak.index', $data)->render())
            ->setPaper('a4', 'portrait')
            ->download('formulir-pendaftaran.pdf');
    }

    public function preview()
    {
        $pendaftar = Auth::user()->pendaftar;
        abort_unless($pendaftar, 404);
        $pendaftar->load([
            'biodata', 'alamat', 'dataAyah', 'dataIbu', 'dataWali', 'sekolahAsal',
            'jurusan1', 'jurusan2', 'jalurPendaftaran', 'kontak',
        ]);

        return view('peserta.formulir.index', [
            'pendaftar' => $pendaftar,
            'groups' => FormFieldCatalog::groups(),
            'enabledFields' => FormFieldCatalog::enabled(),
        ]);
    }

    private function letterheadSource(array $settings): ?string
    {
        $path = $settings['letterhead_path'] ?? null;
        $file = $path ? public_path($path) : null;
        return $file && is_file($file) ? 'data:image/' . pathinfo($file, PATHINFO_EXTENSION) . ';base64,' . base64_encode(file_get_contents($file)) : null;
    }
}
