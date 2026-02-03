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
        // This table is replaced by Spatie's role_has_permissions
        // Only create if it doesn't exist and Spatie table doesn't exist
        if (!Schema::hasTable('role_permissions') && !Schema::hasTable('role_has_permissions')) {
            // Check if user_roles table exists (created by Spatie Permission)
            $userRolesExists = Schema::hasTable('user_roles');
            $permissionsExists = Schema::hasTable('permissions');
            
            Schema::create('role_permissions', function (Blueprint $table) use ($userRolesExists, $permissionsExists) {
                $table->id();
                // Use unsignedBigInteger to avoid foreign key constraint error
                // if user_roles table doesn't exist yet (will be created by Spatie migration later)
                $table->unsignedBigInteger('user_role_id');
                $table->unsignedBigInteger('permission_id');
                $table->timestamps();
                
                $table->unique(['user_role_id', 'permission_id']);
            });
            
            // Add foreign key constraints only if tables exist
            if ($userRolesExists) {
                Schema::table('role_permissions', function (Blueprint $table) {
                    $table->foreign('user_role_id')
                        ->references('id')
                        ->on('user_roles')
                        ->onDelete('cascade');
                });
            }
            
            if ($permissionsExists) {
                Schema::table('role_permissions', function (Blueprint $table) {
                    $table->foreign('permission_id')
                        ->references('id')
                        ->on('permissions')
                        ->onDelete('cascade');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};
