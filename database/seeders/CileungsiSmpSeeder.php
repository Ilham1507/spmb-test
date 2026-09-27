<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CileungsiSmpSeeder extends Seeder
{
    /**
     * SMP in Kecamatan Cileungsi, Kabupaten Bogor.
     * Source: Referensi Satuan Pendidikan Kemendikdasmen.
     */
    public function run(): void
    {
        $location = [
            'bentuk_pendidikan' => 'SMP',
            'status' => 'aktif',
            'kecamatan' => 'Cileungsi',
            'kabupaten_kota' => 'Kab. Bogor',
            'provinsi' => 'Jawa Barat',
        ];

        $schools = [
            ['69915055', 'SMP AL IMAM ISLAMIC SCHOOL', 'Limus Nunggal', 'Limus Pratama Regency, Jl. Blitar Blok E.12/7B RT 004 RW 011'],
            ['20254671', 'SMP AMAL MULIA 2', 'Pasir Angin', 'Jl. Amal Mulia No. 1, Kp. Cinyosog'],
            ['60726327', 'SMP AVICENNA CILEUNGSI', 'Cileungsi Kidul', 'Jl. Raya Cileungsi Jonggol KM 01'],
            ['69917708', 'SMP BINTANG TIMUR', 'Pasir Angin', 'Kp. Cinyosog RT 02 RW 02'],
            ['20230956', 'SMPS BPS&K TERPADU CILEUNGSI', 'Cipeucang', 'Jl. Raya Cileungsi Jonggol KM 10'],
            ['69905906', 'SMP CIKAL CENDEKIA', 'Gandoang', 'Kp. Gandoang RT 01 RW 09'],
            ['20230966', 'SMP DARUSSALAM CILEUNGSI', 'Cileungsi', 'Jl. Alternatif Cileungsi Cibubur'],
            ['20230971', 'SMP FAJAR DUNIA', 'Mampir', 'Kp. Palasari 50 RT 21 RW 10'],
            ['69896557', 'SMP IBADURRAHMAN CILEUNGSI', 'Gandoang', 'Jl. Raya Cileungsi-Jonggol KM 7'],
            ['20271842', 'SMP IBNU SYINA', 'Dayeuh', 'Kp. Cikadu RT 02 RW 02'],
            ['20267939', 'SMP ISLAM DAARUL ISTIQOOMAH', 'Cileungsi', 'Jl. H. Satibi'],
            ['69886197', 'SMP ISLAM CITRA NUANSA', null, null],
            ['69970773', 'SMP ISLAM TERPADU AL-FITYAN BOARDING SCHOOL BOGOR', null, null],
            ['20200584', 'SMP ISLAM NUURUSHSHIBYAN', null, null],
            ['69984510', 'SMP ISLAM YP31 CILEUNGSI', null, null],
            ['20256240', 'SMP MUHAMMADIYAH 2 CILEUNGSI', null, null],
            ['20231100', 'SMP MUHAMMADIYAH CILEUNGSI', null, null],
            ['20200612', 'SMP NEGERI 1 CILEUNGSI', 'Cileungsi Kidul', 'Jl. Raya Cileungsi, Kab. Bogor'],
            ['20200628', 'SMP NEGERI 2 CILEUNGSI', 'Setu Sari', 'Kp. Ciuncal RT 02 RW 04'],
            ['69757407', 'SMP NEGERI 3 CILEUNGSI', 'Pasir Angin', 'Perum Griya Alam Sentosa Blok U RT 09 RW 09'],
            ['69925652', 'SMP NEGERI 4 CILEUNGSI', 'Gandoang', 'Kp. Panangga RT 03 RW 05'],
            ['20231106', 'SMPS NUSANTARA', 'Dayeuh', 'Jl. Raya Samick-Rawailat'],
            ['20231121', 'SMP PERINTIS CILEUNGSI', null, null],
            ['20231130', 'SMP PGRI GANDOANG', null, null],
            ['20200696', 'SMP PGRI SURYAKENCANA', null, null],
            ['20231141', 'SMPS PLUS AL MAHFUDZ', 'Mekarsari', 'Jl. Raya Cileungsi-Jonggol'],
            ['20231142', 'SMP PLUS ARRUSYDA', null, null],
            ['69883650', 'SMP PLUS TAMAN WISATA', 'Mampir', 'Kp. Nyalindung'],
            ['69939933', 'SMP QURAN ELTAHFIDH', 'Jatisari', 'Kp. Kubang RT 011 RW 005'],
            ['20231151', 'SMP SEJAHTERA 2', 'Cileungsi Kidul', 'Jl. Raya Cileungsi Bogor'],
            ['20231156', 'SMPS TAMAN QURANIYAH', null, null],
            ['69932573', 'SMP IT FATAHILLAH CILEUNGSI', null, null],
            ['20231061', 'SMP-IT AL HASANIYYAH', 'Cipeucang', 'Jl. Raya Cileungsi-Jonggol KM 10'],
            ['69878675', 'SMPIT MUTIARA ISLAM', 'Cileungsi', 'Jl. Perum Pondok Cileungsi RT 02 RW 15'],
            ['20254651', 'SMPS AL-HADIID', 'Cileungsi Kidul', 'Jl. Melati I, Perum Cileungsi Indah'],
            ['20231164', 'SMP WIDYA KUSUMA', 'Cileungsi Kidul', 'Perum Cileungsi Hijau, Jl. Thata Raya Boulevard'],
            ['20232207', 'SMP YAPA I', 'Cileungsi', 'Blok Pandai RT 01 RW 17'],
            ['20231173', 'SMP YPB', 'Limus Nunggal', 'Jl. Setu Rawahingkik RT 01 RW 01'],
        ];

        foreach ($schools as [$npsn, $nama, $desaKelurahan, $alamat]) {
            DB::table('referensi_sekolah')->updateOrInsert(
                ['npsn' => $npsn],
                array_merge($location, [
                    'nama' => $nama,
                    'alamat' => $alamat,
                    'desa_kelurahan' => $desaKelurahan,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]),
            );
        }
    }
}
