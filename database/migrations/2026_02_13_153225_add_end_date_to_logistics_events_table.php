<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds end_date to logistics_events to support multi-day trips.
     * This is needed to properly block vehicles during departure/return journeys.
     */
    public function up(): void
    {
        Schema::table('logistics_events', function (Blueprint $table) {
            $table->datetime('end_date')->nullable()->after('event_date');
            $table->index('end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logistics_events', function (Blueprint $table) {
            $table->dropIndex(['end_date']);
            $table->dropColumn('end_date');
        });
    }
};
