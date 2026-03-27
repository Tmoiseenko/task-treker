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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('moonshine_author_id')->constrained('moonshine_users')->onDelete('cascade');
            $table->foreignId('moonshine_assignee_id')->nullable()->constrained('moonshine_users')->onDelete('set null');
            $table->string('priority')->default('medium'); // high, medium, low, frozen
            $table->string('status')->default('todo'); // todo, in_progress, in_testing, test_failed, done
            $table->timestamp('due_date')->nullable();
            $table->foreignId('parent_task_id')->nullable()->constrained('tasks')->onDelete('cascade'); // Для баг-репортов
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
