<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectAssignmentController;
use App\Http\Controllers\ProjectDemandController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\VehicleAssignmentController;
use App\Http\Controllers\AccommodationController;
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

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::redirect('/home', '/dashboard');

Route::middleware('auth')->group(function () {
    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Projects
    Route::resource('projects', ProjectController::class);

    // Project Demands
    Route::resource('demands', ProjectDemandController::class);

    // Project Assignments (Pracownik-Projekt)
    Route::resource('assignments', ProjectAssignmentController::class);
    Route::get('assignments/project/{project}', [ProjectAssignmentController::class, 'byProject'])
        ->name('assignments.by-project');
    Route::get('assignments/employee/{employee}', [ProjectAssignmentController::class, 'byEmployee'])
        ->name('assignments.by-employee');
    Route::post('assignments/check-availability', [ProjectAssignmentController::class, 'checkAvailability'])
        ->name('assignments.check-availability');

    // Employees
    Route::resource('employees', EmployeeController::class);

    // Vehicles
    Route::resource('vehicles', VehicleController::class);

    // Vehicle Assignments (Pracownik-Samochód)
    Route::resource('vehicle-assignments', VehicleAssignmentController::class);

    // Accommodations
    Route::resource('accommodations', AccommodationController::class);

    // Accommodation Assignments (Pracownik-Mieszkanie)
    Route::resource('accommodation-assignments', AccommodationAssignmentController::class);
});

require __DIR__.'/auth.php';
