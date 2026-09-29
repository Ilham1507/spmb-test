<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('minat_promosi', function (Blueprint $table) {
            $table->id();
            $table->string('full_name', 150);
            $table->string('student_phone', 20)->unique();
            $table->string('parent_phone', 20)->nullable();
            $table->string('school_name', 180);
            $table->string('class_level', 30)->nullable();
            $table->foreignId('interested_major_id')->nullable()->constrained('jurusan')->nullOnDelete();
            $table->string('major_interest', 150)->nullable();
            $table->string('promotion_note', 500)->nullable();
            $table->timestamp('submitted_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('minat_promosi');
    }
};
