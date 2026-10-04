<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('whatsapp_chat_reads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id');
            $table->string('phone', 20);
            $table->unsignedBigInteger('last_message_id')->default(0);
            $table->timestamps();
            $table->unique(['admin_id', 'phone']);
        });
    }

    public function down(): void { Schema::dropIfExists('whatsapp_chat_reads'); }
};
