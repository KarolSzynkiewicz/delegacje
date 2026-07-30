<?php

use App\Http\Controllers\AccommodationAssignmentController;
use App\Http\Controllers\RecruitmentProcessController;
use App\Http\Controllers\RecruitmentConsentController;
use App\Http\Controllers\AccommodationController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeDocumentController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectAssignmentController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectDemandController;
use App\Http\Controllers\PromptEngineController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\RotationController;
use App\Http\Controllers\SystemActionsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserRoleController;
use App\Http\Controllers\CompanyAssignmentController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\VehicleAssignmentController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\WeeklyOverviewController;
use Illuminate\Support\Facades\Route;

// review
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Homepage - normal Laravel route (can have auth, middleware, etc.)
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Publiczny formularz rekrutacyjny (bez autoryzacji)
Route::get('/rekrutacja', function () {
    return view('recruitment.apply');
})->name('recruitment.apply');

Route::prefix('rekrutacja')->name('recruitment.')->group(function () {
    Route::get('/rodo', [RecruitmentConsentController::class, 'rodo'])->name('rodo');
    Route::get('/zgoda-rekrutacja', [RecruitmentConsentController::class, 'recruitmentProcessing'])->name('consent.recruitment');
    Route::get('/zgoda-marketingowa', [RecruitmentConsentController::class, 'marketing'])->name('consent.marketing');
});

// ui build helper
Route::get('/2', function () {
    return view('welcome2');
})->name('home2');

