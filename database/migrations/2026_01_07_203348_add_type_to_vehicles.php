<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\VehicleType;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('vehicles')) {
            Schema::table('vehicles', function (Blueprint $table) {
                if (!Schema::hasColumn('vehicles', 'type')) {
                    $table->string('type')->default(VehicleType::COMPANY_VEHICLE->value)->after('registration_number');
                }
            });

            // Update existing vehicles to have company_vehicle type
            \DB::table('vehicles')
                ->whereNull('type')
                ->update(['type' => VehicleType::COMPANY_VEHICLE->value]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('vehicles')) {
            Schema::table('vehicles', function (Blueprint $table) {
                if (Schema::hasColumn('vehicles', 'type')) {
                    $table->dropColumn('type');
                }
            });
        }
    }
};
