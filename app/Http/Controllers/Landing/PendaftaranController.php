<?php

namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;
use App\Models\GelombangPendaftaran;
use App\Models\JadwalSpmb;
use App\Models\JenisTagihan;
use App\Models\Jurusan;
use App\Models\SystemSetting;
use App\Models\TesMasuk;

class PendaftaranController extends Controller
{
    public function biaya()
    {
        return view('landing.biaya', [
            'tagihans' => JenisTagihan::orderBy('id')->get(),
            'jurusans' => Jurusan::where('status', 'aktif')->orderBy('id')->get(),
            'gelombangAktif' => GelombangPendaftaran::with('jurusanBiaya')->where('status', 'aktif')->first(),
        ]);
    }

    public function alur()
    {
        $flow = collect(SystemSetting::landingSections())->firstWhere('id', 'flow');

        return view('landing.alur', [
            'flow' => $flow,
            'gelombangAktif' => GelombangPendaftaran::with('jurusanBiaya')->where('status', 'aktif')->first(),
            'jurusans' => Jurusan::where('status', 'aktif')->orderBy('id')->get(),
            'jadwalSpmb' => JadwalSpmb::latest('tanggal_mulai')->get(),
            'tesMasuk' => TesMasuk::orderBy('test_date')->orderBy('id')->get(),
        ]);
    }
}
