<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // 'text' (обычное сообщение) | 'party' (карточка пати-финдера)
            $table->string('type', 20)->default('text')->after('is_system');
        });

        Schema::create('party_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('channel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
            $table->string('game', 60);
            $table->string('mode', 40)->nullable(); // "Pos 4/5", "Ranked" и т.п. — свободный текст
            $table->unsignedTinyInteger('max_slots')->default(5);
            $table->string('status', 20)->default('open'); // open | full | cancelled
            $table->timestamps();
        });

        Schema::create('party_card_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('party_card_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('position');
            $table->timestamps();
            $table->unique(['party_card_id', 'position']);
            $table->unique(['party_card_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('party_card_slots');
        Schema::dropIfExists('party_cards');
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
