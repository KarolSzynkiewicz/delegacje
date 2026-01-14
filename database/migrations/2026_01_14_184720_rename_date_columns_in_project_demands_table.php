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
        if (Schema::hasTable('project_demands')) {
            // Check if date_from column exists (new structure)
            if (Schema::hasColumn('project_demands', 'date_from')) {
                // Use raw SQL for better compatibility
                if (DB::getDriverName() === 'sqlite') {
                    // SQLite doesn't support RENAME COLUMN directly, need to recreate table
                    Schema::table('project_demands', function (Blueprint $table) {
                        $table->renameColumn('date_from', 'start_date');
                    });
                } else {
                    DB::statement('ALTER TABLE project_demands CHANGE date_from start_date DATE');
                }
            }
            
            if (Schema::hasColumn('project_demands', 'date_to')) {
                if (DB::getDriverName() === 'sqlite') {
                    Schema::table('project_demands', function (Blueprint $table) {
                        $table->renameColumn('date_to', 'end_date');
                    });
                } else {
                    DB::statement('ALTER TABLE project_demands CHANGE date_to end_date DATE NULL');
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('project_demands')) {
            // Check if start_date column exists
            if (Schema::hasColumn('project_demands', 'start_date')) {
                if (DB::getDriverName() === 'sqlite') {
                    Schema::table('project_demands', function (Blueprint $table) {
                        $table->renameColumn('start_date', 'date_from');
                    });
                } else {
                    DB::statement('ALTER TABLE project_demands CHANGE start_date date_from DATE');
                }
            }
            
            if (Schema::hasColumn('project_demands', 'end_date')) {
                if (DB::getDriverName() === 'sqlite') {
                    Schema::table('project_demands', function (Blueprint $table) {
                        $table->renameColumn('end_date', 'date_to');
                    });
                } else {
                    DB::statement('ALTER TABLE project_demands CHANGE end_date date_to DATE NULL');
                }
            }
        }
    }
};
