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
        Schema::create('employee_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->integer('engagement')->unsigned()->comment('Zaangażowanie (1-10)');
            $table->integer('skills')->unsigned()->comment('Umiejętności (1-10)');
            $table->integer('orderliness')->unsigned()->comment('Porządek (1-10)');
            $table->integer('behavior')->unsigned()->comment('Zachowanie (1-10)');
            $table->text('notes')->nullable()->comment('Uwagi');
            $table->timestamps();

            // Indexes
            $table->index('employee_id');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_evaluations');
    }
};
