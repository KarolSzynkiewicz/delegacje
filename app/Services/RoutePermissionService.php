<?php

namespace App\Services;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Collection;

class RoutePermissionService
{
    /**
     * Get all permissions generated from routes with permission_type.
     * Filters out viewAny - only returns view for UI table.
     */
    public function getAllPermissionsFromRoutes(): Collection
    {
        $routes = Route::getRoutes();
        $permissions = collect();
        
        foreach ($routes as $route) {
            // Access defaults from action array (route groups store defaults in action['defaults'])
            $permissionType = $route->action['defaults']['permission_type'] ?? $route->defaults['permission_type'] ?? null;
            if (!$permissionType) {
                continue;
            }
            
            $routeName = $route->getName();
            if (!$routeName) {
                continue;
            }
            
            // Skip excluded routes (same as middleware)
            if ($this->isExcluded($routeName)) {
                continue;
            }
            
            // Extract resource and action using same logic as middleware
            $resource = $this->extractResourceFromRoute($routeName, $permissionType);
            if (!$resource) {
                continue;
            }
            
            $routeAction = $this->getRouteAction($routeName);
            $httpMethod = $route->methods()[0] ?? 'GET';
            
            // Generate permission name using same logic as middleware
            $permissionName = $this->generatePermissionName(
                $permissionType,
                $resource,
                $routeAction,
                $httpMethod
            );
            
            if (!$permissionName) {
                continue;
            }
            
            // Ensure we never return viewAny - middleware generates view for index/show
            // But for safety, if somehow viewAny appears, replace with view
            if (str_ends_with($permissionName, '.viewAny')) {
                $permissionName = str_replace('.viewAny', '.view', $permissionName);
            }
            
            $permissions->push([
                'name' => $permissionName,
                'type' => $permissionType,
                'resource' => $resource,
                'action' => $this->getPermissionAction($permissionName, $permissionType),
            ]);
        }
        
        // Remove duplicates and return
        return $permissions->unique('name')->values();
    }
    
    /**
     * Generate permission name based on permission type.
     * Uses same logic as CheckResourcePermission middleware.
     */
    protected function generatePermissionName(
        string $permissionType,
        string $resource,
        ?string $routeAction,
        string $httpMethod
    ): ?string {
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
     * Extract resource name from route name.
     * Uses same logic as CheckResourcePermission middleware.
     */
    protected function extractResourceFromRoute(?string $routeName, string $permissionType): ?string
    {
        if (!$routeName) {
            return null;
        }
        
        // Split route name by dots
        $parts = explode('.', $routeName);
        
        if ($permissionType === 'action') {
            // For action routes: return-trips.cancel -> return-trips.cancel
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
     * Get permission action from permission name (for UI grouping).
     */
    protected function getPermissionAction(string $permissionName, string $permissionType): string
    {
        $parts = explode('.', $permissionName);
        return end($parts);
    }
    
    /**
     * Check if route should be excluded from permission generation.
     * Same exclusions as middleware.
     */
    protected function isExcluded(string $routeName): bool
    {
        $excludedRoutes = [
            'profile.*',
            'no-role',
            'logout',
            'home',
        ];
        
        foreach ($excludedRoutes as $pattern) {
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
     * Group permissions by resource for UI display.
     */
    public function groupPermissionsByResource(Collection $permissions): Collection
    {
        return $permissions->groupBy('resource')->map(function ($group, $resource) {
            return [
                'resource' => $resource,
                'type' => $group->first()['type'] ?? 'resource',
                'permissions' => $group->keyBy('action'),
            ];
        });
    }
}
