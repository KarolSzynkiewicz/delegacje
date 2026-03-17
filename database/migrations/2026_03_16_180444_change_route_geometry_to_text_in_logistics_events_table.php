<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Changes route_geometry from JSON to TEXT because it stores
     * an encoded polyline string, not JSON data.
     */
    public function up(): void
    {
        // Use raw SQL to change column type from JSON to TEXT
        // MySQL doesn't support direct type change from JSON to TEXT via Schema builder
        \DB::statement('ALTER TABLE logistics_events MODIFY COLUMN route_geometry TEXT NULL COMMENT "Encoded polyline geometry"');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to JSON type
        \DB::statement('ALTER TABLE logistics_events MODIFY COLUMN route_geometry JSON NULL COMMENT "Encoded polyline geometry"');
    }
};
