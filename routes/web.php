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
        ->shallow(); // index i store z kontekstem projektu, reszta płaska

    Route::resource('projects.assignments', ProjectAssignmentController::class)
        ->shallow();

    // Employees + assignments
    Route::resource('employees', EmployeeController::class);

    Route::resource('employees.vehicles', VehicleAssignmentController::class)
        ->shallow();

    Route::resource('employees.accommodations', AccommodationAssignmentController::class)
        ->shallow();

    // Vehicles, Accommodations (CRUD)
    Route::resource('vehicles', VehicleController::class);
    Route::resource('accommodations', AccommodationController::class);
});

require __DIR__.'/auth.php';
