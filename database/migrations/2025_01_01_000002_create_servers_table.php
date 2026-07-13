<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('icon_path')->nullable(); // иконка сервера
            // owner_id — владелец сервера, при удалении пользователя сервер тоже удаляем
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            // уникальный код приглашения, по которому можно вступить на сервер
            $table->string('invite_code', 12)->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servers');
    }
};
