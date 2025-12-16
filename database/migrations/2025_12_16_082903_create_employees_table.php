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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            $table->date('a1_valid_from')->nullable(); // Prawo jazdy A1 ważne od
            $table->date('a1_valid_to')->nullable(); // Prawo jazdy A1 ważne do
            $table->string('document_1')->nullable(); // Dokument 1
            $table->string('document_2')->nullable(); // Dokument 2
            $table->string('document_3')->nullable(); // Dokument 3
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
