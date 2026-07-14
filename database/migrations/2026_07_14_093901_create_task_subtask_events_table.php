<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_subtask_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subtask_id')
                ->constrained('task_subtasks')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('event', 32); // created, completed, reopened, renamed, deleted
            $table->timestamp('created_at')->useCurrent();

            $table->index(['subtask_id', 'event']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_subtask_events');
    }
};
