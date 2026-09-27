<?php

namespace Database\Seeders;

use App\Models\Siswa;
use App\Models\User;
use App\Models\BankAccount;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::firstOrCreate(['email' => 'panitia@smkm4.test'], ['name' => 'Panitia SPMB', 'phone' => '081111111111', 'role' => 'panitia', 'password' => Hash::make('password')]);
        User::firstOrCreate(['email' => 'bendahara@smkm4.test'], ['name' => 'Bendahara SPMB', 'phone' => '082222222222', 'role' => 'bendahara', 'password' => Hash::make('password')]);
        BankAccount::firstOrCreate(['account_number' => '0000000000'], ['bank_name' => 'BMT PCM Cileungsi', 'account_holder' => 'SMK Muhammadiyah 4 Cileungsi', 'is_active' => true]);
        foreach ([
            ['nama' => 'Andi Pratama', 'nis' => '2026001', 'kelas' => 'XII', 'jurusan' => 'RPL'],
            ['nama' => 'Siti Aisyah', 'nis' => '2026002', 'kelas' => 'XII', 'jurusan' => 'TKJ'],
            ['nama' => 'Budi Santoso', 'nis' => '2026003', 'kelas' => 'XI', 'jurusan' => 'RPL'],
        ] as $siswa) Siswa::updateOrCreate(['nis' => $siswa['nis']], $siswa);
    }
}
