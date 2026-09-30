<?php

namespace App\Support;

class FeeCategory
{
    public static function for(string $name): string
    {
        $value = strtolower($name);

        return match (true) {
            str_contains($value, 'seragam'), str_contains($value, 'baju'), str_contains($value, 'atribut'), str_contains($value, 'sepatu'), str_contains($value, 'tas') => 'Seragam & Perlengkapan',
            str_contains($value, 'buku'), str_contains($value, 'lks'), str_contains($value, 'modul'), str_contains($value, 'atlas'), str_contains($value, 'alat tulis') => 'Buku & Pembelajaran',
            str_contains($value, 'formulir'), str_contains($value, 'daftar'), str_contains($value, 'administrasi'), str_contains($value, 'kartu pelajar'), str_contains($value, 'dokumen') => 'Administrasi & Dokumen',
            str_contains($value, 'praktik'), str_contains($value, 'kejuruan'), str_contains($value, 'bahan praktik'), str_contains($value, 'pkl'), str_contains($value, 'workshop') => 'Praktik & Kejuruan',
            str_contains($value, 'study'), str_contains($value, 'kegiatan'), str_contains($value, 'osis'), str_contains($value, 'fortasi'), str_contains($value, 'mpls'), str_contains($value, 'ekskul'), str_contains($value, 'wisata') => 'Kegiatan & Kesiswaan',
            str_contains($value, 'ujian'), str_contains($value, 'tes'), str_contains($value, 'asesmen'), str_contains($value, 'sertifikasi') => 'Asesmen & Kompetensi',
            str_contains($value, 'kesehatan'), str_contains($value, 'uks'), str_contains($value, 'asuransi'), str_contains($value, 'medical') => 'Kesehatan & Perlindungan',
            str_contains($value, 'lab'), str_contains($value, 'komputer'), str_contains($value, 'internet'), str_contains($value, 'teknologi'), str_contains($value, 'fasilitas') => 'Fasilitas & Teknologi',
            str_contains($value, 'transport'), str_contains($value, 'angkutan'), str_contains($value, 'bus'), str_contains($value, 'penginapan'), str_contains($value, 'akomodasi') => 'Transportasi & Akomodasi',
            str_contains($value, 'zakat'), str_contains($value, 'infak'), str_contains($value, 'zis'), str_contains($value, 'sedekah'), str_contains($value, 'sosial'), str_contains($value, 'rohani') => 'Kerohanian & Sosial',
            str_contains($value, 'infaq'), str_contains($value, 'gedung'), str_contains($value, 'spp'), str_contains($value, 'pendidikan'), str_contains($value, 'pangkal'), str_contains($value, 'pembinaan'), str_contains($value, 'tabungan') => 'Biaya Pendidikan',
            default => 'Lainnya',
        };
    }
}
