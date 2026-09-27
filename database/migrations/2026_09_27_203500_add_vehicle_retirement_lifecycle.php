<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->timestamp('retired_at')->nullable()->after('last_departure_id');
            $table->string('retirement_reason', 30)->nullable()->after('retired_at');
            $table->text('retirement_note')->nullable()->after('retirement_reason');
            $table->index('retired_at');
        });

        Schema::create('vehicle_lifecycle_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->timestamp('occurred_at');
            $table->string('reason', 30)->nullable();
            $table->text('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['vehicle_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_lifecycle_events');

        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropIndex(['retired_at']);
            $table->dropColumn(['retired_at', 'retirement_reason', 'retirement_note']);
        });
    }
};
