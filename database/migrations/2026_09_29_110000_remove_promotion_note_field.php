<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('minat_promosi', function (Blueprint $table) {
            $table->dropColumn('promotion_note');
        });
    }

    public function down(): void
    {
        Schema::table('minat_promosi', function (Blueprint $table) {
            $table->string('promotion_note', 500)->nullable()->after('major_interest');
        });
    }
};
