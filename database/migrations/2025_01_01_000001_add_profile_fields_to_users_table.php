<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Добавляем к стандартной таблице users поля, нужные для VibeRoom:
     * - avatar_path: путь к файлу аватара
     * - status: онлайн-статус (online, idle, dnd, offline)
     * - last_seen_at: время последней активности (для авто-статуса "offline")
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar_path')->nullable()->after('email');
            $table->string('status')->default('offline')->after('avatar_path'); // online|idle|dnd|offline
            $table->timestamp('last_seen_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['avatar_path', 'status', 'last_seen_at']);
        });
    }
};
