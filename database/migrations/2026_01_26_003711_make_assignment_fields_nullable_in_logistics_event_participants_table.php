<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /**
     * Run the migrations.
     * 
     * Makes assignment_type and assignment_id nullable to support departures
     * where participants don't have assignments yet (they're going TO projects).
     */
    public function up(): void
    {
        Schema::table('logistics_event_participants', function (Blueprint $table) {
            $table->string('assignment_type')->nullable()->change();
            $table->unsignedBigInteger('assignment_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logistics_event_participants', function (Blueprint $table) {
            // Note: This will fail if there are NULL values in the database
            // In that case, you'd need to clean up NULL values first
            $table->string('assignment_type')->nullable(false)->change();
            $table->unsignedBigInteger('assignment_id')->nullable(false)->change();
        });
    }
};
