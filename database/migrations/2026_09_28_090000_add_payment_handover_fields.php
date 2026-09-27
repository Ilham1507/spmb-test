<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi_pembayaran', function (Blueprint $table) {
            $table->foreignId('treasurer_received_by')->nullable()->after('verified_by')->constrained('users')->nullOnDelete();
            $table->timestamp('treasurer_received_at')->nullable()->after('verified_at');
            $table->text('treasurer_notes')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('transaksi_pembayaran', function (Blueprint $table) {
            $table->dropConstrainedForeignId('treasurer_received_by');
            $table->dropColumn(['treasurer_received_at', 'treasurer_notes']);
        });
    }
};
