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
        Schema::table('accommodations', function (Blueprint $table) {
            $table->enum('type', ['wynajmowany', 'własny'])->default('własny')->after('location_id');
            $table->date('lease_start_date')->nullable()->after('type');
            $table->date('lease_end_date')->nullable()->after('lease_start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accommodations', function (Blueprint $table) {
            $table->dropColumn(['type', 'lease_start_date', 'lease_end_date']);
        });
    }
};
