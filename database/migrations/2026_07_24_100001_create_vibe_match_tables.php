<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * vibe_activities — текущий "движок" пользователя (во что играет / что
     * слушает / что ищет), задаётся вручную в чате. Одна активная запись на
     * пользователя (перезаписывается).
     *
     * channel_presences — лёгкий heartbeat "я сейчас смотрю на этот текстовый
     * канал", нужен, чтобы Vibe Match сравнивал только тех, кто реально в
     * комнате прямо сейчас, а не всех участников сервера.
     */
    public function up(): void
    {
        Schema::create('vibe_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('category'); // game | lfg | music
            $table->string('label', 80); // "Dota 2", "Interstellar OST" и т.п.
            $table->timestamps();
        });

        Schema::create('channel_presences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('last_seen_at');
            $table->unique(['channel_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_presences');
        Schema::dropIfExists('vibe_activities');
    }
};
