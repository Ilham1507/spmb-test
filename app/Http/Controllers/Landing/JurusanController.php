<?php

namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;
use App\Models\GelombangPendaftaran;
use App\Models\Jurusan;

class JurusanController extends Controller
{
    public function index()
    {
        return view('landing.jurusan', [
            'jurusans' => Jurusan::where('status', 'aktif')->orderBy('id')->get(),
        ]);
    }

    public function show(Jurusan $jurusan)
    {
        abort_unless($jurusan->status === 'aktif', 404);

        $gelombangAktif = GelombangPendaftaran::with('jurusanBiaya')
            ->where('status', 'aktif')
            ->first();

        return view('landing.konsentrasi-detail', [
            'jurusan' => $jurusan,
            'gelombangAktif' => $gelombangAktif,
            'rincianBiaya' => $gelombangAktif?->jurusanBiaya
                ?->firstWhere('jurusan_id', $jurusan->id),
        ]);
    }
}
