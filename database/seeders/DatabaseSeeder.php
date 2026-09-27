<?php

namespace Database\Seeders;

use App\Models\User;
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
        $roles = [
            ['name' => 'admin', 'description' => 'Administrator'],
            ['name' => 'panitia', 'description' => 'Guru Panitia Piket SPMB'],
            ['name' => 'bendahara', 'description' => 'Bendahara Keuangan'],
            ['name' => 'kepala_sekolah', 'description' => 'Kepala Sekolah'],
            ['name' => 'peserta', 'description' => 'Calon Siswa Baru'],
        ];

        foreach ($roles as $role) {
            \App\Models\Peran::firstOrCreate(['name' => $role['name']], $role);
        }

        $adminRole = \App\Models\Peran::where('name', 'admin')->first();
        $panitiaRole = \App\Models\Peran::where('name', 'panitia')->first();
        $bendaharaRole = \App\Models\Peran::where('name', 'bendahara')->first();
        $pesertaRole = \App\Models\Peran::where('name', 'peserta')->first();

        // Create test users
        User::firstOrCreate(
            ['email' => 'admin@smk.sch.id'],
            [
                'name' => 'Admin Utama',
                'phone' => '081100000001',
                'password' => bcrypt('password'),
                'role_id' => $adminRole->id,
            ]
        );

        User::firstOrCreate(
            ['email' => 'panitia@smk.sch.id'],
            [
                'name' => 'Panitia PMB',
                'phone' => '081100000002',
                'password' => bcrypt('password'),
                'role_id' => $panitiaRole->id,
            ]
        );

        User::firstOrCreate(
            ['email' => 'bendahara@smk.sch.id'],
            [
                'name' => 'Bendahara Keuangan',
                'phone' => '081100000003',
                'password' => bcrypt('password'),
                'role_id' => $bendaharaRole->id,
            ]
        );

        User::firstOrCreate(
            ['email' => 'peserta@gmail.com'],
            [
                'name' => 'Bagus Setiawan',
                'phone' => '081100000004',
                'password' => bcrypt('password'),
                'role_id' => $pesertaRole->id,
            ]
        );

        $this->call(SpmbMasterSeeder::class);
        $this->call(CileungsiSmpSeeder::class);
    }
}
