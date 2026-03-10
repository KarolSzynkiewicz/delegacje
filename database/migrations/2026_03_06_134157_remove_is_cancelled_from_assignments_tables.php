<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Removes is_cancelled column from project_assignments and vehicle_assignments tables.
     * Assignments are now physically deleted when departure is cancelled, so this flag is no longer needed.
     */
    public function up(): void
    {
        Schema::table('project_assignments', function (Blueprint $table) {
            if (Schema::hasColumn('project_assignments', 'is_cancelled')) {
                $table->dropColumn('is_cancelled');
            }
        });

        Schema::table('vehicle_assignments', function (Blueprint $table) {
            if (Schema::hasColumn('vehicle_assignments', 'is_cancelled')) {
                $table->dropColumn('is_cancelled');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_assignments', function (Blueprint $table) {
            if (!Schema::hasColumn('project_assignments', 'is_cancelled')) {
                $table->boolean('is_cancelled')->default(false)->after('end_date');
            }
        });

        Schema::table('vehicle_assignments', function (Blueprint $table) {
            if (!Schema::hasColumn('vehicle_assignments', 'is_cancelled')) {
                $table->boolean('is_cancelled')->default(false)->after('is_return_trip');
            }
        });
    }
};
