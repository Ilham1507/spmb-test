<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_checkouts', function (Blueprint $table) {
            $table->string('provider_payment_type')->nullable()->after('provider_status');
            $table->string('provider_bank')->nullable()->after('provider_payment_type');
        });
    }

    public function down(): void
    {
        Schema::table('payment_checkouts', function (Blueprint $table) {
            $table->dropColumn(['provider_payment_type', 'provider_bank']);
        });
    }
};
