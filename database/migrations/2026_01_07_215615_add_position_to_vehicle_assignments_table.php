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
        if (Schema::hasTable('vehicle_assignments')) {
            Schema::table('vehicle_assignments', function (Blueprint $table) {
                if (!Schema::hasColumn('vehicle_assignments', 'position')) {
                    $table->string('position')->default('passenger')->after('vehicle_id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('vehicle_assignments')) {
            Schema::table('vehicle_assignments', function (Blueprint $table) {
                if (Schema::hasColumn('vehicle_assignments', 'position')) {
                    $table->dropColumn('position');
                }
            });
        }
    }
};
