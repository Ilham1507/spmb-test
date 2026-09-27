<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi_pembayaran', function (Blueprint $table) {
            $table->json('selected_items')->nullable()->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('transaksi_pembayaran', function (Blueprint $table) {
            $table->dropColumn('selected_items');
        });
    }
};
