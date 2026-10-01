<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_email_deliveries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transaction_id');
            $table->string('recipient', 150);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->unique(['transaction_id', 'recipient']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_email_deliveries');
    }
};
