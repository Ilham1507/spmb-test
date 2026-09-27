<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->unique()->after('email');
            $table->string('role')->default('siswa')->after('password');
            $table->timestamp('account_activated_at')->nullable();
        });
        Schema::table('siswas', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->json('form_data')->nullable();
        });
    }
    public function down(): void { Schema::table('siswas', fn (Blueprint $t) => $t->dropConstrainedForeignId('user_id')); Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['phone', 'role', 'account_activated_at'])); }
};
