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
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
    });

    // Projects + nested demands + assignments
    Route::resource('projects', ProjectController::class);

    Route::resource('projects.demands', ProjectDemandController::class)
        ->shallow()
        ->names([
            'show' => 'demands.show',
            'edit' => 'demands.edit',
            'update' => 'demands.update',
            'destroy' => 'demands.destroy',
        ]);

    Route::resource('projects.assignments', ProjectAssignmentController::class)
        ->shallow()
        ->names([
            'show' => 'assignments.show',
            'edit' => 'assignments.edit',
            'update' => 'assignments.update',
            'destroy' => 'assignments.destroy',
        ]);

    // Employees + assignments
    Route::resource('employees', EmployeeController::class);

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

    // Vehicles, Accommodations (CRUD)
    Route::resource('vehicles', VehicleController::class);
    Route::resource('accommodations', AccommodationController::class);
});

require __DIR__.'/auth.php';
