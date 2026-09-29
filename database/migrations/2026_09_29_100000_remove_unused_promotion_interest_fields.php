<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('minat_promosi', function (Blueprint $table) {
            $table->dropColumn(['parent_phone', 'class_level']);
        });
    }

    public function down(): void
    {
        Schema::table('minat_promosi', function (Blueprint $table) {
            $table->string('parent_phone', 20)->nullable()->after('student_phone');
            $table->string('class_level', 30)->nullable()->after('school_name');
        });
    }
};
