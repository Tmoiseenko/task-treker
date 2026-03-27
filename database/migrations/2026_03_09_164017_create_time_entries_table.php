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
        Schema::create('time_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_stage_id')->constrained()->onDelete('cascade');
            $table->foreignId('moonshine_user_id')->constrained('moonshine_users')->onDelete('cascade');
            $table->decimal('hours', 8, 2);
            $table->date('date');
            $table->text('description')->nullable();
            $table->decimal('cost', 10, 2);
            $table->timestamps();

            // Индексы для быстрого поиска записей времени
            $table->index('task_stage_id');
            $table->index('moonshine_user_id');
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_entries');
    }
};
