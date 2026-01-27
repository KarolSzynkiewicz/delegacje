<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectDemandController;
use App\Http\Controllers\ProjectAssignmentController;
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

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

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
    });
    
    // ===== RESOURCE ROUTES =====
    Route::group(['defaults' => ['permission_type' => 'resource']], function () {
    // Projects + nested demands + assignments
        Route::resource('projects', ProjectController::class);
    
    // Project tabs with separate routes for permission checking
    Route::get('projects/{project}/tab/files', [ProjectController::class, 'showFiles'])
        ->name('projects.show.files')
        ->defaults('resource', 'project-files');
    Route::get('projects/{project}/tab/tasks', [ProjectController::class, 'showTasks'])
        ->name('projects.show.tasks')
        ->defaults('resource', 'project-tasks');
    Route::get('projects/{project}/tab/assignments', [ProjectController::class, 'showAssignments'])
        ->name('projects.show.assignments')
        ->defaults('resource', 'assignments');
    Route::get('projects/{project}/tab/comments', [ProjectController::class, 'showComments'])
        ->name('projects.show.comments')
        ->defaults('resource', 'comments');
    
    // Project files
    Route::post('projects/{project}/files', [\App\Http\Controllers\ProjectFileController::class, 'store'])
        ->name('projects.files.store');
    Route::delete('projects/{project}/files/{file}', [\App\Http\Controllers\ProjectFileController::class, 'destroy'])
        ->name('projects.files.destroy');
    Route::get('projects/{project}/files/{file}/download', [\App\Http\Controllers\ProjectFileController::class, 'download'])
        ->name('projects.files.download');
    
    // Project tasks
    Route::resource('projects.tasks', \App\Http\Controllers\ProjectTaskController::class)
        ->except(['index', 'create']);
    Route::post('projects/{project}/tasks/{task}/mark-in-progress', [\App\Http\Controllers\ProjectTaskController::class, 'markInProgress'])
        ->name('projects.tasks.mark-in-progress');
    Route::post('projects/{project}/tasks/{task}/mark-completed', [\App\Http\Controllers\ProjectTaskController::class, 'markCompleted'])
        ->name('projects.tasks.mark-completed');
    Route::post('projects/{project}/tasks/{task}/cancel', [\App\Http\Controllers\ProjectTaskController::class, 'cancel'])
        ->name('projects.tasks.cancel');
    
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

    Route::resource('projects.assignments', ProjectAssignmentController::class)
        ->shallow()
        ->names([
            'show' => 'assignments.show',
            'edit' => 'assignments.edit',
            'update' => 'assignments.update',
            'destroy' => 'assignments.destroy',
        ]);
    
    // Global route for all assignments (without project context)
    Route::get('project-assignments', [ProjectAssignmentController::class, 'all'])
        ->name('project-assignments.index')
        ->defaults('resource', 'assignments');

    // Employees + assignments + documents
    Route::resource('employees', EmployeeController::class);
    
    // Employee tabs with separate routes for permission checking
    // Using /tab/ prefix to avoid conflicts with resource routes
    Route::get('employees/{employee}/tab/documents', [EmployeeController::class, 'showDocuments'])
        ->name('employees.show.documents')
        ->defaults('resource', 'employee-documents');
    Route::get('employees/{employee}/tab/assignments', [EmployeeController::class, 'showAssignments'])
        ->name('employees.show.assignments')
        ->defaults('resource', 'assignments');
    Route::get('employees/{employee}/tab/payrolls', [EmployeeController::class, 'showPayrolls'])
        ->name('employees.show.payrolls')
        ->defaults('resource', 'payrolls');
    Route::get('employees/{employee}/tab/employee-rates', [EmployeeController::class, 'showEmployeeRates'])
        ->name('employees.show.employee-rates')
        ->defaults('resource', 'employee-rates');
    Route::get('employees/{employee}/tab/advances', [EmployeeController::class, 'showAdvances'])
        ->name('employees.show.advances')
        ->defaults('resource', 'advances');
    Route::get('employees/{employee}/tab/time-logs', [EmployeeController::class, 'showTimeLogs'])
        ->name('employees.show.time-logs')
        ->defaults('resource', 'time-logs');
    Route::get('employees/{employee}/tab/adjustments', [EmployeeController::class, 'showAdjustments'])
        ->name('employees.show.adjustments')
        ->defaults('resource', 'adjustments');
    
        Route::resource('employees.employee-documents', EmployeeDocumentController::class)
            ->except(['index', 'show'])
            ->parameters(['employee-documents' => 'employeeDocument']);
    
    // Rotations (global routes)
    Route::get('rotations', [RotationController::class, 'all'])->name('rotations.index');
    Route::get('rotations/create', [RotationController::class, 'createGlobal'])->name('rotations.create');
    Route::post('rotations', [RotationController::class, 'storeGlobal'])->name('rotations.store');
    
    // Employee rotations tab - using /tab/ prefix to avoid conflicts
    Route::get('employees/{employee}/tab/rotations', [EmployeeController::class, 'showRotations'])
        ->name('employees.show.rotations')
        ->defaults('resource', 'rotations');
    
    // Rotations (nested under employees) - scoped for security
    Route::resource('employees.rotations', RotationController::class)
        ->scoped()
        ->parameters(['rotations' => 'rotation']);
    
    // Employee Documents (dokumenty pracowników - globalna lista)
    Route::get('employee-documents', [EmployeeDocumentController::class, 'index'])->name('employee-documents.index');
    
    // Documents (słownik dokumentów)
    Route::resource('documents', \App\Http\Controllers\DocumentController::class);

    // Employee vehicle assignments tab - using /tab/ prefix to avoid conflicts
    Route::get('employees/{employee}/tab/vehicle-assignments', [EmployeeController::class, 'showVehicleAssignments'])
        ->name('employees.show.vehicle-assignments')
        ->defaults('resource', 'vehicle-assignments');
    
    Route::resource('employees.vehicle-assignments', VehicleAssignmentController::class)
        ->shallow()
        ->names([
            'index' => 'employees.vehicles.index',
            'create' => 'employees.vehicles.create',
            'store' => 'employees.vehicles.store',
            'show' => 'vehicle-assignments.show',
            'edit' => 'vehicle-assignments.edit',
            'update' => 'vehicle-assignments.update',
            'destroy' => 'vehicle-assignments.destroy',
        ]);

    // Employee accommodation assignments tab - using /tab/ prefix to avoid conflicts
    Route::get('employees/{employee}/tab/accommodation-assignments', [EmployeeController::class, 'showAccommodationAssignments'])
        ->name('employees.show.accommodation-assignments')
        ->defaults('resource', 'accommodation-assignments');
    
    Route::resource('employees.accommodation-assignments', AccommodationAssignmentController::class)
        ->shallow()
        ->names([
            'index' => 'employees.accommodations.index',
            'create' => 'employees.accommodations.create',
            'store' => 'employees.accommodations.store',
            'show' => 'accommodation-assignments.show',
            'edit' => 'accommodation-assignments.edit',
            'update' => 'accommodation-assignments.update',
            'destroy' => 'accommodation-assignments.destroy',
        ]);
    
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
});

// Route for users without roles (must be outside role.required middleware)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/no-role', function () {
        return view('no-role');
    })->name('no-role');
});

require __DIR__.'/auth.php';
