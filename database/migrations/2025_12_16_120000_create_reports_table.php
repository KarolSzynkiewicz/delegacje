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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['delegation_summary', 'employee_hours', 'project_status']);
            $table->text('description')->nullable();
            $table->json('filters')->nullable();
            $table->foreignId('generated_by')->constrained('users');
            $table->timestamp('generated_at')->nullable();
            $table->string('file_path')->nullable();
            $table->enum('format', ['pdf', 'excel', 'html'])->default('pdf');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
