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
            $table->decimal('route_distance', 10, 2)->nullable()->after('to_location_id')->comment('Route distance in kilometers');
            $table->integer('route_duration')->nullable()->after('route_distance')->comment('Route duration in seconds');
            $table->json('route_geometry')->nullable()->after('route_duration')->comment('Encoded polyline geometry');
            $table->json('route_waypoints')->nullable()->after('route_geometry')->comment('Array of waypoint location IDs');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logistics_events', function (Blueprint $table) {
            $table->dropColumn(['route_distance', 'route_duration', 'route_geometry', 'route_waypoints']);
        });
    }
};
