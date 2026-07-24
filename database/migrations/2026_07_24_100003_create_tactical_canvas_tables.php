<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Один тактический "холст" на текстовый канал. Штрихи хранятся отдельной
     * таблицей и синхронизируются тем же способом, что и сообщения — polling
     * "дай всё, что появилось после id X". Координаты штрихов — в процентах
     * (0-100) от размера холста, чтобы не зависеть от разрешения экрана.
     */
    public function up(): void
    {
        Schema::create('tactical_boards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('map_key', 30)->default('blank'); // dota | cs | valorant | rust | blank
            $table->unsignedInteger('version')->default(1); // растёт при очистке — клиент понимает, что нужно перерисовать с нуля
            $table->timestamps();
        });

        Schema::create('tactical_strokes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tactical_board_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version'); // на каком "поколении" холста нарисован штрих
            $table->string('tool', 10)->default('pen'); // pen | arrow | line
            $table->string('color', 10)->default('#f23f42');
            $table->unsignedTinyInteger('width')->default(3);
            $table->text('points'); // JSON: [{x:12.3,y:44.1}, ...] в процентах
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tactical_strokes');
        Schema::dropIfExists('tactical_boards');
    }
};
