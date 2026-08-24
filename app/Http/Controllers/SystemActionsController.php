<?php

namespace App\Http\Controllers;

use App\Enums\LocationPurposeType;
use App\Models\Accommodation;
use App\Models\Location;
use App\Models\LogisticsEvent;
use App\Models\Project;
use App\Models\Vehicle;
use App\Models\VehicleRepair;
use App\Models\Warehouse;
use App\Services\RoutePermissionService;
use App\Services\WorkItemSync;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
                ->with('error', 'Błąd: '.$e->getMessage());
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
            if (! empty($toDelete)) {
                Permission::where('guard_name', 'web')
                    ->whereIn('name', $toDelete)
                    ->delete();
                $deleted = count($toDelete);
            }

            // Clear cache after sync
            Artisan::call('permission:cache-reset');
            $this->routePermissionService->clearCache();

            $message = 'Synchronizacja zakończona!';
            if ($created > 0) {
                $message .= " Utworzono: {$created}.";
            }
            if ($deleted > 0) {
                $message .= " Usunięto: {$deleted}.";
            }
            if ($created === 0 && $deleted === 0) {
                $message .= ' Wszystko aktualne.';
            }

            return redirect()->route('system-actions.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->route('system-actions.index')
                ->with('error', 'Błąd synchronizacji: '.$e->getMessage());
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
                ->with('success', 'Migracje uruchomione pomyślnie! '.($output ?: 'Brak nowych migracji do uruchomienia.'));
        } catch (\Exception $e) {
            return redirect()->route('system-actions.index')
                ->with('error', 'Błąd podczas uruchamiania migracji: '.$e->getMessage());
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
                ->with('error', 'Błąd: '.$e->getMessage());
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
                ->with('error', 'Błąd: '.$e->getMessage());
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
                ->with('error', 'Błąd: '.$e->getMessage());
        }
    }

    /**
     * Jednorazowy backfill indeksu /tasks2. Stare project_tasks sprzed tabeli
     * work_items nie mają wiersza w backlogu, dopóki ktoś ich nie edytuje —
     * stąd grid na produkcji pokazuje tylko dzisiejsze zadania.
     */
    public function syncWorkItems(WorkItemSync $sync): RedirectResponse
    {
        try {
            set_time_limit(0);

            $count = $sync->backfill();

            return redirect()->route('system-actions.index')
                ->with('success', "Backfill «Utworzono przez» zakończony. Zapisano {$count} wierszy backlogu. Odśwież /tasks2.");
        } catch (\Exception $e) {
            return redirect()->route('system-actions.index')
                ->with('error', 'Błąd backfillu zadań: '.$e->getMessage());
        }
    }

    /**
     * Fix location names: where location.name == location.address, replace with the linked accommodation name.
     */
    public function fixLocationNames(): RedirectResponse
    {
        try {
            $fixed = 0;

            Accommodation::whereNotNull('location_id')
                ->with('location')
                ->get()
                ->each(function (Accommodation $acc) use (&$fixed) {
                    $loc = $acc->location;
                    if (! $loc) {
                        return;
                    }

                    // Jeśli nazwa lokalizacji wygląda jak adres (= pole address lub city lokalizacji)
                    $looksLikeAddress = $loc->address && trim($loc->name) === trim($loc->address);

                    if ($looksLikeAddress) {
                        $loc->update(['name' => $acc->name]);
                        $fixed++;
                    }
                });

            return redirect()->route('system-actions.index')
                ->with('success', "Naprawiono nazw lokalizacji: {$fixed}.");
        } catch (\Exception $e) {
            return redirect()->route('system-actions.index')
                ->with('error', 'Błąd: '.$e->getMessage());
        }
    }

    /**
     * Sync location purposes based on actual data:
     * - accommodations → QUARTER
     * - projects        → PROJECT
     */
    public function syncLocationPurposes(): RedirectResponse
    {
        try {
            $stats = ['quarter' => 0, 'project' => 0, 'workshop' => 0, 'warehouse' => 0, 'other' => 0];

            // Lokalizacje z mieszkaniami → Kwatera
            Accommodation::whereNotNull('location_id')
                ->with('location')
                ->get()
                ->each(function (Accommodation $acc) use (&$stats) {
                    if ($acc->location) {
                        $acc->location->addPurposes([LocationPurposeType::QUARTER]);
                        $stats['quarter']++;
                    }
                });

            // Lokalizacje z projektami → Projekt
            Project::whereNotNull('location_id')
                ->with('location')
                ->get()
                ->each(function (Project $project) use (&$stats) {
                    if ($project->location) {
                        $project->location->addPurposes([LocationPurposeType::PROJECT]);
                        $stats['project']++;
                    }
                });

            // Lokalizacje z naprawami pojazdów → Warsztat
            VehicleRepair::whereNotNull('location_id')
                ->with('location')
                ->get()
                ->each(function (VehicleRepair $repair) use (&$stats) {
                    if ($repair->location) {
                        $repair->location->addPurposes([LocationPurposeType::WORKSHOP]);
                        $stats['workshop']++;
                    }
                });

            // Lokalizacje z magazynami → Magazyn
            Warehouse::whereNotNull('location_id')
                ->with('location')
                ->get()
                ->each(function (Warehouse $warehouse) use (&$stats) {
                    if ($warehouse->location) {
                        $warehouse->location->addPurposes([LocationPurposeType::WAREHOUSE]);
                        $stats['warehouse']++;
                    }
                });

            // Lokalizacje widoczne w logistyce / pojazdach, które nie mają żadnego typu → Inne
            $locationIdsFromLogistics = LogisticsEvent::query()
                ->selectRaw('from_location_id as location_id')
                ->union(LogisticsEvent::query()->selectRaw('to_location_id as location_id'))
                ->pluck('location_id')
                ->filter()
                ->unique()
                ->values();

            $locationIdsFromVehicles = Vehicle::query()
                ->whereNotNull('current_location_id')
                ->pluck('current_location_id')
                ->filter()
                ->unique()
                ->values();

            $fallbackLocationIds = $locationIdsFromLogistics
                ->merge($locationIdsFromVehicles)
                ->unique()
                ->values();

            if ($fallbackLocationIds->isNotEmpty()) {
                Location::whereIn('id', $fallbackLocationIds)
                    ->withCount('purposes')
                    ->get()
                    ->each(function (Location $loc) use (&$stats) {
                        if (($loc->purposes_count ?? 0) === 0) {
                            $loc->addPurposes([LocationPurposeType::OTHER]);
                            $stats['other']++;
                        }
                    });
            }

            // Ostateczny fallback: wszystkie lokalizacje bez żadnego typu → Inne
            Location::withCount('purposes')
                ->get()
                ->each(function (Location $loc) use (&$stats) {
                    if (($loc->purposes_count ?? 0) === 0) {
                        $loc->addPurposes([LocationPurposeType::OTHER]);
                        $stats['other']++;
                    }
                });

            $msg = 'Typy lokalizacji zaktualizowane — '
                ."kwatery: {$stats['quarter']}, "
                ."projekty: {$stats['project']}, "
                ."warsztaty: {$stats['workshop']}, "
                ."magazyny: {$stats['warehouse']}, "
                ."inne: {$stats['other']}.";

            return redirect()->route('system-actions.index')
                ->with('success', $msg);
        } catch (\Exception $e) {
            return redirect()->route('system-actions.index')
                ->with('error', 'Błąd: '.$e->getMessage());
        }
    }

    /**
     * Export a full JSON backup of all database tables (data only, no binary files).
     */
    public function backupDatabase(): StreamedResponse
    {
        $dbName = DB::getDatabaseName();
        $tablesRaw = DB::select('SHOW TABLES');
        $key = 'Tables_in_'.$dbName;

        $backup = [
            'generated_at' => now()->toIso8601String(),
            'app_env' => config('app.env'),
            'database' => $dbName,
            'tables' => [],
        ];

        foreach ($tablesRaw as $tableRow) {
            $tableName = $tableRow->$key;
            $backup['tables'][$tableName] = DB::table($tableName)->get()->toArray();
        }

        $filename = 'backup_'.$dbName.'_'.now()->format('Y-m-d_His').'.json';
        $json = json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return response()->streamDownload(function () use ($json) {
            echo $json;
        }, $filename, [
            'Content-Type' => 'application/json; charset=utf-8',
        ]);
    }

    /**
     * Seed the database.
     * Only available in non-production environments.
     */
    public function seedDatabase(): RedirectResponse
    {
        // Double check environment (should be protected by route, but extra safety)
        if (app()->environment('production')) {
            abort(403, 'Seeding is not allowed in production environment.');
        }

        try {
            Artisan::call('db:seed', [
                '--force' => true,
                '--no-interaction' => true,
            ]);

            $output = Artisan::output();

            return redirect()->route('system-actions.index')
                ->with('success', 'Baza danych została zaseedowana pomyślnie! '.($output ?: ''));
        } catch (\Exception $e) {
            return redirect()->route('system-actions.index')
                ->with('error', 'Błąd podczas seedowania bazy danych: '.$e->getMessage());
        }
    }
}
