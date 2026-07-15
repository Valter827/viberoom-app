<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Таблица дружеских связей.
     * user_id     — тот, кто отправил заявку в друзья
     * friend_id   — тот, кому отправлена заявка
     * status      — pending | accepted
     *
     * Одна строка = одна связь между двумя пользователями.
     * Когда заявка принята, status меняется на accepted и связь
     * считается двусторонней (проверяется в обе стороны в модели).
     */
    public function up(): void
    {
        Schema::create('friendships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('friend_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('pending'); // pending|accepted
            $table->timestamps();

            $table->unique(['user_id', 'friend_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('friendships');
    }
};
