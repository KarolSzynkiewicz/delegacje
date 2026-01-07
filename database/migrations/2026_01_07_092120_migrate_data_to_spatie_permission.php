<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add guard_name column to existing permissions table if it doesn't exist
        if (Schema::hasTable('permissions') && !Schema::hasColumn('permissions', 'guard_name')) {
            Schema::table('permissions', function (Blueprint $table) {
                $table->string('guard_name')->default('web')->after('slug');
            });
            
            // Update all existing permissions with guard_name
            DB::table('permissions')->update(['guard_name' => 'web']);
            
            // Rename slug to name for Spatie compatibility
            if (Schema::hasColumn('permissions', 'slug') && !Schema::hasColumn('permissions', 'name')) {
                DB::statement('ALTER TABLE `permissions` CHANGE `slug` `name` VARCHAR(255)');
            }
            
            // Add unique constraint on name + guard_name if not exists
            try {
                DB::statement('ALTER TABLE `permissions` ADD UNIQUE `permissions_name_guard_name_unique` (`name`, `guard_name`)');
            } catch (\Exception $e) {
                // Constraint might already exist
            }
        }

        // Add guard_name column to existing user_roles table if it doesn't exist
        if (Schema::hasTable('user_roles') && !Schema::hasColumn('user_roles', 'guard_name')) {
            Schema::table('user_roles', function (Blueprint $table) {
                $table->string('guard_name')->default('web')->after('slug');
            });
            
            // Update all existing roles with guard_name
            DB::table('user_roles')->update(['guard_name' => 'web']);
            
            // Rename slug to name for Spatie compatibility
            if (Schema::hasColumn('user_roles', 'slug') && !Schema::hasColumn('user_roles', 'name')) {
                DB::statement('ALTER TABLE `user_roles` CHANGE `slug` `name` VARCHAR(255)');
            }
            
            // Add unique constraint on name + guard_name if not exists
            try {
                DB::statement('ALTER TABLE `user_roles` ADD UNIQUE `user_roles_name_guard_name_unique` (`name`, `guard_name`)');
            } catch (\Exception $e) {
                // Constraint might already exist
            }
        }

        // Migrate role permissions (after tables are updated and Spatie tables exist)
        if (Schema::hasTable('user_roles') && Schema::hasTable('role_permissions') && Schema::hasTable('role_has_permissions')) {
            $oldRoles = DB::table('user_roles')->get();
            
            foreach ($oldRoles as $oldRole) {
                $roleName = $oldRole->name ?? $oldRole->slug;
                $spatieRole = DB::table('user_roles')
                    ->where('name', $roleName)
                    ->where('guard_name', 'web')
                    ->first();
                
                if ($spatieRole) {
                    // Get old permissions for this role
                    $oldPermissionSlugs = DB::table('role_permissions')
                        ->where('user_role_id', $oldRole->id)
                        ->join('permissions', 'role_permissions.permission_id', '=', 'permissions.id')
                        ->pluck('permissions.name');
                    
                    // Get Spatie permission IDs
                    $spatiePermissionIds = DB::table('permissions')
                        ->whereIn('name', $oldPermissionSlugs)
                        ->where('guard_name', 'web')
                        ->pluck('id');
                    
                    // Insert into role_has_permissions
                    foreach ($spatiePermissionIds as $permissionId) {
                        DB::table('role_has_permissions')->insertOrIgnore([
                            'permission_id' => $permissionId,
                            'role_id' => $spatieRole->id,
                        ]);
                    }
                }
            }
        }

        // Migrate user roles assignments (after model_has_roles table exists)
        if (Schema::hasTable('user_user_roles') && Schema::hasTable('model_has_roles')) {
            $userRoles = DB::table('user_user_roles')->get();
            
            foreach ($userRoles as $userRole) {
                $oldRole = DB::table('user_roles')->find($userRole->user_role_id);
                
                if ($oldRole) {
                    $roleName = $oldRole->name ?? $oldRole->slug;
                    $spatieRole = DB::table('user_roles')
                        ->where('name', $roleName)
                        ->where('guard_name', 'web')
                        ->first();
                    
                    if ($spatieRole) {
                        // Insert into model_has_roles
                        DB::table('model_has_roles')->insertOrIgnore([
                            'role_id' => $spatieRole->id,
                            'model_type' => 'App\Models\User',
                            'model_id' => $userRole->user_id,
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration migrates data, so rollback would require restoring old tables
        // which is complex. We'll leave this empty or implement if needed.
    }
};
