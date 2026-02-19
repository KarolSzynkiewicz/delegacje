<?php

namespace App\Http\Controllers;

use App\Services\RoutePermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;

class SystemActionsController extends Controller
{
    public function __construct(
        private RoutePermissionService $routePermissionService
    ) {}

    /**
     * Clear permissions and route cache.
     */
    public function clearPermissions(): RedirectResponse
    {
        try {
            Artisan::call('permission:cache-reset');
            Artisan::call('route:clear');
            $this->routePermissionService->clearCache();
            
            return redirect()->route('system-actions.index')
                ->with('success', 'Cache uprawnień i route zostały odświeżone!');
        } catch (\Exception $e) {
            return redirect()->route('system-actions.index')
                ->with('error', 'Błąd: ' . $e->getMessage());
        }
    }

    /**
     * Synchronize permissions from routes to database.
     * Creates new permissions and removes old ones that no longer exist in routes.
     */
    public function syncPermissions(): RedirectResponse
    {
        try {
            $routePerms = $this->routePermissionService->getAllPermissionsFromRoutes();
            
            // Get all permission names from routes
            $routePermissionNames = $routePerms->pluck('name')->toArray();
            
            // Get all existing permissions from database
            $existingPermissions = Permission::where('guard_name', 'web')->get();
            $existingPermissionNames = $existingPermissions->pluck('name')->toArray();
            
            // Find permissions to create and to delete
            $toCreate = array_diff($routePermissionNames, $existingPermissionNames);
            $toDelete = array_diff($existingPermissionNames, $routePermissionNames);
            
            $created = 0;
            $deleted = 0;
            
            // Create new permissions
            foreach ($toCreate as $permissionName) {
                Permission::create([
                    'name' => $permissionName,
                    'guard_name' => 'web',
                ]);
                $created++;
            }
            
            // Delete old permissions that no longer exist in routes
            if (!empty($toDelete)) {
                Permission::where('guard_name', 'web')
                    ->whereIn('name', $toDelete)
                    ->delete();
                $deleted = count($toDelete);
            }
            
            // Clear cache after sync
            Artisan::call('permission:cache-reset');
            $this->routePermissionService->clearCache();
            
            $message = "Synchronizacja zakończona!";
            if ($created > 0) {
                $message .= " Utworzono: {$created}.";
            }
            if ($deleted > 0) {
                $message .= " Usunięto: {$deleted}.";
            }
            if ($created === 0 && $deleted === 0) {
                $message .= " Wszystko aktualne.";
            }
            
            return redirect()->route('system-actions.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->route('system-actions.index')
                ->with('error', 'Błąd synchronizacji: ' . $e->getMessage());
        }
    }

    /**
     * Run database migrations.
     */
    public function runMigrations(): RedirectResponse
    {
        try {
            Artisan::call('migrate', [
                '--force' => true,
                '--no-interaction' => true,
            ]);
            
            $output = Artisan::output();
            
            return redirect()->route('system-actions.index')
                ->with('success', 'Migracje uruchomione pomyślnie! ' . ($output ?: 'Brak nowych migracji do uruchomienia.'));
        } catch (\Exception $e) {
            return redirect()->route('system-actions.index')
                ->with('error', 'Błąd podczas uruchamiania migracji: ' . $e->getMessage());
        }
    }

    /**
     * Toggle debug mode on.
     */
    public function debugOn(): RedirectResponse
    {
        try {
            Cache::put('force_debug_mode', true, now()->addHour());
            
            return redirect()->route('system-actions.index')
                ->with('success', '🐛 Debug mode WŁĄCZONY na 1 godzinę! Odśwież stronę aby zobaczyć szczegółowe błędy.');
        } catch (\Exception $e) {
            return redirect()->route('system-actions.index')
                ->with('error', 'Błąd: ' . $e->getMessage());
        }
    }

    /**
     * Toggle debug mode off.
     */
    public function debugOff(): RedirectResponse
    {
        try {
            Cache::forget('force_debug_mode');
            
            return redirect()->route('system-actions.index')
                ->with('success', '✅ Debug mode WYŁĄCZONY! Błędy znów ukryte.');
        } catch (\Exception $e) {
            return redirect()->route('system-actions.index')
                ->with('error', 'Błąd: ' . $e->getMessage());
        }
    }

    /**
     * Clear all cache.
     */
    public function clearCache(): RedirectResponse
    {
        try {
            Artisan::call('optimize:clear');
            Artisan::call('permission:cache-reset');
            Cache::flush();
            $this->routePermissionService->clearCache();
            
            return redirect()->route('system-actions.index')
                ->with('success', 'Wszystkie cache zostały wyczyszczone pomyślnie!');
        } catch (\Exception $e) {
            return redirect()->route('system-actions.index')
                ->with('error', 'Błąd: ' . $e->getMessage());
        }
    }
}
