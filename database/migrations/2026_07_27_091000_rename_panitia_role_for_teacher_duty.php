<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('peran')->where('name', 'panitia')->update([
            'description' => 'Guru Panitia Piket SPMB',
        ]);
    }

    public function down(): void
    {
        DB::table('peran')->where('name', 'panitia')->update([
            'description' => 'Panitia Penerimaan',
        ]);
    }
};
