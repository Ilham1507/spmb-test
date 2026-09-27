<?php

namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;

class SchoolPageController extends Controller
{
    public function show(string $page)
    {
        if ($page === 'pimpinan') {
            return view('landing.pimpinan');
        }

        if ($page === 'program-studi') {
            return view('landing.program-sekolah');
        }

        if ($page === 'fasilitas') {
            return view('landing.fasilitas-sekolah');
        }

        if ($page === 'sambutan-kepala-sekolah') {
            return view('landing.sambutan-kepala-sekolah');
        }

        if ($page === 'sejarah') {
            return view('landing.sejarah-sekolah');
        }

        $pages = [
            'pimpinan' => [
                'eyebrow' => 'Tentang sekolah',
                'title' => 'Pimpinan sekolah',
                'description' => 'Kenali pimpinan yang mengarahkan layanan pendidikan dan pertumbuhan siswa di SMK Muhammadiyah 4 Cileungsi.',
                'image' => 'images/landing/activity-btq.png',
            ],
            'sejarah' => [
                'eyebrow' => 'Tentang sekolah',
                'title' => 'Sejarah sekolah',
                'description' => 'Perjalanan SMK Muhammadiyah 4 Cileungsi dalam membangun pendidikan vokasi yang dekat dengan kebutuhan masa depan.',
                'image' => 'images/landing/campus-carousel-1.png',
            ],
            'program-studi' => [
                'eyebrow' => 'Pendidikan',
                'title' => 'Program studi',
                'description' => 'Pembelajaran di SIMUPA dikelompokkan ke dalam dua program studi yang mengarahkan pilihan konsentrasi keahlian siswa.',
                'image' => 'images/landing/campus-carousel-2.png',
                'programs' => [
                    [
                        'number' => '01',
                        'name' => 'Kesehatan',
                        'description' => 'Bidang pembelajaran yang membangun kepedulian, ketelitian, dan keterampilan layanan kesehatan.',
                        'concentrations' => ['Layanan Kefarmasian Klinis dan Komunitas', 'Layanan Penunjang Keperawatan dan Caregiving'],
                    ],
                    [
                        'number' => '02',
                        'name' => 'Pariwisata',
                        'description' => 'Bidang pembelajaran kreatif yang mengembangkan layanan, produk, dan pengalaman wisata.',
                        'concentrations' => ['Kuliner', 'Desain dan Produksi Busana'],
                    ],
                ],
            ],
            'fasilitas' => [
                'eyebrow' => 'Pendidikan',
                'title' => 'Fasilitas sekolah',
                'description' => 'Ruang belajar dan praktik disiapkan untuk mendukung pembelajaran vokasi, pengembangan keterampilan, dan kegiatan siswa.',
                'image' => 'images/landing/campus-carousel-3.png',
                'facilities' => [
                    [
                        'name' => 'Laboratorium Kesehatan',
                        'description' => 'Ruang praktik untuk pembelajaran kefarmasian dan layanan penunjang keperawatan.',
                        'image' => 'images/landing/campus-carousel-3.png',
                    ],
                    [
                        'name' => 'Dapur Praktik Kuliner',
                        'description' => 'Ruang produksi untuk mengembangkan keterampilan pengolahan dan penyajian makanan.',
                        'image' => 'images/landing/campus-carousel-2.png',
                    ],
                    [
                        'name' => 'Ruang Praktik Busana',
                        'description' => 'Area belajar untuk proses desain, produksi, dan pengembangan karya busana.',
                        'image' => 'images/landing/campus-hero-v1.png',
                    ],
                    [
                        'name' => 'Ruang Kegiatan Siswa',
                        'description' => 'Ruang yang mendukung pembelajaran, kolaborasi, dan aktivitas pengembangan siswa.',
                        'image' => 'images/landing/campus-carousel-1.png',
                    ],
                ],
            ],
        ];

        abort_unless(isset($pages[$page]), 404);

        return view('landing.school-page', ['page' => $pages[$page]]);
    }
}
