<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Older Railway databases were bootstrapped before the manual-payment
     * fields existed. Add only missing fields so both fresh and long-lived
     * databases can accept payments entered by panitia.
     */
    public function up(): void
    {
        if (! Schema::hasTable('transaksi_pembayaran')) {
            return;
        }

        $missing = [
            'selected_items' => ! Schema::hasColumn('transaksi_pembayaran', 'selected_items'),
            'discount_amount' => ! Schema::hasColumn('transaksi_pembayaran', 'discount_amount'),
            'bill_total_snapshot' => ! Schema::hasColumn('transaksi_pembayaran', 'bill_total_snapshot'),
            'received_amount' => ! Schema::hasColumn('transaksi_pembayaran', 'received_amount'),
            'change_amount' => ! Schema::hasColumn('transaksi_pembayaran', 'change_amount'),
            'promotion_name' => ! Schema::hasColumn('transaksi_pembayaran', 'promotion_name'),
        ];

        if (! in_array(true, $missing, true)) {
            return;
        }

        Schema::table('transaksi_pembayaran', function (Blueprint $table) use ($missing): void {
            if ($missing['selected_items']) {
                $table->json('selected_items')->nullable();
            }
            if ($missing['discount_amount']) {
                $table->unsignedBigInteger('discount_amount')->default(0);
            }
            if ($missing['bill_total_snapshot']) {
                $table->decimal('bill_total_snapshot', 14, 2)->nullable();
            }
            if ($missing['received_amount']) {
                $table->decimal('received_amount', 14, 2)->nullable();
            }
            if ($missing['change_amount']) {
                $table->decimal('change_amount', 14, 2)->default(0);
            }
            if ($missing['promotion_name']) {
                $table->string('promotion_name', 120)->nullable();
            }
        });
    }

    public function down(): void {}
};
