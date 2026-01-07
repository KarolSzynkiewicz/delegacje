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
        // Remove old columns from permissions table if they exist (SQLite compatibility)
        if (Schema::hasTable('permissions')) {
            if (Schema::hasColumn('permissions', 'slug')) {
                if (DB::getDriverName() === 'sqlite') {
                    // SQLite doesn't support DROP COLUMN directly, need to recreate table
                    DB::statement('CREATE TABLE permissions_new AS SELECT id, name, guard_name, created_at, updated_at FROM permissions');
                    DB::statement('DROP TABLE permissions');
                    DB::statement('ALTER TABLE permissions_new RENAME TO permissions');
                } else {
                    Schema::table('permissions', function (Blueprint $table) {
                        $table->dropColumn('slug');
                    });
                }
            }
            
            if (Schema::hasColumn('permissions', 'model')) {
                if (DB::getDriverName() === 'sqlite') {
                    // Already handled above if slug existed
                } else {
                    Schema::table('permissions', function (Blueprint $table) {
                        $table->dropColumn('model');
                    });
                }
            }
            
            if (Schema::hasColumn('permissions', 'action')) {
                if (DB::getDriverName() === 'sqlite') {
                    // Already handled above if slug existed
                } else {
                    Schema::table('permissions', function (Blueprint $table) {
                        $table->dropColumn('action');
                    });
                }
            }
            
            if (Schema::hasColumn('permissions', 'description')) {
                if (DB::getDriverName() === 'sqlite') {
                    // Already handled above if slug existed
                } else {
                    Schema::table('permissions', function (Blueprint $table) {
                        $table->dropColumn('description');
                    });
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Not reversible - old columns were removed
    }
};
