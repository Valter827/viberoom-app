<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Промежуточная таблица many-to-many между users и servers,
     * с дополнительным полем role (owner, admin, member).
     */
    public function up(): void
    {
        Schema::create('server_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('member'); // owner|admin|member
            $table->timestamps();

            // пользователь не может состоять в одном сервере дважды
            $table->unique(['server_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_members');
    }
};
