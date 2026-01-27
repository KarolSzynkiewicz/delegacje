<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vehicle_assignments', function (Blueprint $table) {
            if (!Schema::hasColumn('vehicle_assignments', 'is_cancelled')) {
                $table->boolean('is_cancelled')->default(false)->after('is_return_trip');
            }
        });

        // Migrate cancelled status to is_cancelled (if status column exists)
        if (Schema::hasColumn('vehicle_assignments', 'status')) {
            DB::table('vehicle_assignments')
                ->where('status', 'cancelled')
                ->update(['is_cancelled' => true]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicle_assignments', function (Blueprint $table) {
            if (Schema::hasColumn('vehicle_assignments', 'is_cancelled')) {
                $table->dropColumn('is_cancelled');
            }
        });
    }
};
