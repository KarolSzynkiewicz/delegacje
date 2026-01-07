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
        if (!Schema::hasTable('equipment_requirements')) {
            Schema::create('equipment_requirements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
                $table->foreignId('equipment_id')->constrained('equipment')->onDelete('cascade');
                $table->integer('required_quantity')->default(1);
                $table->boolean('is_mandatory')->default(true);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['role_id', 'equipment_id']);
                $table->index('role_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment_requirements');
    }
};
