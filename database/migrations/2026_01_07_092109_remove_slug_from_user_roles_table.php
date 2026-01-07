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
        // This migration is only needed if old user_roles table exists with slug column
        // Spatie's create_permission_tables migration should create user_roles without slug
        // So we only drop slug if it exists and guard_name doesn't exist (old structure)
        if (Schema::hasTable('user_roles')) {
            if (Schema::hasColumn('user_roles', 'slug') && !Schema::hasColumn('user_roles', 'guard_name')) {
                // Old structure - drop the table and let Spatie recreate it
                Schema::dropIfExists('user_roles');
            } elseif (Schema::hasColumn('user_roles', 'slug') && Schema::hasColumn('user_roles', 'guard_name')) {
                // Mixed structure - remove slug
                if (DB::getDriverName() !== 'sqlite') {
                    Schema::table('user_roles', function (Blueprint $table) {
                        $table->dropColumn('slug');
                    });
                }
                // For SQLite, if both columns exist, we'll leave it as is (slug will be ignored)
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
