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
        if (!Schema::hasTable('logistics_event_participants')) {
            Schema::create('logistics_event_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('logistics_event_id')->constrained('logistics_events')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->string('assignment_type'); // Polymorphic type (project_assignment, vehicle_assignment, accommodation_assignment)
            $table->unsignedBigInteger('assignment_id'); // Polymorphic id
            $table->string('status')->default('pending'); // pending, in_transit, completed
            $table->timestamps();

            // Indexes
            $table->index(['logistics_event_id', 'employee_id']);
            $table->index(['assignment_type', 'assignment_id']); // For polymorphic queries
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logistics_event_participants');
    }
};
