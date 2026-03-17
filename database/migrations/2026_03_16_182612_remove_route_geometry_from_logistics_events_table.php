<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Removes route_geometry column as we don't need to store the encoded polyline.
     * We only store: route_distance, route_duration, and route_waypoints (accommodation IDs).
     */
    public function up(): void
    {
        Schema::table('logistics_events', function (Blueprint $table) {
            $table->dropColumn('route_geometry');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logistics_events', function (Blueprint $table) {
            $table->text('route_geometry')->nullable()->after('route_duration')->comment('Encoded polyline geometry');
        });
    }
};
