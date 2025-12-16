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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('registration_number')->unique(); // Numer rejestracyjny
            $table->string('brand')->nullable(); // Marka pojazdu
            $table->string('model')->nullable(); // Model pojazdu
            $table->integer('capacity')->nullable(); // Pojemność (liczba osób)
            $table->enum('technical_condition', ['excellent', 'good', 'fair', 'poor'])->default('good'); // Stan techniczny
            $table->date('inspection_valid_to')->nullable(); // Przegląd ważny do
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
