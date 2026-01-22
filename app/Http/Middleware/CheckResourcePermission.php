<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckResourcePermission
{
    /**
     * Routes that should be excluded from permission checking.
     * Only technical routes (login, logout, no-role, profile).
     */
    protected array $excludedRoutes = [
        'profile.*',
        'no-role',
        'logout',
        'home',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // If user is not authenticated, let auth middleware handle it
        if (!$user) {
            return $next($request);
        }

        // Admin always has access
        if ($user->isAdmin()) {
            return $next($request);
        }

        $route = $request->route();
        $routeName = $route?->getName();

        // If route has no name, allow (might be closure routes or special cases)
        if (!$routeName) {
            return $next($request);
        }

        // Check if route is excluded
        if ($this->isExcluded($routeName)) {
            return $next($request);
        }

        // Get permission type from route defaults (check both action['defaults'] and defaults)
        $permissionType = $route->action['defaults']['permission_type'] ?? $route->defaults['permission_type'] ?? null;

        // Fail fast: route must have permission_type
        if (!$permissionType) {
            if (app()->environment('local', 'testing')) {
                throw new \Exception("Route {$routeName} missing permission_type default. All routes must have permission_type set via route group defaults.");
            }
            Log::error("Route missing permission_type", [
                'route' => $routeName,
                'uri' => $request->path(),
                'method' => $request->method(),
            ]);
            abort(500, 'Route configuration error');
        }

        // Get resource from route defaults - REQUIRED, no guessing!
        $resource = $route->action['defaults']['resource'] ?? $route->defaults['resource'] ?? null;

        // If resource is not explicitly set, try to extract from route name (fallback for resource routes)
        if (!$resource) {
        $resource = $this->extractResourceFromRoute($routeName, $permissionType);
        }

        // If we still can't determine resource, fail fast
        if (!$resource) {
            if (app()->environment('local', 'testing')) {
                throw new \Exception("Route {$routeName} missing resource default. All routes must have resource set via ->defaults('resource', '...') or be a standard resource route.");
            }
            Log::error("Cannot determine resource from route", [
                'route' => $routeName,
                'type' => $permissionType,
                'uri' => $request->path(),
            ]);
            abort(500, 'Route configuration error');
        }

        $routeAction = $this->getRouteAction($routeName);
        $httpMethod = $request->method();

        // Map to permission name based on permission type
        $permissionName = $this->mapToPermissionName($permissionType, $resource, $routeAction, $httpMethod);

        if (!$permissionName) {
            if (app()->environment('local', 'testing')) {
                throw new \Exception("Cannot determine permission for route: {$routeName} (type: {$permissionType})");
            }
            Log::error("Cannot determine permission", [
                'route' => $routeName,
                'type' => $permissionType,
                'resource' => $resource,
                'action' => $routeAction,
            ]);
            abort(500, 'Route configuration error');
        }

        // Check if user has permission
        if (!$user->hasPermission($permissionName)) {
            abort(403, 'Brak uprawnień do wykonania tej akcji.');
        }

        return $next($request);
    }

    /**
     * Map to permission name based on permission type.
     */
    protected function mapToPermissionName(string $permissionType, string $resource, ?string $routeAction, string $httpMethod): ?string
    {
        return match ($permissionType) {
            'resource' => $this->mapResourcePermission($resource, $routeAction, $httpMethod),
            'view' => $this->mapViewPermission($resource),
            'action' => $this->mapActionPermission($resource),
            default => null,
        };
    }

    /**
     * Map resource route to permission (CRUD).
     * IMPORTANT: index and show both map to .view (not viewAny and view).
     */
    protected function mapResourcePermission(string $resource, ?string $routeAction, string $httpMethod): string
    {
        $actionMap = [
            'index' => 'view',
            'show' => 'view',
            'create' => 'create',
            'store' => 'create',
            'edit' => 'update',
            'update' => 'update',
            'destroy' => 'delete',
        ];

        // If route action is explicitly mapped, use it
        if ($routeAction && isset($actionMap[$routeAction])) {
            $action = $actionMap[$routeAction];
        } else {
            // Fallback: map HTTP method directly
            $methodMap = [
                'GET' => 'view',
                'POST' => 'create',
                'PUT' => 'update',
                'PATCH' => 'update',
                'DELETE' => 'delete',
            ];
            $action = $methodMap[$httpMethod] ?? 'view';
        }

        return "{$resource}.{$action}";
    }

    /**
     * Map view route to permission (always .view).
     */
    protected function mapViewPermission(string $resource): string
    {
        return "{$resource}.view";
    }

    /**
     * Map action route to permission (always .update).
     */
    protected function mapActionPermission(string $resource): string
    {
        return "{$resource}.update";
    }

    /**
     * Extract resource name from route name (fallback for standard resource routes).
     * This is only used when resource is not explicitly set in route defaults.
     */
    protected function extractResourceFromRoute(?string $routeName, string $permissionType): ?string
    {
        if (!$routeName) {
            return null;
        }

        // Split route name by dots
        $parts = explode('.', $routeName);
        
        if ($permissionType === 'action') {
            // For action routes: return-trips.cancel -> return-trips
            // Remove the last part (action) to get resource
            array_pop($parts);
            $resource = implode('.', $parts);
            return $resource ?: null;
        }
        
        if ($permissionType === 'view') {
            // For view routes: 
            // - "dashboard" -> "dashboard" (no dots, return as-is)
            // - "profitability.index" -> "profitability" (remove action)
            // - "weekly-overview.index" -> "weekly-overview" (remove action)
            if (count($parts) === 1) {
                // No dots - route name is the resource (e.g., "dashboard")
                return $routeName;
            }
            // Has dots - remove last part (action)
            array_pop($parts);
            return implode('.', $parts) ?: null;
        }
        
        // For resource routes: remove the last part (action) to get resource
        array_pop($parts);
        
        // Join remaining parts - this handles nested routes
        // e.g., "projects.assignments.index" -> "assignments"
        // e.g., "vehicle-assignments.show" -> "vehicle-assignments"
        $resource = implode('.', $parts);

        // Special handling for project-assignments -> assignments
        if ($resource === 'project-assignments') {
            return 'assignments';
        }

        // For nested resources, take the last part
        // e.g., "employees.vehicles" -> "vehicles"
        // e.g., "employees.accommodations" -> "accommodations"
        if (str_contains($resource, '.')) {
            $nestedParts = explode('.', $resource);
            $lastPart = end($nestedParts);
            
            // Map nested resources
            $nestedMappings = [
                'vehicles' => 'vehicle-assignments',
                'accommodations' => 'accommodation-assignments',
            ];
            
            return $nestedMappings[$lastPart] ?? $lastPart;
        }

        return $resource ?: null;
    }

    /**
     * Get route action from route name.
     */
    protected function getRouteAction(?string $routeName): ?string
    {
        if (!$routeName) {
            return null;
        }

        $parts = explode('.', $routeName);
        return end($parts);
    }

    /**
     * Check if route should be excluded from permission checking.
     */
    protected function isExcluded(string $routeName): bool
    {
        foreach ($this->excludedRoutes as $pattern) {
            if (str_ends_with($pattern, '.*')) {
                $prefix = rtrim($pattern, '.*');
                if (str_starts_with($routeName, $prefix . '.')) {
                    return true;
                }
            } elseif ($routeName === $pattern) {
                return true;
            }
        }

        return false;
    }
}
