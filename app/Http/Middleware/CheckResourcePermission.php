<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckResourcePermission
{
    /**
     * Routes that should be excluded from permission checking.
     */
    protected array $excludedRoutes = [
        'dashboard',
        'profile.*',
        'no-role',
        'logout',
        'home',
    ];

    /**
     * Map HTTP method and route action to permission action.
     */
    protected function mapActionToPermission(string $httpMethod, ?string $routeAction): ?string
    {
        // Map route action to permission action
        $actionMap = [
            'index' => 'viewAny',
            'show' => 'view',
            'create' => 'create', // For GET create form
            'edit' => 'update',   // For GET edit form
            'store' => 'create',
            'update' => 'update',
            'destroy' => 'delete',
        ];

        // If route action is explicitly mapped, use it
        if ($routeAction && isset($actionMap[$routeAction])) {
            return $actionMap[$routeAction];
        }

        // Fallback: map HTTP method directly
        $methodMap = [
            'GET' => 'viewAny',
            'POST' => 'create',
            'PUT' => 'update',
            'PATCH' => 'update',
            'DELETE' => 'delete',
        ];

        return $methodMap[$httpMethod] ?? null;
    }

    /**
     * Extract resource name from route name.
     */
    protected function extractResourceFromRoute(?string $routeName): ?string
    {
        if (!$routeName) {
            return null;
        }

        // Split route name by dots
        $parts = explode('.', $routeName);
        
        // Remove the last part (action) to get resource
        array_pop($parts);
        
        // Join remaining parts - this handles nested routes
        // e.g., "projects.assignments.index" -> "assignments"
        // e.g., "vehicle-assignments.show" -> "vehicle-assignments"
        $resource = implode('.', $parts);
        
        // Handle special cases for custom route names
        // Maps route resource names to permission resource names
        $customMappings = [
            'project-demands' => 'demands',
            'project-assignments' => 'assignments',
            'employee-documents' => 'employee-documents',
            'return-trips' => 'logistics-events', // return-trips routes use logistics-events permissions
            'equipment-issues' => 'equipment-issues',
            'transport-costs' => 'transport-costs',
            'project-variable-costs' => 'project-variable-costs',
            'fixed-costs' => 'fixed-costs',
            'time-logs' => 'time-logs',
            'employee-rates' => 'employee-rates',
            'user-roles' => 'user-roles',
            'weekly-overview' => 'weekly-overview',
            'dashboard' => 'profitability', // dashboard.profitability uses profitability permissions
        ];

        // Check if we have a custom mapping
        if (isset($customMappings[$resource])) {
            return $customMappings[$resource];
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

        // Extract resource and action
        $resource = $this->extractResourceFromRoute($routeName);
        $routeAction = $this->getRouteAction($routeName);
        $httpMethod = $request->method();

        // If we can't determine resource, allow (might be special route)
        if (!$resource) {
            return $next($request);
        }

        // Map to permission action
        $permissionAction = $this->mapActionToPermission($httpMethod, $routeAction);

        // If we can't determine permission action, allow (might be custom action)
        if (!$permissionAction) {
            return $next($request);
        }

        // Build permission name: {resource}.{action}
        $permissionName = "{$resource}.{$permissionAction}";

        // Special case: weekly-overview routes should check weekly-overview.view
        // (because weekly-overview only has 'view' permission, not 'viewAny' or other actions)
        if ($resource === 'weekly-overview') {
            $permissionName = 'weekly-overview.view';
        }

        // Check if user has permission
        if (!$user->hasPermission($permissionName)) {
            abort(403, 'Brak uprawnień do wykonania tej akcji.');
        }

        return $next($request);
    }
}
