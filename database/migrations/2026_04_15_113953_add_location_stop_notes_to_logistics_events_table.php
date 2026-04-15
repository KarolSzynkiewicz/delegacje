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
        Schema::table('logistics_events', function (Blueprint $table) {
            if (! Schema::hasColumn('logistics_events', 'location_stop_notes')) {
                $table->json('location_stop_notes')->nullable()->after('route_waypoints');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logistics_events', function (Blueprint $table) {
            if (Schema::hasColumn('logistics_events', 'location_stop_notes')) {
                $table->dropColumn('location_stop_notes');
            }
        });
    }
};
