<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Тумблеры новых фич на уровне сервера — владелец/админ может
     * включать/выключать их в настройках (вкладка "Функции").
     */
    public function up(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->boolean('vibe_match_enabled')->default(true)->after('invite_code');
            $table->boolean('party_finder_enabled')->default(true)->after('vibe_match_enabled');
            $table->boolean('tactical_canvas_enabled')->default(true)->after('party_finder_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn(['vibe_match_enabled', 'party_finder_enabled', 'tactical_canvas_enabled']);
        });
    }
};
