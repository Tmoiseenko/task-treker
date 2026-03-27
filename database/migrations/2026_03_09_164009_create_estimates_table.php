<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('estimates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_stage_id')->constrained()->onDelete('cascade');
            $table->foreignId('moonshine_user_id')->constrained('moonshine_users')->onDelete('cascade');
            $table->decimal('hours', 8, 2);
            $table->timestamps();

            // Индекс для быстрого поиска оценок по этапу задачи
            $table->index('task_stage_id');
            // Индекс для быстрого поиска оценок по пользователю
            $table->index('moonshine_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estimates');
    }
};
