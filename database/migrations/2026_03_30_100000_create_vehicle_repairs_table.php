<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_repairs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action_type', 50);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->char('currency', 3)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('fixed_cost_entry_id')->nullable()->constrained('fixed_cost_entries')->nullOnDelete();
            $table->string('previous_technical_condition', 20)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_repairs');
    }
};
