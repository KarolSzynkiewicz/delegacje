<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\DelegationController;
use App\Http\Controllers\TimeLogController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\AccommodationController;
use App\Http\Controllers\VehicleController;

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
});

// Resource routes for Locations
Route::resource('locations', LocationController::class);

// Resource routes for Projects
Route::resource('projects', ProjectController::class);

// Resource routes for Delegations
Route::resource('delegations', DelegationController::class);

// Resource routes for Time Logs
Route::resource('time_logs', TimeLogController::class);

// Resource routes for Employees
Route::resource('employees', EmployeeController::class);

// Resource routes for Accommodations
Route::resource('accommodations', AccommodationController::class);

// Resource routes for Vehicles
Route::resource('vehicles', VehicleController::class);
