<?php

namespace App\Http\Controllers;

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
        $this->authorize('viewAny', Role::class);
        
        $userRoles = Role::with('permissions')->orderBy('name')->get();
        return view('user-roles.index', compact('userRoles'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $this->authorize('create', Role::class);
        
        $permissions = Permission::orderBy('name')->get();
        return view('user-roles.create', compact('permissions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Role::class);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:user_roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);

        if (isset($validated['permissions'])) {
            $permissions = Permission::whereIn('id', $validated['permissions'])->get();
            $role->syncPermissions($permissions);
        }

        return redirect()->route('user-roles.index')->with('success', 'Rola została utworzona.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $userRole): View
    {
        $this->authorize('view', $userRole);
        
        $userRole->load(['permissions', 'users']);
        
        return view('user-roles.show', compact('userRole'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $userRole): View
    {
        $this->authorize('update', $userRole);
        
        $permissions = Permission::orderBy('name')->get();
        $userRole->load('permissions');
        
        return view('user-roles.edit', compact('userRole', 'permissions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Role $userRole): RedirectResponse
    {
        $this->authorize('update', $userRole);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:user_roles,name,' . $userRole->id,
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $userRole->update([
            'name' => $validated['name'],
        ]);

        if (isset($validated['permissions'])) {
            $permissions = Permission::whereIn('id', $validated['permissions'])->get();
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
        $this->authorize('delete', $userRole);
        
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
        $this->authorize('update', $userRole);

        // Administrator nie może mieć zmienianych uprawnień
        if ($userRole->name === 'administrator') {
            return response()->json([
                'success' => false,
                'message' => 'Nie można zmieniać uprawnień dla roli administrator.'
            ], 403);
        }

        $validated = $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $permissions = isset($validated['permissions']) 
            ? Permission::whereIn('id', $validated['permissions'])->get()
            : collect();

        $userRole->syncPermissions($permissions);

        return response()->json([
            'success' => true,
            'message' => 'Uprawnienia zostały zaktualizowane.',
            'count' => $permissions->count()
        ]);
    }
}
