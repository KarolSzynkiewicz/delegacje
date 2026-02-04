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
        // This migration is redundant - columns are already created in create_fixed_costs_table migration
        // Check if columns already exist before trying to add them
        if (Schema::hasColumn('fixed_costs', 'cost_date') && 
            Schema::hasColumn('fixed_costs', 'start_date') && 
            Schema::hasColumn('fixed_costs', 'end_date')) {
            // Columns already exist, skip this migration
            return;
        }
        
        // Only add columns if they don't exist (shouldn't happen if create_fixed_costs_table ran first)
        Schema::table('fixed_costs', function (Blueprint $table) {
            if (!Schema::hasColumn('fixed_costs', 'cost_date')) {
            $table->date('cost_date')->after('currency');
            }
            if (!Schema::hasColumn('fixed_costs', 'start_date')) {
            $table->date('start_date')->after('cost_date');
            }
            if (!Schema::hasColumn('fixed_costs', 'end_date')) {
            $table->date('end_date')->nullable()->after('start_date');
            }
        });
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
