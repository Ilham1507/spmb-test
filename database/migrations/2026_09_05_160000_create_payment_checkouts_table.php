<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi_pembayaran', function (Blueprint $table) {
            $table->unsignedBigInteger('discount_amount')->default(0);
            $table->decimal('bill_total_snapshot', 14, 2)->nullable();
        });
        Schema::create('payment_checkouts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bill_id')->index();
            // Only one unresolved checkout per bill, even with concurrent requests.
            $table->unsignedBigInteger('active_bill_id')->nullable()->unique();
            $table->string('order_id', 50)->unique();
            $table->boolean('production')->default(false);
            $table->string('merchant_id');
            $table->unsignedBigInteger('amount');
            $table->decimal('bill_total', 14, 2);
            $table->decimal('bill_paid', 14, 2);
            $table->unsignedBigInteger('discount')->default(0);
            $table->json('selected_items')->nullable();
            $table->string('status')->default('creating')->index();
            $table->string('provider_status')->nullable();
            $table->string('provider_transaction_id')->nullable()->unique();
            $table->text('redirect_url')->nullable();
            $table->unsignedBigInteger('transaction_id')->nullable()->unique();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_checkouts');
        Schema::table('transaksi_pembayaran', fn (Blueprint $table) => $table->dropColumn(['discount_amount', 'bill_total_snapshot']));
    }
};
