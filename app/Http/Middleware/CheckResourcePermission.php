<?php

namespace App\Http\Middleware;

use App\Services\RoutePermissionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckResourcePermission
{
    public function __construct(
        protected RoutePermissionService $routePermissionService
    ) {
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $routeName = $request->route()?->getName();
        $user = $request->user();

        // If user is not authenticated, let auth middleware handle it
        if (!$user) {
            return $next($request);
        }

        // Admin always has access
        if ($user->isAdmin()) {
            Log::info('CheckResourcePermission: Admin bypass', [
                'route' => $routeName,
                'user_id' => $user->id,
            ]);
            return $next($request);
        }

        $route = $request->route();

        // If route has no name, allow (might be closure routes or special cases)
        if (!$route || !$route->getName()) {
            return $next($request);
        }

        // Get permission for route using RoutePermissionService
        $permissionName = $this->routePermissionService->getPermissionForRouteObject($route);

        Log::info('CheckResourcePermission: Permission check', [
            'route' => $routeName,
            'permission' => $permissionName,
            'user_id' => $user->id,
        ]);

        // If we can't determine permission, check if route is excluded
        if (!$permissionName) {
            $routeName = $route->getName();
            
            // If route is excluded from permission checking, allow access
            if ($this->routePermissionService->isExcluded($routeName)) {
                return $next($request);
            }
            
            // Route is not excluded but has no permission - this is an error
            if (app()->environment('local', 'testing')) {
                throw new \Exception("Cannot determine permission for route: {$routeName}. Route must have permission_type and resource in defaults.");
            }
            Log::error("Cannot determine permission for route", [
                'route' => $routeName,
                'uri' => $request->path(),
                'method' => $request->method(),
            ]);
            abort(500, 'Route configuration error');
        }

        // Check if user has permission
        $hasPermission = $user->hasPermission($permissionName);
        
        if (!$hasPermission) {
            Log::warning('CheckResourcePermission: Permission denied', [
                'route' => $routeName,
                'permission' => $permissionName,
                'user_id' => $user->id,
            ]);
            abort(403, 'Brak uprawnień do wykonania tej akcji.');
        }

        Log::info('CheckResourcePermission: Access granted', [
            'route' => $routeName,
            'permission' => $permissionName,
            'user_id' => $user->id,
        ]);

        return $next($request);
    }
}
