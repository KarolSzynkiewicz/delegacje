<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('procedure_template_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procedure_template_id')->constrained()->cascadeOnDelete();
            $table->json('definition');
            $table->foreignId('changed_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('changed_at');
            $table->index(['procedure_template_id', 'changed_at'], 'ptv_template_changed_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('procedure_template_versions');
    }
};
