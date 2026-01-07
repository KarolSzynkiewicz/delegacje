<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\TransportMode;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('transports')) {
            Schema::create('transports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('logistics_event_id')->constrained('logistics_events')->onDelete('cascade');
            $table->string('mode'); // TransportMode enum
            $table->string('carrier')->nullable(); // Przewoźnik (np. "LOT", "PKP")
            $table->string('ticket_number')->nullable();
            $table->datetime('departure_datetime');
            $table->datetime('arrival_datetime');
            $table->string('departure_location')->nullable(); // Lotnisko, dworzec (np. "WAW", "Warszawa Centralna")
            $table->string('arrival_location')->nullable();
            $table->decimal('cost', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('logistics_event_id');
            $table->index('departure_datetime');
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transports');
    }
};
