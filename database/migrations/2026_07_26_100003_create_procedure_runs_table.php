<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procedure_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procedure_template_id')->constrained()->cascadeOnDelete();
            $table->json('definition_snapshot');
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('current_node_id');
            $table->json('path'); // ordered array of visited node ids
            $table->enum('status', ['in_progress', 'finished', 'abandoned'])->default('in_progress');
            $table->json('variables')->nullable();
            $table->foreignId('started_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procedure_runs');
    }
};
