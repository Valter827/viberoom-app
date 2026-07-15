<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Поля для кастомизации профиля:
     * - bio: короткое описание "о себе", видно всем
     * - banner_color: цвет баннера профиля (hex)
     * - pronouns: местоимения (необязательно), как в Discord
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('bio')->nullable()->after('status');
            $table->string('banner_color', 7)->default('#5865F2')->after('bio');
            $table->string('pronouns')->nullable()->after('banner_color');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['bio', 'banner_color', 'pronouns']);
        });
    }
};
