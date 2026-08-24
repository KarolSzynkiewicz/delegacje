<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_requests', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('approver_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('comment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sprint_id')->nullable()->constrained('sprints')->nullOnDelete();
            $table->string('category')->nullable();
            $table->unsignedTinyInteger('priority')->nullable();
            $table->date('due_at')->nullable();
            $table->string('decision', 16)->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['approver_id', 'decision']);
            $table->index('comment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_requests');
    }
};
