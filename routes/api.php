<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Healthcheck endpoint for Railway - NO middleware, NO APP_KEY requirement
// This must be registered BEFORE api middleware group to avoid any middleware
// Explicitly exclude from ALL middleware to ensure fast response
Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'timestamp' => now()->toIso8601String()], 200);
})->withoutMiddleware([
    \Illuminate\Routing\Middleware\ThrottleRequests::class,
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
]);

// Clear permissions cache endpoint (light & fast - protected by simple token)
Route::get('/clear-permissions/{token}', function ($token) {
    // Simple token check
    if ($token !== 'delegate-clear-2024') {
        abort(403, 'Invalid token');
    }
    
    try {
        // Clear only permissions and routes - fast and safe
        \Illuminate\Support\Facades\Artisan::call('permission:cache-reset');
        \Illuminate\Support\Facades\Artisan::call('route:clear');
        
        return response()->json([
            'status' => 'success',
            'message' => 'Permissions and routes cache cleared successfully',
            'timestamp' => now()->toIso8601String(),
            'cleared' => [
                'permissions',
                'routes',
            ],
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
})->withoutMiddleware([
    \Illuminate\Routing\Middleware\ThrottleRequests::class,
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
]);

// Clear cache endpoint - full (protected by simple token)
Route::get('/clear-cache/{token}', function ($token) {
    // Simple token check
    if ($token !== 'delegate-clear-2024') {
        abort(403, 'Invalid token');
    }
    
    try {
        // Clear all caches
        \Illuminate\Support\Facades\Artisan::call('optimize:clear');
        \Illuminate\Support\Facades\Artisan::call('permission:cache-reset');
        \Illuminate\Support\Facades\Artisan::call('view:clear');
        \Illuminate\Support\Facades\Artisan::call('route:clear');
        
        // Clear specific caches
        \Illuminate\Support\Facades\Cache::flush();
        
        return response()->json([
            'status' => 'success',
            'message' => 'All caches cleared successfully',
            'timestamp' => now()->toIso8601String(),
            'cleared' => [
                'optimize',
                'permissions',
                'views',
                'routes',
                'cache',
            ],
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
})->withoutMiddleware([
    \Illuminate\Routing\Middleware\ThrottleRequests::class,
    \Illuminate\Routing\Middleware\SubstituteBindings::class,
]);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