Route::middleware(['auth', 'verified', 'role.required', 'permission.check'])->group(function () {

    // ===== ACTION ROUTES =====
    // IMPORTANT: Action routes MUST be defined BEFORE resource routes to avoid route conflicts
    // Laravel matches routes in order, so specific routes (like /prepare) must come before parameterized routes (like /{id})

    Route::group(['defaults' => ['permission_type' => 'action']], function () {
        // System actions
        Route::post('/system-actions/clear-permissions', [SystemActionsController::class, 'clearPermissions'])
            ->name('system-actions.clear-permissions')
            ->defaults('resource', 'system-actions');

        Route::post('/system-actions/sync-permissions', [SystemActionsController::class, 'syncPermissions'])
            ->name('system-actions.sync-permissions')
            ->defaults('resource', 'system-actions');

        Route::post('/system-actions/run-migrations', [SystemActionsController::class, 'runMigrations'])
            ->name('system-actions.run-migrations')
            ->defaults('resource', 'system-actions');

        Route::post('/system-actions/debug-on', [SystemActionsController::class, 'debugOn'])
            ->name('system-actions.debug-on')
            ->defaults('resource', 'system-actions');

        Route::post('/system-actions/debug-off', [SystemActionsController::class, 'debugOff'])
            ->name('system-actions.debug-off')
            ->defaults('resource', 'system-actions');

        Route::post('/system-actions/clear-cache', [SystemActionsController::class, 'clearCache'])
            ->name('system-actions.clear-cache')
            ->defaults('resource', 'system-actions');

        Route::post('/system-actions/sync-location-purposes', [SystemActionsController::class, 'syncLocationPurposes'])
            ->name('system-actions.sync-location-purposes')
            ->defaults('resource', 'system-actions');

        Route::post('/system-actions/fix-location-names', [SystemActionsController::class, 'fixLocationNames'])
            ->name('system-actions.fix-location-names')
            ->defaults('resource', 'system-actions');

        Route::get('/system-actions/backup-database', [SystemActionsController::class, 'backupDatabase'])
            ->name('system-actions.backup-database')
            ->defaults('resource', 'system-actions');
        // Return Trips Actions - MUST BE BEFORE resource routes to avoid route conflict
        Route::post('return-trips/prepare', [\App\Http\Controllers\ReturnTripController::class, 'prepareFromForm'])
            ->name('return-trips.prepare-form')
            ->defaults('resource', 'return-trips');
        Route::get('return-trips/prepare', [\App\Http\Controllers\ReturnTripController::class, 'prepare'])
            ->name('return-trips.prepare')
            ->defaults('resource', 'return-trips');
        Route::match(['get', 'post'], 'return-trips/store-v2', [\App\Http\Controllers\ReturnTripController::class, 'storeV2'])
            ->name('return-trips.store-v2')
            ->defaults('resource', 'return-trips');

        Route::post('vehicle-repairs/prepare', [\App\Http\Controllers\VehicleRepairController::class, 'prepareFromForm'])
            ->name('vehicle-repairs.prepare-form')
            ->defaults('resource', 'vehicle-repairs');
        Route::get('vehicle-repairs/prepare', [\App\Http\Controllers\VehicleRepairController::class, 'prepare'])
            ->name('vehicle-repairs.prepare')
            ->defaults('resource', 'vehicle-repairs');
        Route::post('return-trips/{returnTrip}/cancel', [\App\Http\Controllers\ReturnTripController::class, 'cancel'])
            ->name('return-trips.cancel')
            ->defaults('resource', 'return-trips');
        Route::post('departures/{departure}/cancel', [\App\Http\Controllers\DepartureController::class, 'cancel'])
            ->name('departures.cancel')
            ->defaults('resource', 'departures');
        Route::get('departures/{departure}/prepare-cancellation', [\App\Http\Controllers\DepartureController::class, 'prepareCancellation'])
            ->name('departures.prepare-cancellation')
            ->defaults('resource', 'departures');

        // New V2 departure form with Livewire
        Route::match(['get', 'post'], 'departures/store-v2', [\App\Http\Controllers\DepartureController::class, 'storeV2'])
            ->name('departures.store-v2')
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
        Route::get('time-logs/analytics', [\App\Http\Controllers\TimeLogController::class, 'analytics'])
            ->name('time-logs.analytics')
            ->defaults('resource', 'time-logs');
        Route::post('time-logs/bulk-update', [\App\Http\Controllers\TimeLogController::class, 'bulkUpdate'])
            ->name('time-logs.bulk-update')
            ->defaults('resource', 'time-logs');
        Route::get('time-logs/export-csv', [\App\Http\Controllers\TimeLogController::class, 'exportCsv'])
            ->name('time-logs.export-csv')
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

        // Task actions
        Route::post('tasks/{task}/mark-in-progress', [\App\Http\Controllers\TaskController::class, 'markInProgress'])
            ->name('tasks.mark-in-progress')
            ->defaults('resource', 'tasks');
        Route::post('tasks/{task}/mark-completed', [\App\Http\Controllers\TaskController::class, 'markCompleted'])
            ->name('tasks.mark-completed')
            ->defaults('resource', 'tasks');
        Route::post('tasks/{task}/cancel', [\App\Http\Controllers\TaskController::class, 'cancel'])
            ->name('tasks.cancel')
            ->defaults('resource', 'tasks');

        // Geocoding API
        Route::get('api/geocoding/search', [\App\Http\Controllers\Api\GeocodingController::class, 'search'])
            ->name('api.geocoding.search');
        Route::post('api/geocoding/geocode', [\App\Http\Controllers\Api\GeocodingController::class, 'geocode'])
            ->name('api.geocoding.geocode');
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

        // Procedures (SOP)
        Route::group(['defaults' => ['resource' => 'procedure-templates']], function () {
            Route::resource('procedure-templates', \App\Http\Controllers\ProcedureTemplateController::class)
                ->except(['create', 'show']);
            Route::get('procedure-templates/{procedureTemplate}/editor',
                [\App\Http\Controllers\ProcedureTemplateController::class, 'editor'])
                ->name('procedure-templates.editor');
        });
        Route::group(['defaults' => ['resource' => 'procedure-runs']], function () {
            Route::post('procedure-templates/{procedureTemplate}/runs',
                [\App\Http\Controllers\ProcedureRunController::class, 'store'])
                ->name('procedure-runs.store');
            Route::post('procedure-runs/{procedureRun}/advance',
                [\App\Http\Controllers\ProcedureRunController::class, 'advance'])
                ->name('procedure-runs.advance');
            Route::post('procedure-runs/{procedureRun}/back',
                [\App\Http\Controllers\ProcedureRunController::class, 'back'])
                ->name('procedure-runs.back');
            Route::post('procedure-runs/{procedureRun}/abandon',
                [\App\Http\Controllers\ProcedureRunController::class, 'abandon'])
                ->name('procedure-runs.abandon');
            Route::post('procedure-runs/{procedureRun}/comments',
                [\App\Http\Controllers\ProcedureRunController::class, 'storeComment'])
                ->name('procedure-runs.comments.store');
        });

        // Tasks — tasks2 przed resource, żeby uniknąć konfliktów z tasks/{task}
        Route::group(['defaults' => ['resource' => 'tasks']], function () {
            Route::get('tasks/home', [\App\Http\Controllers\TaskController::class, 'home'])
                ->name('tasks.home');
            Route::post('tasks/default-view', [\App\Http\Controllers\TaskController::class, 'setDefaultView'])
                ->name('tasks.default-view');
            Route::get('tasks2', [\App\Http\Controllers\TaskController::class, 'grid'])
                ->name('tasks.grid');
            Route::get('tasks/grid', [\App\Http\Controllers\TaskController::class, 'grid'])
                ->name('tasks.grid.alias');
        });

        Route::group(['defaults' => ['resource' => 'tasks']], function () {
            Route::resource('tasks', \App\Http\Controllers\TaskController::class)
                ->except(['create']);
        });

        // Comments (polymorphic)
        Route::post('comments', [\App\Http\Controllers\CommentController::class, 'store'])
            ->name('comments.store');
        Route::put('comments/{comment}', [\App\Http\Controllers\CommentController::class, 'update'])
            ->name('comments.update');
        Route::delete('comments/{comment}', [\App\Http\Controllers\CommentController::class, 'destroy'])
            ->name('comments.destroy');
        Route::post('comments/{comment}/like', [\App\Http\Controllers\CommentController::class, 'toggleLike'])
            ->name('comments.like');

        Route::get('attachments/{attachment}/download', [\App\Http\Controllers\AttachmentController::class, 'download'])
            ->name('attachments.download');
        Route::delete('attachments/{attachment}', [\App\Http\Controllers\AttachmentController::class, 'destroy'])
            ->name('attachments.destroy');

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

        // Recruitment processes (admin panel)
        Route::get('recruitment-processes', [RecruitmentProcessController::class, 'index'])
            ->name('recruitment-processes.index')
            ->defaults('resource', 'employees');
        Route::get('recruitment-processes/{recruitmentProcess}', [RecruitmentProcessController::class, 'show'])
            ->name('recruitment-processes.show')
            ->defaults('resource', 'employees');
        Route::patch('recruitment-processes/{recruitmentProcess}/status', [RecruitmentProcessController::class, 'updateStatus'])
            ->name('recruitment-processes.update-status')
            ->defaults('resource', 'employees');
        Route::post('recruitment-processes/{recruitmentProcess}/convert', [RecruitmentProcessController::class, 'convert'])
            ->name('recruitment-processes.convert')
            ->defaults('resource', 'employees');

        // Employees + assignments + documents
        // Trasa 'destroy' celowo wyłączona — usuwanie pracowników jest zablokowane.
        // Employee model używa SoftDeletes; fizyczne usunięcie możliwe wyłącznie przez Tinker/DB.
        Route::resource('employees', EmployeeController::class)->except(['destroy']);

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
        Route::get('employee-documents/{employeeDocument}', [EmployeeDocumentController::class, 'show'])
            ->name('employee-documents.show')
            ->defaults('permission_type', 'view')
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

        // Inline preview route for employee documents (without forcing a download)
        Route::get('employee-documents/{employeeDocument}/preview', [EmployeeDocumentController::class, 'preview'])
            ->name('employee-documents.preview')
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

        // Vehicles, Accommodations, Companies (CRUD)
        Route::resource('vehicles', VehicleController::class);
        Route::resource('accommodations', AccommodationController::class);
        Route::resource('companies', CompanyController::class);
        Route::resource('company-assignments', CompanyAssignmentController::class);
        Route::post('accommodations/{accommodation}/leases', [AccommodationController::class, 'storeLease'])
            ->name('accommodations.leases.store');
        Route::put('accommodations/{accommodation}/leases/{lease}', [AccommodationController::class, 'updateLease'])
            ->name('accommodations.leases.update');
        Route::delete('accommodations/{accommodation}/leases/{lease}', [AccommodationController::class, 'destroyLease'])
            ->name('accommodations.leases.destroy');

        // Vehicle Repairs (książka serwisowa)
        Route::get('vehicle-repairs/{vehicleRepair}/complete', [\App\Http\Controllers\VehicleRepairController::class, 'completeForm'])
            ->name('vehicle-repairs.complete-form')
            ->defaults('resource', 'vehicle-repairs');
        Route::post('vehicle-repairs/{vehicleRepair}/complete', [\App\Http\Controllers\VehicleRepairController::class, 'complete'])
            ->name('vehicle-repairs.complete')
            ->defaults('resource', 'vehicle-repairs');
        Route::resource('vehicle-repairs', \App\Http\Controllers\VehicleRepairController::class);

        // Locations, Roles (CRUD)
        Route::get('locations/map', [LocationController::class, 'map'])->name('locations.map');
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

        // Exchange Rates (kursy walut — do przeliczeń orientacyjnych w kontrolingu)
        Route::resource('exchange-rates', \App\Http\Controllers\ExchangeRateController::class)
            ->except(['show']);

        // Fixed Cost Entries (koszty księgowe)
        Route::get('fixed-cost-entries/create', [\App\Http\Controllers\FixedCostController::class, 'createEntry'])
            ->name('fixed-cost-entries.create')
            ->defaults('resource', 'fixed-cost-entries');
        Route::get('fixed-cost-entries/create-many', [\App\Http\Controllers\FixedCostController::class, 'createManyEntries'])
            ->name('fixed-cost-entries.create-many')
            ->defaults('resource', 'fixed-cost-entries');
        Route::post('fixed-cost-entries/store-many', [\App\Http\Controllers\FixedCostController::class, 'storeManyEntries'])
            ->name('fixed-cost-entries.store-many')
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

        // Employee Rates
        Route::resource('employee-rates', \App\Http\Controllers\EmployeeRateController::class);

        // Employee Evaluations
        Route::resource('employee-evaluations', \App\Http\Controllers\EmployeeEvaluationController::class);

        // Payroll
        Route::resource('payrolls', \App\Http\Controllers\PayrollController::class);

        // Adjustments (obciążenia / uznania)
        Route::resource('adjustments', \App\Http\Controllers\AdjustmentController::class);

        // Advances (Zaliczki)
        Route::resource('advances', \App\Http\Controllers\AdvanceController::class);

        // Return Trips (Zjazdy) - resource routes (MUST BE AFTER action routes to avoid route conflict)
        // Action routes like /return-trips/prepare must be registered before /return-trips/{id}
        Route::resource('return-trips', \App\Http\Controllers\ReturnTripController::class)->except(['destroy']);

        // V2 departure form route (must be before resource route to avoid conflict)
        Route::get('departures/create-v2', [\App\Http\Controllers\DepartureController::class, 'createV2'])
            ->name('departures.create-v2');

        Route::resource('departures', \App\Http\Controllers\DepartureController::class)->except(['destroy', 'edit', 'update', 'create', 'store']);

        // Transfers
        Route::group(['defaults' => ['resource' => 'transfers']], function () {
            Route::post('transfers/{transfer}/cancel', [\App\Http\Controllers\TransferController::class, 'cancel'])
                ->name('transfers.cancel')
                ->defaults('permission_type', 'action');

            Route::get('transfers', [\App\Http\Controllers\TransferController::class, 'index'])
                ->name('transfers.index');
            Route::get('transfers/create', [\App\Http\Controllers\TransferController::class, 'create'])
                ->name('transfers.create');
            Route::get('transfers/{transfer}', [\App\Http\Controllers\TransferController::class, 'show'])
                ->name('transfers.show');
        });
    });

    // ===== VIEW ROUTES =====
    Route::group(['defaults' => ['permission_type' => 'view']], function () {
        Route::get('/dashboard', [DashboardController::class, 'home'])->name('dashboard');

        Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])
            ->name('notifications.index')
            // Powiadomienia są ściśle związane z zadaniami, więc opieramy dostęp o istniejące uprawnienie `tasks.view`
            ->defaults('resource', 'tasks');

        Route::get('/system-actions', function () {
            return view('system-actions');
        })->name('system-actions.index');

        Route::get('/audit-logs', [AuditLogController::class, 'index'])
            ->name('audit-logs.index')
            ->defaults('resource', 'audit-logs');

        Route::get('/changelog', function () {
            return view('changelog.index');
        })->name('changelog.index')
            ->defaults('resource', 'audit-logs');

        Route::get('/prompts', [PromptEngineController::class, 'index'])
            ->name('prompts.index')
            ->defaults('resource', 'tasks');

        Route::get('/prompts/export/tasks', [PromptEngineController::class, 'exportTasks'])
            ->name('prompts.export.tasks')
            ->defaults('resource', 'tasks');

        Route::get('/prompts/export/assignments', [PromptEngineController::class, 'exportAssignments'])
            ->name('prompts.export.assignments')
            ->defaults('resource', 'tasks');

        Route::get('/prompts/export/costs', [PromptEngineController::class, 'exportCosts'])
            ->name('prompts.export.costs')
            ->defaults('resource', 'tasks');

        Route::get('/prompts/export/time-logs', [PromptEngineController::class, 'exportTimeLogs'])
            ->name('prompts.export.time-logs')
            ->defaults('resource', 'tasks');

        // Redirect old route to new one
        Route::get('/dashboard/profitability', function () {
            return redirect()->route('profitability.index');
        });

        Route::get('/profitability', [DashboardController::class, 'index'])
            ->name('profitability.index');

        Route::get('/profitability/export-csv', [DashboardController::class, 'exportCsv'])
            ->name('profitability.export-csv')
            ->defaults('resource', 'profitability');

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

    // Mine routes - projects managed by current user for manages of projects)
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

// routes definied only for non-production enviroments.
if (! app()->environment('production')) {
    Route::middleware(['auth', 'verified', 'role.required', 'permission.check'])->group(function () {
        Route::post('/system-actions/seed-database', [SystemActionsController::class, 'seedDatabase'])
            ->name('system-actions.seed-database')
            ->defaults('resource', 'system-actions')
            ->defaults('permission_type', 'action');
    });
}

require __DIR__.'/auth.php';
