<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds 'workshop' to the technical_condition enum in vehicles table.
     * In MySQL/MariaDB, we need to modify the enum column with all values.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE vehicles MODIFY COLUMN technical_condition ENUM('excellent', 'good', 'fair', 'poor', 'workshop') DEFAULT 'good'");
    }

    /**
     * Reverse the migrations.
     * 
     * Removes 'workshop' from the technical_condition enum.
     * Note: This will fail if any vehicles have 'workshop' status.
     */
    public function down(): void
    {
        // First, update any vehicles with 'workshop' status to 'good'
        DB::table('vehicles')
            ->where('technical_condition', 'workshop')
            ->update(['technical_condition' => 'good']);
        
        // Then modify the enum to remove 'workshop'
        DB::statement("ALTER TABLE vehicles MODIFY COLUMN technical_condition ENUM('excellent', 'good', 'fair', 'poor') DEFAULT 'good'");
    }
};
