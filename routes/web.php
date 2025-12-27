<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectAssignmentController;
use App\Http\Controllers\ProjectDemandController;
use App\Http\Controllers\TimeLogController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\AccommodationController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::redirect('/home', '/dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Resource routes for Locations
    Route::resource('locations', LocationController::class);

    // Resource routes for Projects
    Route::resource('projects', ProjectController::class);

    // Resource routes for Project Demands
    Route::resource('demands', ProjectDemandController::class);

    // Resource routes for Project Assignments (replaces Delegations)
    Route::resource('assignments', ProjectAssignmentController::class);
    Route::get('assignments/project/{project}', [ProjectAssignmentController::class, 'byProject'])
        ->name('assignments.by-project');
    Route::get('assignments/employee/{employee}', [ProjectAssignmentController::class, 'byEmployee'])
        ->name('assignments.by-employee');
    Route::post('assignments/check-availability', [ProjectAssignmentController::class, 'checkAvailability'])
        ->name('assignments.check-availability');

    // Resource routes for Time Logs
    Route::resource('time_logs', TimeLogController::class);

    // Resource routes for Employees
    Route::resource('employees', EmployeeController::class);

    // Resource routes for Accommodations
    Route::resource('accommodations', AccommodationController::class);

    // Resource routes for Vehicles
    Route::resource('vehicles', VehicleController::class);

    // Resource routes for Reports
    Route::resource('reports', ReportController::class);
    Route::get('reports/{id}/download', [ReportController::class, 'download'])->name('reports.download');
});

require __DIR__.'/auth.php';
