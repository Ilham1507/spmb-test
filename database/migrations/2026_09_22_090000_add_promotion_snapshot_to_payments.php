<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi_pembayaran', function (Blueprint $table) {
            $table->string('promotion_name', 120)->nullable()->after('discount_amount');
        });

        Schema::table('payment_checkouts', function (Blueprint $table) {
            $table->string('promotion_name', 120)->nullable()->after('discount');
        });
    }

    public function down(): void
    {
        Schema::table('payment_checkouts', fn (Blueprint $table) => $table->dropColumn('promotion_name'));
        Schema::table('transaksi_pembayaran', fn (Blueprint $table) => $table->dropColumn('promotion_name'));
    }
};
