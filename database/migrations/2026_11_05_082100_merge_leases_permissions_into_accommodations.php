<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * Okresy najmu to część mieszkania — nie osobny zasób w uprawnieniach.
 * leases.create/update/delete → accommodations.create/update/delete.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('role_has_permissions')) {
            return;
        }

        $leasePermissions = DB::table('permissions')
            ->where('guard_name', 'web')
            ->whereIn('name', ['leases.create', 'leases.update', 'leases.delete', 'leases.view'])
            ->get(['id', 'name']);

        if ($leasePermissions->isEmpty()) {
            $this->forgetPermissionCache();

            return;
        }

        foreach ($leasePermissions as $leasePermission) {
            $action = substr($leasePermission->name, strlen('leases.'));
            $accommodationName = 'accommodations.'.$action;

            $accommodationPermissionId = DB::table('permissions')
                ->where('guard_name', 'web')
                ->where('name', $accommodationName)
                ->value('id');

            if (! $accommodationPermissionId) {
                $accommodationPermissionId = DB::table('permissions')->insertGetId([
                    'name' => $accommodationName,
                    'guard_name' => 'web',
                    'type' => 'resource',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $roleIds = DB::table('role_has_permissions')
                ->where('permission_id', $leasePermission->id)
                ->pluck('role_id');

            foreach ($roleIds as $roleId) {
                $alreadyGranted = DB::table('role_has_permissions')
                    ->where('role_id', $roleId)
                    ->where('permission_id', $accommodationPermissionId)
                    ->exists();

                if (! $alreadyGranted) {
                    DB::table('role_has_permissions')->insert([
                        'role_id' => $roleId,
                        'permission_id' => $accommodationPermissionId,
                    ]);
                }
            }

            DB::table('role_has_permissions')->where('permission_id', $leasePermission->id)->delete();

            if (Schema::hasTable('model_has_permissions')) {
                DB::table('model_has_permissions')->where('permission_id', $leasePermission->id)->delete();
            }

            DB::table('permissions')->where('id', $leasePermission->id)->delete();
        }

        $this->forgetPermissionCache();
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('role_has_permissions')) {
            return;
        }

        foreach (['create', 'update', 'delete'] as $action) {
            $exists = DB::table('permissions')
                ->where('guard_name', 'web')
                ->where('name', 'leases.'.$action)
                ->exists();

            if ($exists) {
                continue;
            }

            $leasePermissionId = DB::table('permissions')->insertGetId([
                'name' => 'leases.'.$action,
                'guard_name' => 'web',
                'type' => 'resource',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $accommodationPermissionId = DB::table('permissions')
                ->where('guard_name', 'web')
                ->where('name', 'accommodations.'.$action)
                ->value('id');

            if (! $accommodationPermissionId) {
                continue;
            }

            $roleIds = DB::table('role_has_permissions')
                ->where('permission_id', $accommodationPermissionId)
                ->pluck('role_id');

            foreach ($roleIds as $roleId) {
                DB::table('role_has_permissions')->insert([
                    'role_id' => $roleId,
                    'permission_id' => $leasePermissionId,
                ]);
            }
        }

        $this->forgetPermissionCache();
    }

    private function forgetPermissionCache(): void
    {
        try {
            app()[PermissionRegistrar::class]->forgetCachedPermissions();
        } catch (\Throwable) {
            // Cache may be unavailable during early migrate.
        }

        try {
            app(\App\Services\RoutePermissionService::class)->clearCache();
        } catch (\Throwable) {
            // Routes/cache may be unavailable during early migrate.
        }
    }
};
