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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->string('type'); // typ dokumentu
            $table->date('valid_from'); // dokument ważny od
            $table->date('valid_to')->nullable(); // dokument ważny do (nullable dla bezterminowych)
            $table->enum('kind', ['terminowy', 'bezterminowy'])->default('terminowy'); // rodzaj dokumentu
            $table->text('notes')->nullable(); // dodatkowe notatki
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
