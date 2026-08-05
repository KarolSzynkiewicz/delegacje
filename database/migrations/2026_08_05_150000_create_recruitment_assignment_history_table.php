<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment_assignment_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recruitment_process_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_recruiter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('to_recruiter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['recruitment_process_id', 'created_at'], 'recruitment_assignment_history_process_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_assignment_history');
    }
};
