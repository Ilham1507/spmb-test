<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('whatsapp_chat_replies', function (Blueprint $table) {
            $table->id();
            $table->string('recipient_phone', 20)->index();
            $table->unsignedBigInteger('admin_id');
            $table->text('body');
            $table->string('wa_message_id')->nullable()->unique();
            $table->string('status', 20)->default('sending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_chat_replies');
    }
};
