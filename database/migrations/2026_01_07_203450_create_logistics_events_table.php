<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\LogisticsEventType;
use App\Enums\LogisticsEventStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('logistics_events')) {
            Schema::create('logistics_events', function (Blueprint $table) {
                $table->id();
                $table->string('type'); // LogisticsEventType enum
                $table->datetime('event_date');
                $table->boolean('has_transport')->default(false);
                $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->onDelete('set null');
                $table->unsignedBigInteger('transport_id')->nullable(); // Foreign key added in separate migration
                $table->foreignId('from_location_id')->constrained('locations')->onDelete('restrict');
                $table->foreignId('to_location_id')->constrained('locations')->onDelete('restrict');
                $table->string('status')->default(LogisticsEventStatus::PLANNED->value);
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
                $table->timestamps();

                // Indexes
                $table->index('event_date');
                $table->index('type');
                $table->index('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logistics_events');
    }
};
