<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Route model binding for Spatie Role - prefer name over id
        // Laravel converts snake_case to camelCase, so {user_role} uses 'userRole' binding
        Route::bind('userRole', function ($value) {
            // Try name first (for friendly URLs), then id (for backward compatibility)
            $role = Role::where('name', $value)->first();
            if (!$role && is_numeric($value)) {
                $role = Role::find($value);
            }
            if (!$role) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
            }
            return $role;
        });
        
        // Also bind for snake_case parameter name (just in case)
        Route::bind('user_role', function ($value) {
            $role = Role::where('name', $value)->first();
            if (!$role && is_numeric($value)) {
                $role = Role::find($value);
            }
            if (!$role) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
            }
            return $role;
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
