<?php

namespace App\Http\Controllers\Peserta;

use App\Http\Controllers\Controller;
use App\Models\DataAyah;
use App\Models\Pendaftar;
use App\Models\Pekerjaan;
use App\Models\Pendidikan;
use App\Models\Penghasilan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Support\FormFieldCatalog;

class AyahController extends Controller
{
    public function index()
    {
        $pendaftar = $this->getPendaftar();
        if (!$pendaftar) {
            return redirect()->route('peserta.biodata')
                             ->with('warning', 'Silakan lengkapi biodata terlebih dahulu.');
        }

        $ayah = $pendaftar->dataAyah;
        $pekerjaans = Pekerjaan::orderBy('nama')->pluck('nama');
        $pendidikans = Pendidikan::orderBy('id')->pluck('nama');
        $penghasilans = Penghasilan::orderBy('id')->pluck('nama');

        return view('peserta.ayah.index', compact('ayah', 'pendaftar', 'pekerjaans', 'pendidikans', 'penghasilans'));
    }

    public function store(Request $request)
    {
        $required = fn (string $key, string $rules) => FormFieldCatalog::isRequired($key) ? "required|{$rules}" : "nullable|{$rules}";
        $request->validate([
            'nama'        => $required('nama_ayah', 'string|max:150'),
            'nik'         => 'nullable|string|max:20',
            'pekerjaan'   => ['nullable', Rule::in(Pekerjaan::pluck('nama')->all())],
            'pendidikan'  => ['nullable', Rule::in(Pendidikan::pluck('nama')->all())],
            'penghasilan' => ['nullable', Rule::in(Penghasilan::pluck('nama')->all())],
            'no_hp'       => 'nullable|string|max:20',
        ]);

        $pendaftar = $this->getPendaftar();

        DataAyah::updateOrCreate(
            ['applicant_id' => $pendaftar->id],
            [
                'name'       => $request->nama,
                'nik'        => $request->nik,
                'occupation' => $request->pekerjaan,
                'education'  => $request->pendidikan,
                'income'     => $request->penghasilan,
                'phone'      => $request->no_hp,
            ]
        );

        return $this->redirectAfterParticipantSave($request, 'peserta.ibu', 'Data ayah berhasil disimpan.', 'peserta.ibu');
    }

    private function getPendaftar(): ?Pendaftar
    {
        return Auth::user()->pendaftar;
    }
}
