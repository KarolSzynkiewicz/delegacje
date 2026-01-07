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
        if (!Schema::hasTable('transport_costs')) {
            Schema::create('transport_costs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('logistics_event_id')->nullable()->constrained('logistics_events')->onDelete('set null');
                $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->onDelete('set null');
                $table->foreignId('transport_id')->nullable()->constrained('transports')->onDelete('set null');
                $table->string('cost_type'); // fuel, ticket, parking, toll, other
                $table->decimal('amount', 10, 2);
                $table->string('currency')->default('PLN');
                $table->date('cost_date');
                $table->text('description')->nullable();
                $table->string('receipt_number')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
                $table->timestamps();

                $table->index('logistics_event_id');
                $table->index('vehicle_id');
                $table->index('transport_id');
                $table->index('cost_date');
                $table->index('cost_type');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transport_costs');
    }
};
