<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procedure_run_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procedure_run_id')->constrained()->cascadeOnDelete();
            $table->string('node_id');
            $table->string('node_name');
            $table->string('node_type');
            $table->timestamp('entered_at');
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            // checklist: [{item_id, checked}] | decision: {option_id, label}
            $table->json('data')->nullable();
            $table->timestamps();

            $table->index(['procedure_run_id', 'entered_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procedure_run_steps');
    }
};
