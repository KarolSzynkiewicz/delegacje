<?php

namespace App\Http\Controllers;

use App\Services\RoutePermissionService;
use App\Services\MenuService;
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
    public function create(): View
    {
        // Pobierz WSZYSTKIE uprawnienia z bazy (Spatie) zamiast z routes
        $allPermissions = \Spatie\Permission\Models\Permission::orderBy('name')->get();
        
        return view('user-roles.create', compact('allPermissions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:user_roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name', // Musi istnieć w bazie!
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);

        // Synchronizuj uprawnienia - bez firstOrCreate
        if (isset($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        // Clear menu cache for all users since permissions changed
        app(MenuService::class)->clearMenuCache();

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
    public function edit(Role $userRole): View
    {
        // Pobierz WSZYSTKIE uprawnienia z bazy (Spatie) zamiast z routes
        $allPermissions = \Spatie\Permission\Models\Permission::orderBy('name')->get();
        $userRole->load('permissions');
        
        return view('user-roles.edit', compact('userRole', 'allPermissions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Role $userRole): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:user_roles,name,' . $userRole->id,
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,name', // Musi istnieć w bazie!
        ]);

        $userRole->update([
            'name' => $validated['name'],
        ]);

        // Synchronizuj uprawnienia - bez firstOrCreate
        if (isset($validated['permissions'])) {
            $userRole->syncPermissions($validated['permissions']);
        } else {
            $userRole->syncPermissions([]);
        }

        // Clear menu cache for all users since permissions changed
        app(MenuService::class)->clearMenuCache();

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
    public function updatePermissions(Request $request, Role $userRole): \Illuminate\Http\JsonResponse
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
            'permissions.*' => 'exists:permissions,name', // Musi istnieć w bazie!
        ]);

        // Synchronizuj uprawnienia - bez firstOrCreate
        if (isset($validated['permissions'])) {
            $userRole->syncPermissions($validated['permissions']);
        } else {
            $userRole->syncPermissions([]);
        }

        // Clear menu cache for all users since permissions changed
        app(MenuService::class)->clearMenuCache();

        return response()->json([
            'success' => true,
            'message' => 'Uprawnienia zostały zaktualizowane.',
            'count' => isset($validated['permissions']) ? count($validated['permissions']) : 0
        ]);
    }
}
