<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 20);
            $table->string('subject_type', 120);
            $table->unsignedBigInteger('subject_id');
            $table->string('category', 80);
            $table->string('subject_label', 180);
            $table->json('changes')->nullable();
            $table->string('route_name', 180)->nullable();
            $table->timestamps();
            $table->index(['category', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('audit_logs'); }
};
