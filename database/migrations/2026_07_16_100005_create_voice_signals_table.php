<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Почтовый ящик" WebRTC-сигналов (offer/answer/ice candidate) между двумя
     * участниками голосового канала. Вместо постоянного WebSocket-сервера
     * клиенты просто опрашивают эту таблицу — так голосовые звонки можно
     * поднять на обычном shared-хостинге без Reverb/Node-процесса.
     */
    public function up(): void
    {
        Schema::create('voice_signals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('to_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 16); // offer|answer|candidate|leave
            $table->longText('payload');
            $table->timestamps();

            $table->index(['channel_id', 'to_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voice_signals');
    }
};
