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
        // Check if columns already exist (they are created in create_fixed_costs_table migration)
        $hasCostDate = Schema::hasColumn('fixed_costs', 'cost_date');
        $hasStartDate = Schema::hasColumn('fixed_costs', 'start_date');
        $hasEndDate = Schema::hasColumn('fixed_costs', 'end_date');
        
        // Only add columns if they don't exist
        if (!$hasCostDate || !$hasStartDate || !$hasEndDate) {
            Schema::table('fixed_costs', function (Blueprint $table) use ($hasCostDate, $hasStartDate, $hasEndDate) {
                if (!$hasCostDate) {
                    $table->date('cost_date')->after('currency');
                }
                if (!$hasStartDate) {
                    $table->date('start_date')->after('cost_date');
                }
                if (!$hasEndDate) {
                    $table->date('end_date')->nullable()->after('start_date');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fixed_costs', function (Blueprint $table) {
            $table->dropColumn(['cost_date', 'start_date', 'end_date']);
        });
    }
};
