<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectDemandController;
use App\Http\Controllers\ProjectAssignmentController;
use App\Http\Controllers\BulkAssignmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\AccommodationController;
use App\Http\Controllers\VehicleAssignmentController;
use App\Http\Controllers\AccommodationAssignmentController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserRoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EmployeeDocumentController;
use App\Http\Controllers\DocumentTypeController;
use App\Http\Controllers\WeeklyOverviewController;
use App\Http\Controllers\RotationController;
use App\Http\Controllers\EmployeeRateController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

//review
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// TEST ENDPOINT - Debug Railway deployment (remove after testing)
Route::get('/test', function () {
    return response()->json([
        'status' => 'ok',
        'app_key' => config('app.key') ? 'set' : 'missing',
        'app_key_length' => config('app.key') ? strlen(config('app.key')) : 0,
        'env' => config('app.env'),
        'debug' => config('app.debug'),
        'app_name' => config('app.name'),
        'timestamp' => now()->toIso8601String(),
    ], 200);
});

// Healthcheck moved to routes/api.php to avoid web middleware requiring APP_KEY

// Homepage - normal Laravel route (can have auth, middleware, etc.)
Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/2', function () {
    return view('welcome2');
})->name('home2');

Route::middleware(['auth', 'verified', 'role.required', 'permission.check'])->group(function () {
    
    // ===== ACTION ROUTES =====
    // IMPORTANT: Action routes MUST be defined BEFORE resource routes to avoid route conflicts
    // Laravel matches routes in order, so specific routes (like /prepare) must come before parameterized routes (like /{id})
    Route::group(['defaults' => ['permission_type' => 'action']], function () {
        // System actions - light cache clear (permissions + routes only)
        Route::post('/system-actions/clear-permissions', function () {
            try {
                \Illuminate\Support\Facades\Artisan::call('permission:cache-reset');
                \Illuminate\Support\Facades\Artisan::call('route:clear');
                
                return redirect()->route('system-actions.index')
                    ->with('success', 'Cache uprawnień i route zostały odświeżone!');
            } catch (\Exception $e) {
                return redirect()->route('system-actions.index')
                    ->with('error', 'Błąd: ' . $e->getMessage());
            }
        })->name('system-actions.clear-permissions')
          ->defaults('resource', 'system-actions');
        
        // System actions - sync permissions from routes to database
        Route::post('/system-actions/sync-permissions', function () {
            try {
                $service = app(\App\Services\RoutePermissionService::class);
                $routePerms = $service->getAllPermissionsFromRoutes();
                
                $created = 0;
                $existing = 0;
                
                foreach ($routePerms as $perm) {
                    $p = \Spatie\Permission\Models\Permission::firstOrCreate([
                        'name' => $perm['name'],
                        'guard_name' => 'web',
                    ]);
                    
                    if ($p->wasRecentlyCreated) {
                        $created++;
                    } else {
                        $existing++;
                    }
                }
                
                return redirect()->route('system-actions.index')
                    ->with('success', "Synchronizacja zakończona! Utworzono: {$created}, Istniało już: {$existing}");
            } catch (\Exception $e) {
                return redirect()->route('system-actions.index')
                    ->with('error', 'Błąd synchronizacji: ' . $e->getMessage());
            }
        })->name('system-actions.sync-permissions')
          ->defaults('resource', 'system-actions');
        
        // System actions - run migrations
        Route::post('/system-actions/run-migrations', function () {
            try {
                // Uruchom migracje z --force (wymaga na produkcji)
                \Illuminate\Support\Facades\Artisan::call('migrate', [
                    '--force' => true,
                    '--no-interaction' => true,
                ]);
                
                $output = \Illuminate\Support\Facades\Artisan::output();
                
                return redirect()->route('system-actions.index')
                    ->with('success', 'Migracje uruchomione pomyślnie! ' . ($output ?: 'Brak nowych migracji do uruchomienia.'));
            } catch (\Exception $e) {
                return redirect()->route('system-actions.index')
                    ->with('error', 'Błąd podczas uruchamiania migracji: ' . $e->getMessage());
            }
        })->name('system-actions.run-migrations')
          ->defaults('resource', 'system-actions');
        
        // System actions - fix old departures without end_date
        Route::post('/system-actions/fix-departure-dates', function () {
            try {
                \Illuminate\Support\Facades\Artisan::call('fix:departure-end-dates');
                $output = \Illuminate\Support\Facades\Artisan::output();
                
                return redirect()->route('system-actions.index')
                    ->with('success', 'Naprawiono daty wyjazdów! ' . $output);
            } catch (\Exception $e) {
                return redirect()->route('system-actions.index')
                    ->with('error', 'Błąd naprawy dat: ' . $e->getMessage());
            }
        })->name('system-actions.fix-departure-dates')
          ->defaults('resource', 'system-actions');
        
        // System actions - toggle debug mode (temporary via cache)
        Route::post('/system-actions/debug-on', function () {
            try {
                \Illuminate\Support\Facades\Cache::put('force_debug_mode', true, now()->addHour());
                
                return redirect()->route('system-actions.index')
                    ->with('success', '🐛 Debug mode WŁĄCZONY na 1 godzinę! Odśwież stronę aby zobaczyć szczegółowe błędy.');
            } catch (\Exception $e) {
                return redirect()->route('system-actions.index')
                    ->with('error', 'Błąd: ' . $e->getMessage());
            }
        })->name('system-actions.debug-on')
          ->defaults('resource', 'system-actions');
        
        Route::post('/system-actions/debug-off', function () {
            try {
                \Illuminate\Support\Facades\Cache::forget('force_debug_mode');
                
                return redirect()->route('system-actions.index')
                    ->with('success', '✅ Debug mode WYŁĄCZONY! Błędy znów ukryte.');
            } catch (\Exception $e) {
                return redirect()->route('system-actions.index')
                    ->with('error', 'Błąd: ' . $e->getMessage());
            }
        })->name('system-actions.debug-off')
          ->defaults('resource', 'system-actions');
        
        // System actions - full cache clear
        Route::post('/system-actions/clear-cache', function () {
            try {
                \Illuminate\Support\Facades\Artisan::call('optimize:clear');
                \Illuminate\Support\Facades\Artisan::call('permission:cache-reset');
                \Illuminate\Support\Facades\Cache::flush();
                
                return redirect()->route('system-actions.index')
                    ->with('success', 'Wszystkie cache zostały wyczyszczone pomyślnie!');
            } catch (\Exception $e) {
                return redirect()->route('system-actions.index')
                    ->with('error', 'Błąd: ' . $e->getMessage());
            }
        })->name('system-actions.clear-cache')
          ->defaults('resource', 'system-actions');
        // Return Trips Actions - MUST BE BEFORE resource routes to avoid route conflict
        Route::post('return-trips/prepare', [\App\Http\Controllers\ReturnTripController::class, 'prepareFromForm'])
            ->name('return-trips.prepare-form')
            ->defaults('resource', 'return-trips');
        Route::get('return-trips/prepare', [\App\Http\Controllers\ReturnTripController::class, 'prepare'])
            ->name('return-trips.prepare')
            ->defaults('resource', 'return-trips');
        Route::post('return-trips/{returnTrip}/cancel', [\App\Http\Controllers\ReturnTripController::class, 'cancel'])
            ->name('return-trips.cancel')
            ->defaults('resource', 'return-trips');
        Route::post('departures/{departure}/cancel', [\App\Http\Controllers\DepartureController::class, 'cancel'])
            ->name('departures.cancel')
            ->defaults('resource', 'departures');
        Route::get('departures/{departure}/prepare-cancellation', [\App\Http\Controllers\DepartureController::class, 'prepareCancellation'])
            ->name('departures.prepare-cancellation')
            ->defaults('resource', 'departures');
        
        // Two-step departure creation with bulk assignments
        Route::post('departures/prepare-bulk-assignment', [\App\Http\Controllers\DepartureController::class, 'prepareBulkAssignment'])
            ->name('departures.prepare-bulk-assignment')
            ->defaults('resource', 'departures');
        Route::post('departures/store-with-assignments', [\App\Http\Controllers\DepartureController::class, 'storeWithAssignments'])
            ->name('departures.store-with-assignments')
            ->defaults('resource', 'departures');
        
        // Equipment Issues Actions
        Route::get('equipment-issues/{equipmentIssue}/return', [\App\Http\Controllers\EquipmentIssueController::class, 'returnForm'])
            ->name('equipment-issues.return')
            ->defaults('resource', 'equipment-issues');
        Route::post('equipment-issues/{equipmentIssue}/return', [\App\Http\Controllers\EquipmentIssueController::class, 'return'])
            ->name('equipment-issues.return.store')
            ->defaults('resource', 'equipment-issues');
        
        // Time Logs Actions
        Route::get('time-logs/monthly-grid', [\App\Http\Controllers\TimeLogController::class, 'monthlyGrid'])
            ->name('time-logs.monthly-grid')
            ->defaults('resource', 'time-logs');
        Route::post('time-logs/bulk-update', [\App\Http\Controllers\TimeLogController::class, 'bulkUpdate'])
            ->name('time-logs.bulk-update')
            ->defaults('resource', 'time-logs');
        
        // Payrolls Actions
        Route::get('payrolls/generate-batch', [\App\Http\Controllers\PayrollController::class, 'generateBatchForm'])
            ->name('payrolls.generate-batch')
            ->defaults('resource', 'payrolls');
        Route::post('payrolls/generate-batch', [\App\Http\Controllers\PayrollController::class, 'generateBatch'])
            ->name('payrolls.generate-batch.store')
            ->defaults('resource', 'payrolls');
        Route::post('payrolls/recalculate-all', [\App\Http\Controllers\PayrollController::class, 'recalculateAll'])
            ->name('payrolls.recalculate-all')
            ->defaults('resource', 'payrolls');
        Route::post('payrolls/{payroll}/recalculate', [\App\Http\Controllers\PayrollController::class, 'recalculate'])
            ->name('payrolls.recalculate')
            ->defaults('resource', 'payrolls');
        
        // Project Tasks Actions - MUST BE BEFORE resource routes to avoid route conflict
        Route::post('projects/{project}/tasks/{task}/mark-in-progress', [\App\Http\Controllers\ProjectTaskController::class, 'markInProgress'])
            ->name('projects.tasks.mark-in-progress')
            ->defaults('resource', 'project-tasks');
        Route::post('projects/{project}/tasks/{task}/mark-completed', [\App\Http\Controllers\ProjectTaskController::class, 'markCompleted'])
            ->name('projects.tasks.mark-completed')
            ->defaults('resource', 'project-tasks');
        Route::post('projects/{project}/tasks/{task}/cancel', [\App\Http\Controllers\ProjectTaskController::class, 'cancel'])
            ->name('projects.tasks.cancel')
            ->defaults('resource', 'project-tasks');
        
        // Global task actions (for tasks without project)
        Route::post('tasks/{task}/mark-in-progress', [\App\Http\Controllers\ProjectTaskController::class, 'markInProgressGlobal'])
            ->name('tasks.mark-in-progress')
            ->defaults('resource', 'project-tasks');
        Route::post('tasks/{task}/mark-completed', [\App\Http\Controllers\ProjectTaskController::class, 'markCompletedGlobal'])
            ->name('tasks.mark-completed')
            ->defaults('resource', 'project-tasks');
        Route::post('tasks/{task}/cancel', [\App\Http\Controllers\ProjectTaskController::class, 'cancelGlobal'])
            ->name('tasks.cancel')
            ->defaults('resource', 'project-tasks');
    });
    
    // ===== RESOURCE ROUTES =====
    Route::group(['defaults' => ['permission_type' => 'resource']], function () {
    // Projects + nested demands + assignments
        Route::resource('projects', ProjectController::class);
    
    // Project tabs - usunięte, teraz przez Livewire ProjectTabs z query string
    
    // Project files
    Route::post('projects/{project}/files', [\App\Http\Controllers\ProjectFileController::class, 'store'])
        ->name('projects.files.store');
    Route::delete('projects/{project}/files/{file}', [\App\Http\Controllers\ProjectFileController::class, 'destroy'])
        ->name('projects.files.destroy');
    Route::get('projects/{project}/files/{file}/download', [\App\Http\Controllers\ProjectFileController::class, 'download'])
        ->name('projects.files.download');
    
    // Project tasks
    // Global tasks view
    Route::get('tasks', [\App\Http\Controllers\ProjectTaskController::class, 'index'])
        ->name('tasks.index')
        ->defaults('permission_type', 'resource')
        ->defaults('resource', 'project-tasks');
    
    // Global tasks store (without project requirement)
    Route::post('tasks', [\App\Http\Controllers\ProjectTaskController::class, 'storeGlobal'])
        ->name('tasks.store')
        ->defaults('permission_type', 'resource')
        ->defaults('resource', 'project-tasks');
    
    // Projects.tasks routes (explicit routes with defaults instead of resource)
    Route::post('projects/{project}/tasks', [\App\Http\Controllers\ProjectTaskController::class, 'store'])
        ->name('projects.tasks.store')
        ->defaults('permission_type', 'resource')
        ->defaults('resource', 'project-tasks');
    Route::get('projects/{project}/tasks/{task}', [\App\Http\Controllers\ProjectTaskController::class, 'show'])
        ->name('projects.tasks.show')
        ->defaults('permission_type', 'resource')
        ->defaults('resource', 'project-tasks');
    Route::get('projects/{project}/tasks/{task}/edit', [\App\Http\Controllers\ProjectTaskController::class, 'edit'])
        ->name('projects.tasks.edit')
        ->defaults('permission_type', 'resource')
        ->defaults('resource', 'project-tasks');
    Route::match(['put', 'patch'], 'projects/{project}/tasks/{task}', [\App\Http\Controllers\ProjectTaskController::class, 'update'])
        ->name('projects.tasks.update')
        ->defaults('permission_type', 'resource')
        ->defaults('resource', 'project-tasks');
    Route::delete('projects/{project}/tasks/{task}', [\App\Http\Controllers\ProjectTaskController::class, 'destroy'])
        ->name('projects.tasks.destroy')
        ->defaults('permission_type', 'resource')
        ->defaults('resource', 'project-tasks');
    
    // Global task views (for tasks without project)
    Route::get('tasks/{task}', [\App\Http\Controllers\ProjectTaskController::class, 'showGlobal'])
        ->name('tasks.show')
        ->defaults('permission_type', 'resource')
        ->defaults('resource', 'project-tasks');
    Route::get('tasks/{task}/edit', [\App\Http\Controllers\ProjectTaskController::class, 'editGlobal'])
        ->name('tasks.edit')
        ->defaults('permission_type', 'resource')
        ->defaults('resource', 'project-tasks');
    Route::put('tasks/{task}', [\App\Http\Controllers\ProjectTaskController::class, 'updateGlobal'])
        ->name('tasks.update')
        ->defaults('permission_type', 'resource')
        ->defaults('resource', 'project-tasks');
    
    // Comments (polymorphic)
    Route::post('comments', [\App\Http\Controllers\CommentController::class, 'store'])
        ->name('comments.store');
    Route::put('comments/{comment}', [\App\Http\Controllers\CommentController::class, 'update'])
        ->name('comments.update');
    Route::delete('comments/{comment}', [\App\Http\Controllers\CommentController::class, 'destroy'])
        ->name('comments.destroy');

    Route::resource('projects.demands', ProjectDemandController::class)
        ->shallow()
        ->names([
            'show' => 'demands.show',
            'edit' => 'demands.edit',
            'update' => 'demands.update',
            'destroy' => 'demands.destroy',
        ]);
    
    // Global route for all demands (without project context)
    Route::get('project-demands', [ProjectDemandController::class, 'all'])
        ->name('project-demands.index');

    // Project assignments - standard resource route (inherits permission_type from group)
    Route::resource('project-assignments', ProjectAssignmentController::class)
        ->except(['index']);
    
    // Global route for all assignments (without project context)
    Route::get('project-assignments', [ProjectAssignmentController::class, 'all'])
        ->name('project-assignments.index');
    
    // Bulk assignment (project + vehicle + accommodation in one go)
    Route::post('bulk-assignments', [\App\Http\Controllers\BulkAssignmentController::class, 'store'])
        ->name('bulk-assignments.store')
        ->defaults('permission_type', 'resource')
        ->defaults('resource', 'project-assignments');

    // Employees + assignments + documents
    Route::resource('employees', EmployeeController::class);
    
    // Employee tabs - usunięte, teraz przez Livewire EmployeeTabs z query string
    
    // Employee documents - globalne resource route z query params
    // Używamy osobnych route'ów zamiast resource() żeby mieć pełną kontrolę nad defaults
    Route::get('employee-documents/create', [EmployeeDocumentController::class, 'create'])
        ->name('employee-documents.create')
        ->defaults('permission_type', 'resource')
        ->defaults('resource', 'employee-documents');
    Route::post('employee-documents', [EmployeeDocumentController::class, 'store'])
        ->name('employee-documents.store')
        ->defaults('permission_type', 'resource')
        ->defaults('resource', 'employee-documents');
    Route::get('employee-documents/{employeeDocument}/edit', [EmployeeDocumentController::class, 'edit'])
        ->name('employee-documents.edit')
        ->defaults('permission_type', 'resource')
        ->defaults('resource', 'employee-documents');
    Route::put('employee-documents/{employeeDocument}', [EmployeeDocumentController::class, 'update'])
        ->name('employee-documents.update')
        ->defaults('permission_type', 'resource')
        ->defaults('resource', 'employee-documents');
    Route::patch('employee-documents/{employeeDocument}', [EmployeeDocumentController::class, 'update'])
        ->name('employee-documents.update')
        ->defaults('permission_type', 'resource')
        ->defaults('resource', 'employee-documents');
    Route::delete('employee-documents/{employeeDocument}', [EmployeeDocumentController::class, 'destroy'])
        ->name('employee-documents.destroy')
        ->defaults('permission_type', 'resource')
        ->defaults('resource', 'employee-documents');
    
    // Download route for employee documents (with authorization)
    Route::get('employee-documents/{employeeDocument}/download', [EmployeeDocumentController::class, 'download'])
        ->name('employee-documents.download')
        ->defaults('permission_type', 'view')
        ->defaults('resource', 'employee-documents');
    
    // Rotations (global routes)
    Route::get('rotations', [RotationController::class, 'all'])->name('rotations.index');
    Route::get('rotations/create', [RotationController::class, 'createGlobal'])->name('rotations.create');
    Route::post('rotations', [RotationController::class, 'storeGlobal'])->name('rotations.store');
    
    // Employee rotations tab - usunięte, teraz przez Livewire EmployeeTabs
    
    // Rotations (nested under employees) - scoped for security
    Route::resource('employees.rotations', RotationController::class)
        ->scoped()
        ->parameters(['rotations' => 'rotation']);
    
    // Employee Documents (dokumenty pracowników - globalna lista)
    Route::get('employee-documents', [EmployeeDocumentController::class, 'index'])
        ->name('employee-documents.index')
        ->defaults('permission_type', 'view')
        ->defaults('resource', 'employee-documents');
    
    // Documents (słownik dokumentów)
    Route::resource('documents', \App\Http\Controllers\DocumentController::class);

    // Vehicle assignments - globalne resource route z query params
    Route::resource('vehicle-assignments', VehicleAssignmentController::class)
        ->except(['index']);

    // Accommodation assignments - globalne resource route z query params
    Route::resource('accommodation-assignments', AccommodationAssignmentController::class)
        ->except(['index']);
    
    // Global routes for all vehicle and accommodation assignments
    Route::get('vehicle-assignments', [VehicleAssignmentController::class, 'all'])
        ->name('vehicle-assignments.index');
    
    Route::get('accommodation-assignments', [AccommodationAssignmentController::class, 'all'])
        ->name('accommodation-assignments.index');

    // Vehicles, Accommodations (CRUD)
    Route::resource('vehicles', VehicleController::class);
    Route::resource('accommodations', AccommodationController::class);

    // Locations, Roles (CRUD)
    Route::resource('locations', LocationController::class);
    Route::resource('roles', RoleController::class);
    
    // User Roles (RBAC)
    Route::resource('user-roles', UserRoleController::class);
    
    // Users Management
    Route::resource('users', UserController::class);
    
    // Equipment
    Route::resource('equipment', \App\Http\Controllers\EquipmentController::class);
    Route::resource('equipment-issues', \App\Http\Controllers\EquipmentIssueController::class);
    
    // Transport Costs
    Route::resource('transport-costs', \App\Http\Controllers\TransportCostController::class);
    
    // Project Variable Costs
    Route::resource('project-variable-costs', \App\Http\Controllers\ProjectVariableCostController::class);
    
    // Fixed Costs
    Route::get('fixed-costs/generate', [\App\Http\Controllers\FixedCostController::class, 'generateForm'])
        ->name('fixed-costs.generate')
        ->defaults('resource', 'fixed-costs');
    Route::post('fixed-costs/generate', [\App\Http\Controllers\FixedCostController::class, 'generate'])
        ->name('fixed-costs.generate.store')
        ->defaults('resource', 'fixed-costs');
    
    // Fixed Costs Tabs
    Route::get('fixed-costs/tab/templates', [\App\Http\Controllers\FixedCostController::class, 'indexTemplates'])
        ->name('fixed-costs.tab.templates')
        ->defaults('resource', 'fixed-costs');
    Route::get('fixed-costs/tab/entries', [\App\Http\Controllers\FixedCostController::class, 'indexEntries'])
        ->name('fixed-costs.tab.entries')
        ->defaults('resource', 'fixed-costs');
    
    Route::resource('fixed-costs', \App\Http\Controllers\FixedCostController::class);
    
    // Fixed Cost Entries (koszty księgowe)
    Route::get('fixed-cost-entries/create', [\App\Http\Controllers\FixedCostController::class, 'createEntry'])
        ->name('fixed-cost-entries.create')
        ->defaults('resource', 'fixed-cost-entries');
    Route::post('fixed-cost-entries', [\App\Http\Controllers\FixedCostController::class, 'storeEntry'])
        ->name('fixed-cost-entries.store')
        ->defaults('resource', 'fixed-cost-entries');
    Route::get('fixed-cost-entries/{entry}', [\App\Http\Controllers\FixedCostController::class, 'showEntry'])
        ->name('fixed-cost-entries.show')
        ->defaults('resource', 'fixed-cost-entries');
    Route::delete('fixed-cost-entries/{entry}', [\App\Http\Controllers\FixedCostController::class, 'destroyEntry'])
        ->name('fixed-cost-entries.destroy')
        ->defaults('resource', 'fixed-cost-entries');
    
    // Time Logs
    Route::resource('time-logs', \App\Http\Controllers\TimeLogController::class);
        Route::get('assignments/{assignment}/time-logs', [\App\Http\Controllers\TimeLogController::class, 'byAssignment'])
            ->name('assignments.time-logs');
    
    // Employee Rates
    Route::resource('employee-rates', \App\Http\Controllers\EmployeeRateController::class);
    
    // Employee Evaluations
    Route::resource('employee-evaluations', \App\Http\Controllers\EmployeeEvaluationController::class);
    
    // Payroll
    Route::resource('payrolls', \App\Http\Controllers\PayrollController::class);
    
    // Adjustments (Kary/Nagrody)
    Route::resource('adjustments', \App\Http\Controllers\AdjustmentController::class);
    
    // Advances (Zaliczki)
    Route::resource('advances', \App\Http\Controllers\AdvanceController::class);
    
    // Return Trips (Zjazdy) - resource routes (MUST BE AFTER action routes to avoid route conflict)
    // Action routes like /return-trips/prepare must be registered before /return-trips/{id}
    Route::resource('return-trips', \App\Http\Controllers\ReturnTripController::class)->except(['destroy']);
    
    Route::resource('departures', \App\Http\Controllers\DepartureController::class)->except(['destroy']);
    });
    
    // ===== VIEW ROUTES =====
    Route::group(['defaults' => ['permission_type' => 'view']], function () {
        Route::get('/dashboard', function () {
            return view('dashboard');
        })->name('dashboard');
        
        Route::get('/system-actions', function () {
            return view('system-actions');
        })->name('system-actions.index');
        
        // Redirect old route to new one
        Route::get('/dashboard/profitability', function () {
            return redirect()->route('profitability.index');
        });
        
        Route::get('/profitability', [DashboardController::class, 'index'])
            ->name('profitability.index');
        
        Route::get('/weekly-overview', [WeeklyOverviewController::class, 'index'])
            ->name('weekly-overview.index');
        
        Route::get('/weekly-overview/planner2', [WeeklyOverviewController::class, 'planner2'])
            ->name('weekly-overview.planner2');
        
        Route::get('/weekly-overview/planner3', [WeeklyOverviewController::class, 'planner3'])
            ->name('weekly-overview.planner3');
    });
    
    // Profile routes (excluded from permission checking)
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
    });
    
    // Mine routes - projects managed by current user
    Route::prefix('mine')->name('mine.')->group(function () {
        Route::get('projects', [\App\Http\Controllers\MineController::class, 'projects'])->name('projects.index');
        Route::get('projects/{project}', [\App\Http\Controllers\MineController::class, 'show'])->name('projects.show');
        Route::get('tasks', [\App\Http\Controllers\MineController::class, 'tasks'])->name('tasks.index');
        Route::get('time-logs', [\App\Http\Controllers\MineController::class, 'timeLogs'])->name('time-logs.index');
        Route::get('time-logs/monthly-grid', [\App\Http\Controllers\MineController::class, 'monthlyGrid'])->name('time-logs.monthly-grid');
        Route::get('employees', [\App\Http\Controllers\MineController::class, 'employees'])->name('employees.index');
        Route::get('assignments', [\App\Http\Controllers\MineController::class, 'assignments'])->name('assignments.index');
        Route::get('employee-evaluations', [\App\Http\Controllers\MineController::class, 'employeeEvaluations'])->name('employee-evaluations.index');
    });
});

// Route for users without roles (must be outside role.required middleware)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/no-role', function () {
        return view('no-role');
    })->name('no-role');
});

require __DIR__.'/auth.php';
