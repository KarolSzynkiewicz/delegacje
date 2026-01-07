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
        // Add is_base to locations
        if (Schema::hasTable('locations')) {
            Schema::table('locations', function (Blueprint $table) {
                if (!Schema::hasColumn('locations', 'is_base')) {
                    $table->boolean('is_base')->default(false)->after('description');
                }
            });
        }

        // Add current_location_id to vehicles
        if (Schema::hasTable('vehicles')) {
            Schema::table('vehicles', function (Blueprint $table) {
                if (!Schema::hasColumn('vehicles', 'current_location_id')) {
                    // Add after notes or at the end
                    $afterColumn = Schema::hasColumn('vehicles', 'notes') ? 'notes' : null;
                    if ($afterColumn) {
                        $table->foreignId('current_location_id')->nullable()->after($afterColumn)->constrained('locations')->onDelete('set null');
                    } else {
                        $table->foreignId('current_location_id')->nullable()->constrained('locations')->onDelete('set null');
                    }
                }
            });
        }

        // Add location_id to accommodations (needed for LocationTrackingService)
        if (Schema::hasTable('accommodations')) {
            Schema::table('accommodations', function (Blueprint $table) {
                if (!Schema::hasColumn('accommodations', 'location_id')) {
                    $table->foreignId('location_id')->nullable()->after('postal_code')->constrained('locations')->onDelete('set null');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('accommodations')) {
            Schema::table('accommodations', function (Blueprint $table) {
                if (Schema::hasColumn('accommodations', 'location_id')) {
                    $table->dropForeign(['location_id']);
                    $table->dropColumn('location_id');
                }
            });
        }

        if (Schema::hasTable('vehicles')) {
            Schema::table('vehicles', function (Blueprint $table) {
                if (Schema::hasColumn('vehicles', 'current_location_id')) {
                    $table->dropForeign(['current_location_id']);
                    $table->dropColumn('current_location_id');
                }
            });
        }

        if (Schema::hasTable('locations')) {
            Schema::table('locations', function (Blueprint $table) {
                if (Schema::hasColumn('locations', 'is_base')) {
                    $table->dropColumn('is_base');
                }
            });
        }
    }
};
