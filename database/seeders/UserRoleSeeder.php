<?php

namespace Database\Seeders;

use App\Services\RoutePermissionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Spatie: uprawnienia z tras + rola administrator (dla testów i ręcznego seedowania).
 * Odpowiada logice z {@see \App\Services\SystemBootstrapService::bootstrap()}.
 */
class UserRoleSeeder extends Seeder
{
    public function run(): void
    {
        $routePermissionService = app(RoutePermissionService::class);
        $routePermissionService->clearCache();

        foreach ($routePermissionService->getAllPermissionsFromRoutes() as $perm) {
            Permission::firstOrCreate(
                [
                    'name' => $perm['name'],
                    'guard_name' => 'web',
                ],
                [
                    'type' => $perm['type'] ?? null,
                ]
            );
        }

        $adminRole = Role::firstOrCreate(
            ['name' => 'administrator', 'guard_name' => 'web'],
            ['name' => 'administrator', 'guard_name' => 'web']
        );

        $adminRole->syncPermissions(Permission::where('guard_name', 'web')->get());

        try {
            Artisan::call('permission:cache-reset');
        } catch (\Throwable) {
            // środowiska testowe / brak cache
        }
    }
}
