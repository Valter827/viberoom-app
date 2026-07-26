<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            // Личный чат (DM) — канал без сервера, поэтому server_id должен допускать null
            $table->foreignId('server_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->foreignId('server_id')->nullable(false)->change();
        });
    }
};
