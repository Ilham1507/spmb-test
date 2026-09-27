<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id(); $table->string('bank_name'); $table->string('account_number')->unique(); $table->string('account_holder'); $table->boolean('is_active')->default(true); $table->timestamps();
        });
        Schema::create('payments', function (Blueprint $table) {
            $table->id(); $table->foreignId('siswa_id')->constrained()->cascadeOnDelete();
            $table->string('payment_type'); $table->string('method'); $table->unsignedBigInteger('amount');
            $table->foreignId('bank_account_id')->nullable()->constrained()->nullOnDelete(); $table->string('proof_path')->nullable();
            $table->string('status')->default('pending_panitia'); $table->string('invoice_number')->unique(); $table->text('notes')->nullable(); $table->timestamp('paid_at');
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamp('approved_at')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamp('received_at')->nullable(); $table->timestamps();
        });
        Schema::create('payment_details', function (Blueprint $table) {
            $table->id(); $table->foreignId('payment_id')->constrained()->cascadeOnDelete(); $table->string('fee_name'); $table->unsignedBigInteger('amount'); $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('payment_details'); Schema::dropIfExists('payments'); Schema::dropIfExists('bank_accounts'); }
};
