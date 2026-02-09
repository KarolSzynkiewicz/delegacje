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
        // #region agent log
        $logFile = '/home/karol/delegacje/.cursor/debug.log';
        $routeName = $request->route()?->getName();
        if ($routeName === 'employee-documents.store') {
            file_put_contents($logFile, json_encode([
                'id' => 'log_' . time() . '_middleware_entry',
                'timestamp' => time() * 1000,
                'location' => 'CheckResourcePermission.php:21',
                'message' => 'Middleware entry for employee-documents.store',
                'data' => [
                    'route_name' => $routeName,
                    'uri' => $request->path(),
                    'method' => $request->method(),
                    'user_id' => $request->user()?->id,
                ],
                'runId' => 'run1',
                'hypothesisId' => 'A'
            ]) . "\n", FILE_APPEND);
        }
        // #endregion

        $user = $request->user();

        // If user is not authenticated, let auth middleware handle it
        if (!$user) {
            return $next($request);
        }

        // Admin always has access
        if ($user->isAdmin()) {
            // #region agent log
            if ($routeName === 'employee-documents.store') {
                file_put_contents($logFile, json_encode([
                    'id' => 'log_' . time() . '_admin_bypass',
                    'timestamp' => time() * 1000,
                    'location' => 'CheckResourcePermission.php:32',
                    'message' => 'Admin bypass - allowing access',
                    'data' => ['user_id' => $user->id],
                    'runId' => 'run1',
                    'hypothesisId' => 'A'
                ]) . "\n", FILE_APPEND);
            }
            // #endregion
            return $next($request);
        }

        $route = $request->route();

        // If route has no name, allow (might be closure routes or special cases)
        if (!$route || !$route->getName()) {
            return $next($request);
        }

        // Get permission for route using RoutePermissionService
        $permissionName = $this->routePermissionService->getPermissionForRouteObject($route);

        // #region agent log
        if ($routeName === 'employee-documents.store') {
            $routeDefaults = $route->defaults ?? [];
            $routeActionDefaults = $route->getAction('defaults') ?? [];
            file_put_contents($logFile, json_encode([
                'id' => 'log_' . time() . '_permission_check',
                'timestamp' => time() * 1000,
                'location' => 'CheckResourcePermission.php:43',
                'message' => 'Permission check result',
                'data' => [
                    'permission_name' => $permissionName,
                    'route_defaults' => $routeDefaults,
                    'route_action_defaults' => $routeActionDefaults,
                    'permission_type' => $routeDefaults['permission_type'] ?? $routeActionDefaults['permission_type'] ?? null,
                    'resource' => $routeDefaults['resource'] ?? $routeActionDefaults['resource'] ?? null,
                ],
                'runId' => 'run1',
                'hypothesisId' => 'A,D'
            ]) . "\n", FILE_APPEND);
        }
        // #endregion

        // If we can't determine permission, check if route is excluded
        if (!$permissionName) {
            $routeName = $route->getName();
            
            // If route is excluded from permission checking, allow access
            if ($this->routePermissionService->isExcluded($routeName)) {
                return $next($request);
            }
            
            // Route is not excluded but has no permission - this is an error
            // #region agent log
            if ($routeName === 'employee-documents.store') {
                file_put_contents($logFile, json_encode([
                    'id' => 'log_' . time() . '_no_permission',
                    'timestamp' => time() * 1000,
                    'location' => 'CheckResourcePermission.php:46',
                    'message' => 'No permission determined - will abort',
                    'data' => [
                        'route_name' => $routeName,
                        'is_excluded' => $this->routePermissionService->isExcluded($routeName),
                    ],
                    'runId' => 'run1',
                    'hypothesisId' => 'A,D'
                ]) . "\n", FILE_APPEND);
            }
            // #endregion
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
        // #region agent log
        if ($routeName === 'employee-documents.store') {
            file_put_contents($logFile, json_encode([
                'id' => 'log_' . time() . '_user_permission',
                'timestamp' => time() * 1000,
                'location' => 'CheckResourcePermission.php:67',
                'message' => 'User permission check',
                'data' => [
                    'user_id' => $user->id,
                    'permission_name' => $permissionName,
                    'has_permission' => $hasPermission,
                ],
                'runId' => 'run1',
                'hypothesisId' => 'A'
            ]) . "\n", FILE_APPEND);
        }
        // #endregion
        if (!$hasPermission) {
            abort(403, 'Brak uprawnień do wykonania tej akcji.');
        }

        // #region agent log
        if ($routeName === 'employee-documents.store') {
            file_put_contents($logFile, json_encode([
                'id' => 'log_' . time() . '_middleware_pass',
                'timestamp' => time() * 1000,
                'location' => 'CheckResourcePermission.php:71',
                'message' => 'Middleware passed - allowing request',
                'data' => [],
                'runId' => 'run1',
                'hypothesisId' => 'A'
            ]) . "\n", FILE_APPEND);
        }
        // #endregion

        return $next($request);
    }
}
