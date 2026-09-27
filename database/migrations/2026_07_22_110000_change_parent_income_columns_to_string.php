<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['data_ayah', 'data_ibu', 'data_wali'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'income')) {
                DB::statement("ALTER TABLE `{$table}` MODIFY `income` VARCHAR(100) NULL");
            }
        }
    }

    public function down(): void
    {
        foreach (['data_ayah', 'data_ibu', 'data_wali'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'income')) {
                DB::statement("ALTER TABLE `{$table}` MODIFY `income` DECIMAL(15,2) NULL");
            }
        }
    }
};
