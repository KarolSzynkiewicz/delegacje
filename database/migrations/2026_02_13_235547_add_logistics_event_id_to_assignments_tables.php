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
        // Add logistics_event_id to project_assignments
        Schema::table('project_assignments', function (Blueprint $table) {
            $table->foreignId('logistics_event_id')
                ->nullable()
                ->after('role_id')
                ->constrained('logistics_events')
                ->onDelete('set null');
            $table->index('logistics_event_id');
        });

        // Add logistics_event_id to vehicle_assignments
        Schema::table('vehicle_assignments', function (Blueprint $table) {
            $table->foreignId('logistics_event_id')
                ->nullable()
                ->after('vehicle_id')
                ->constrained('logistics_events')
                ->onDelete('set null');
            $table->index('logistics_event_id');
        });

        // Add logistics_event_id to accommodation_assignments
        Schema::table('accommodation_assignments', function (Blueprint $table) {
            $table->foreignId('logistics_event_id')
                ->nullable()
                ->after('accommodation_id')
                ->constrained('logistics_events')
                ->onDelete('set null');
            $table->index('logistics_event_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_assignments', function (Blueprint $table) {
            $table->dropForeign(['logistics_event_id']);
            $table->dropIndex(['logistics_event_id']);
            $table->dropColumn('logistics_event_id');
        });

        Schema::table('vehicle_assignments', function (Blueprint $table) {
            $table->dropForeign(['logistics_event_id']);
            $table->dropIndex(['logistics_event_id']);
            $table->dropColumn('logistics_event_id');
        });

        Schema::table('accommodation_assignments', function (Blueprint $table) {
            $table->dropForeign(['logistics_event_id']);
            $table->dropIndex(['logistics_event_id']);
            $table->dropColumn('logistics_event_id');
        });
    }
};
