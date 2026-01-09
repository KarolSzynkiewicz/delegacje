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
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware(['auth', 'verified', 'role.required'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // Weekly Overview
    Route::get('/weekly-overview', [WeeklyOverviewController::class, 'index'])->name('weekly-overview.index');

    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
    });

    // Projects + nested demands + assignments
    Route::resource('projects', controller: ProjectController::class);

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
        ->name('project-assignments.index');

    // Employees + assignments + documents
    Route::resource('employees', EmployeeController::class);
    Route::resource('employees.employee-documents', EmployeeDocumentController::class)->except(['index', 'show'])->parameters(['employee-documents' => 'employeeDocument']);
    
    // Rotations (global routes)
    Route::get('rotations', [RotationController::class, 'all'])->name('rotations.index');
    Route::get('rotations/create', [RotationController::class, 'createGlobal'])->name('rotations.create');
    Route::post('rotations', [RotationController::class, 'storeGlobal'])->name('rotations.store');
    
    // Rotations (nested under employees) - scoped for security
    Route::resource('employees.rotations', RotationController::class)
        ->scoped()
        ->parameters(['rotations' => 'rotation']);
    
    // Employee Documents (dokumenty pracowników - globalna lista)
    Route::get('employee-documents', [EmployeeDocumentController::class, 'index'])->name('employee-documents.index');
    
    // Documents (słownik dokumentów)
    Route::resource('documents', \App\Http\Controllers\DocumentController::class);

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
    Route::post('user-roles/{userRole}/permissions', [UserRoleController::class, 'updatePermissions'])->name('user-roles.permissions.update');
    
    // Users Management
    Route::resource('users', UserController::class);
    
    // Return Trips (Zjazdy)
    Route::resource('return-trips', \App\Http\Controllers\ReturnTripController::class);
    
    // Equipment
    Route::resource('equipment', \App\Http\Controllers\EquipmentController::class);
    Route::resource('equipment-issues', \App\Http\Controllers\EquipmentIssueController::class);
    Route::get('equipment-issues/{equipmentIssue}/return', [\App\Http\Controllers\EquipmentIssueController::class, 'returnForm'])->name('equipment-issues.return');
    Route::post('equipment-issues/{equipmentIssue}/return', [\App\Http\Controllers\EquipmentIssueController::class, 'return'])->name('equipment-issues.return.store');
    
    // Transport Costs
    Route::resource('transport-costs', \App\Http\Controllers\TransportCostController::class);
    
    // Time Logs
    Route::resource('time-logs', \App\Http\Controllers\TimeLogController::class);
    Route::get('assignments/{assignment}/time-logs', [\App\Http\Controllers\TimeLogController::class, 'byAssignment'])->name('assignments.time-logs');
});

// Route for users without roles (must be outside role.required middleware)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/no-role', function () {
        return view('no-role');
    })->name('no-role');
});

require __DIR__.'/auth.php';
