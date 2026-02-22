<?php

namespace App\Services;

use App\Models\User;
use App\Services\MenuService;
use App\Services\RoutePermissionService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SystemBootstrapService
{
    public function __construct(
        private RoutePermissionService $routePermissionService,
        private MenuService $menuService
    ) {}

    /**
     * Check if system needs initialization and bootstrap if necessary.
     * This is a state check, not a user action.
     * 
     * Returns true if bootstrap was performed, false if already initialized.
     */
    public function ensureInitialized(): bool
    {
        // Skip during migrations - cache may not be available and DB is being reset
        if (app()->runningInConsole()) {
            $command = $_SERVER['argv'][1] ?? null;
            if (in_array($command, ['migrate', 'migrate:fresh', 'migrate:refresh', 'migrate:reset', 'migrate:rollback'])) {
                return false; // Skip during migrations
            }
        }

        // Check if DB tables exist (migrations run)
        if (!Schema::hasTable('permissions')) {
            return false; // DB not ready yet
        }

        // Try to use cache, but gracefully handle cache failures (e.g., during migrations)
        $cacheKey = 'system:initialized';
        $isCachedAsInitialized = false;
        try {
            $isCachedAsInitialized = Cache::has($cacheKey);
        } catch (\Exception $e) {
            // Cache not available (e.g., permissions issue) - continue without cache
        }

        // Check if system is initialized: permissions exist
        $hasPermissions = DB::table('permissions')
            ->where('guard_name', 'web')
            ->exists();

        if ($hasPermissions) {
            // System is already initialized - nothing to do
            // For existing environments: system is ready, no action needed
            // Try to cache the result (but don't fail if cache unavailable)
            if (!$isCachedAsInitialized) {
                try {
                    Cache::put($cacheKey, true, now()->addDay());
                } catch (\Exception $e) {
                    // Cache not available - that's ok, we'll check DB next time
                }
            }
            return false;
        }

        // System is uninitialized - bootstrap it
        $this->bootstrap();
        
        // Mark as initialized (but don't fail if cache unavailable)
        try {
            Cache::put($cacheKey, true, now()->addDay());
        } catch (\Exception $e) {
            // Cache not available - that's ok
        }
        
        return true;
    }

    /**
     * Bootstrap the system: create permissions, roles, assign admin to first user.
     * This is deterministic state transition: uninitialized → initialized.
     */
    private function bootstrap(): void
    {
        // Step 1: Create permissions from routes
        $routePerms = $this->routePermissionService->getAllPermissionsFromRoutes();
        
        foreach ($routePerms as $perm) {
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

        // Step 2: Ensure administrator role exists
        $adminRole = Role::firstOrCreate(
            ['name' => 'administrator', 'guard_name' => 'web'],
            ['name' => 'administrator', 'guard_name' => 'web']
        );

        // Step 3: Give admin role all permissions
        $allPermissions = Permission::where('guard_name', 'web')->get();
        $adminRole->syncPermissions($allPermissions);

        // Step 4: Assign admin role to first user (if exists and doesn't have role)
        $this->ensureFirstUserIsAdmin();

        // Step 5: Clear caches (gracefully handle failures)
        try {
            Artisan::call('permission:cache-reset');
        } catch (\Exception $e) {
            // Cache may not be available during migrations - that's ok
        }
        
        try {
            $this->routePermissionService->clearCache();
        } catch (\Exception $e) {
            // Cache may not be available - that's ok
        }
    }

    /**
     * Ensure first user has administrator role.
     * Called both during bootstrap and after system is initialized.
     */
    private function ensureFirstUserIsAdmin(): void
    {
        $firstUser = User::orderBy('id')->first();

        if ($firstUser && !$firstUser->hasRole('administrator')) {
            $adminRole = Role::where('name', 'administrator')->where('guard_name', 'web')->first();
            if ($adminRole) {
                $firstUser->assignRole('administrator');
                
                // Clear caches so menu and permissions update immediately
                try {
                    Artisan::call('permission:cache-reset');
                } catch (\Exception $e) {
                    // Cache may not be available - that's ok
                }
                
                try {
                    $this->menuService->clearMenuCacheForUser($firstUser->id);
                } catch (\Exception $e) {
                    // Cache may not be available - that's ok
                }
            }
        }
    }
}
