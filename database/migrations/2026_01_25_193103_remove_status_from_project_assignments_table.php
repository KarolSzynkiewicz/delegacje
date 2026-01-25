<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Removes status column from project_assignments table.
     * Status is now computed based on dates via accessor.
     * Adds is_cancelled boolean column to preserve ability to mark assignments as cancelled.
     */
    public function up(): void
    {
        Schema::table('project_assignments', function (Blueprint $table) {
            // Add is_cancelled column before removing status
            if (!Schema::hasColumn('project_assignments', 'is_cancelled')) {
                $table->boolean('is_cancelled')->default(false)->after('end_date');
            }
        });

        // Migrate cancelled status to is_cancelled
        DB::table('project_assignments')
            ->where('status', 'cancelled')
            ->update(['is_cancelled' => true]);

        // Drop the status column - the index will be automatically dropped when column is removed
        Schema::table('project_assignments', function (Blueprint $table) {
            if (Schema::hasColumn('project_assignments', 'status')) {
                // MySQL will automatically drop the composite index when we drop the column
                $table->dropColumn('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_assignments', function (Blueprint $table) {
            // Re-add status column
            if (!Schema::hasColumn('project_assignments', 'status')) {
                $table->enum('status', ['pending', 'active', 'completed', 'cancelled'])
                    ->default('pending')
                    ->after('end_date');
            }
        });

        // Migrate is_cancelled back to status
        DB::table('project_assignments')
            ->where('is_cancelled', true)
            ->update(['status' => 'cancelled']);

        DB::table('project_assignments')
            ->where('is_cancelled', false)
            ->orWhereNull('is_cancelled')
            ->update(['status' => 'active']);

        Schema::table('project_assignments', function (Blueprint $table) {
            // Re-add index
            $table->index(['employee_id', 'status']);
            
            // Drop is_cancelled
            if (Schema::hasColumn('project_assignments', 'is_cancelled')) {
                $table->dropColumn('is_cancelled');
            }
        });
    }
};
