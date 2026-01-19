<?php

namespace App\Http\Controllers;

use App\Services\RoutePermissionService;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class UserRoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $userRoles = Role::with('permissions')->orderBy('name')->get();
        return view('user-roles.index', compact('userRoles'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(RoutePermissionService $routePermissionService): View
    {
        // Get permissions from routes instead of database
        $routePermissions = $routePermissionService->getAllPermissionsFromRoutes();
        return view('user-roles.create', compact('routePermissions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, RoutePermissionService $routePermissionService): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:user_roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string', // Permission names instead of IDs
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);

        if (isset($validated['permissions'])) {
            // Get all permissions from routes
            $routePermissions = $routePermissionService->getAllPermissionsFromRoutes();
            
            // Create missing permissions and collect Permission models
            $permissions = collect();
            foreach ($validated['permissions'] as $permissionName) {
                // Find in route permissions to get type
                $routePerm = $routePermissions->firstWhere('name', $permissionName);
                
                if ($routePerm) {
                    // Create permission if it doesn't exist
                    $permission = Permission::firstOrCreate(
                        ['name' => $permissionName, 'guard_name' => 'web'],
                        ['type' => $routePerm['type']]
                    );
                    
                    // Update type if it changed
                    if ($permission->type !== $routePerm['type']) {
                        $permission->update(['type' => $routePerm['type']]);
                    }
                    
                    $permissions->push($permission);
                }
            }
            
            $role->syncPermissions($permissions);
        }

        return redirect()->route('user-roles.index')->with('success', 'Rola została utworzona.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $userRole): View
    {
        $userRole->load(['permissions', 'users']);
        
        return view('user-roles.show', compact('userRole'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $userRole, RoutePermissionService $routePermissionService): View
    {
        // Get permissions from routes instead of database
        $routePermissions = $routePermissionService->getAllPermissionsFromRoutes();
        $userRole->load('permissions');
        
        return view('user-roles.edit', compact('userRole', 'routePermissions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Role $userRole, RoutePermissionService $routePermissionService): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:user_roles,name,' . $userRole->id,
            'permissions' => 'nullable|array',
            'permissions.*' => 'string', // Permission names instead of IDs
        ]);

        $userRole->update([
            'name' => $validated['name'],
        ]);

        if (isset($validated['permissions'])) {
            // Get all permissions from routes
            $routePermissions = $routePermissionService->getAllPermissionsFromRoutes();
            
            // Create missing permissions and collect Permission models
            $permissions = collect();
            foreach ($validated['permissions'] as $permissionName) {
                // Find in route permissions to get type
                $routePerm = $routePermissions->firstWhere('name', $permissionName);
                
                if ($routePerm) {
                    // Create permission if it doesn't exist
                    $permission = Permission::firstOrCreate(
                        ['name' => $permissionName, 'guard_name' => 'web'],
                        ['type' => $routePerm['type']]
                    );
                    
                    // Update type if it changed
                    if ($permission->type !== $routePerm['type']) {
                        $permission->update(['type' => $routePerm['type']]);
                    }
                    
                    $permissions->push($permission);
                }
            }
            
            $userRole->syncPermissions($permissions);
        } else {
            $userRole->syncPermissions([]);
        }

        return redirect()->route('user-roles.show', $userRole)->with('success', 'Rola została zaktualizowana.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $userRole): RedirectResponse
    {
        // Sprawdź czy rola nie jest przypisana do użytkowników
        if ($userRole->users()->count() > 0) {
            return redirect()->route('user-roles.index')
                ->with('error', 'Nie można usunąć roli, która jest przypisana do użytkowników.');
        }

        $userRole->delete();

        return redirect()->route('user-roles.index')->with('success', 'Rola została usunięta.');
    }

    /**
     * Update permissions for a role via AJAX.
     */
    public function updatePermissions(Request $request, Role $userRole, RoutePermissionService $routePermissionService): \Illuminate\Http\JsonResponse
    {
        // Administrator nie może mieć zmienianych uprawnień
        if ($userRole->name === 'administrator') {
            return response()->json([
                'success' => false,
                'message' => 'Nie można zmieniać uprawnień dla roli administrator.'
            ], 403);
        }

        $validated = $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*' => 'string', // Permission names instead of IDs
        ]);

        if (isset($validated['permissions'])) {
            // Get all permissions from routes
            $routePermissions = $routePermissionService->getAllPermissionsFromRoutes();
            
            // Create missing permissions and collect Permission models
            $permissions = collect();
            foreach ($validated['permissions'] as $permissionName) {
                // Find in route permissions to get type
                $routePerm = $routePermissions->firstWhere('name', $permissionName);
                
                if ($routePerm) {
                    // Create permission if it doesn't exist
                    $permission = Permission::firstOrCreate(
                        ['name' => $permissionName, 'guard_name' => 'web'],
                        ['type' => $routePerm['type']]
                    );
                    
                    // Update type if it changed
                    if ($permission->type !== $routePerm['type']) {
                        $permission->update(['type' => $routePerm['type']]);
                    }
                    
                    $permissions->push($permission);
                }
            }
            
            $userRole->syncPermissions($permissions);
        } else {
            $userRole->syncPermissions([]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Uprawnienia zostały zaktualizowane.',
            'count' => isset($validated['permissions']) ? count($validated['permissions']) : 0
        ]);
    }
}
