<?php

namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;
use App\Models\GelombangPendaftaran;
use App\Models\Jurusan;
use App\Models\Faq;
use App\Models\Pengumuman;

class HomeController extends Controller
{
    public function index()
    {
        return view('landing.home', [
            'jurusans' => Jurusan::where('status', 'aktif')->orderBy('id')->take(4)->get(),
            'gelombangAktif' => GelombangPendaftaran::where('status', 'aktif')->first(),
            'pengumumans' => Pengumuman::where('status', 'publish')
                ->where(fn ($query) => $query->whereNull('tampil_mulai')->orWhere('tampil_mulai', '<=', now()))
                ->where(fn ($query) => $query->whereNull('tampil_sampai')->orWhere('tampil_sampai', '>=', now()))
                ->latest('tampil_mulai')
                ->latest()
                ->take(3)
                ->get(),
        ]);
    }

    public function faq()
    {
        return view('landing.faq', [
            'faqs' => Faq::where('status', true)->orderBy('urutan')->orderBy('id')->get(),
        ]);
    }

    public function pengumuman()
    {
        return view('landing.pengumuman', [
            'pengumumans' => Pengumuman::where('status', 'publish')
                ->where(fn ($query) => $query->whereNull('tampil_mulai')->orWhere('tampil_mulai', '<=', now()))
                ->where(fn ($query) => $query->whereNull('tampil_sampai')->orWhere('tampil_sampai', '>=', now()))
                ->latest('tampil_mulai')
                ->latest()
                ->get(),
        ]);
    }

    public function artikel(Pengumuman $pengumuman)
    {
        abort_unless(
            $pengumuman->status === 'publish'
            && (! $pengumuman->tampil_mulai || $pengumuman->tampil_mulai->isPast())
            && (! $pengumuman->tampil_sampai || $pengumuman->tampil_sampai->isFuture()),
            404
        );

        return view('landing.artikel', compact('pengumuman'));
    }

    public function artikelSorotan(string $slug)
    {
        $articles = [
            'festival-seni' => ['Festival Seni dan Kreativitas Siswa', 'Siswa menampilkan karya, musik, dan pertunjukan dalam agenda sekolah.'],
            'pramuka' => ['Latihan Kepemimpinan Pramuka', 'Belajar kerja sama, disiplin, dan tanggung jawab bersama.'],
            'futsal' => ['Ekstrakurikuler Futsal', 'Ruang bagi siswa untuk aktif, sehat, dan berprestasi.'],
        ];

        abort_unless(isset($articles[$slug]), 404);
        [$title, $body] = $articles[$slug];
        $pengumuman = new Pengumuman(['judul' => $title, 'isi' => $body, 'status' => 'publish']);
        $pengumuman->tampil_mulai = now();

        return view('landing.artikel', compact('pengumuman'));
    }
}
