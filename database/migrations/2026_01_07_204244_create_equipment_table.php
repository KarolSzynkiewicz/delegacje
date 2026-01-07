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
        if (!Schema::hasTable('equipment')) {
            Schema::create('equipment', function (Blueprint $table) {
                $table->id();
                $table->string('name'); // np. "Kask ochronny", "Buty BHP", "Maska spawalnicza"
                $table->text('description')->nullable();
                $table->string('category')->nullable(); // np. "Ochrona", "Narzędzia", "Elektronika"
                $table->integer('quantity_in_stock')->default(0);
                $table->integer('min_quantity')->default(0); // Minimum stock level
                $table->string('unit')->default('szt'); // Jednostka (szt, kg, m, etc.)
                $table->decimal('unit_cost', 10, 2)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index('category');
                $table->index('name');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment');
    }
};
