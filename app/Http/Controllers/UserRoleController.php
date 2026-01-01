<?php

namespace App\Http\Controllers;

use App\Models\UserRole;
use App\Models\Permission;
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
        $this->authorize('viewAny', UserRole::class);
        
        $userRoles = UserRole::with('permissions')->orderBy('name')->get();
        return view('user-roles.index', compact('userRoles'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $this->authorize('create', UserRole::class);
        
        $permissions = Permission::orderBy('model')->orderBy('action')->get()->groupBy('model');
        return view('user-roles.create', compact('permissions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', UserRole::class);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:user_roles,name',
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $slug = Str::slug($validated['name']);
        
        $userRole = UserRole::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
        ]);

        if (isset($validated['permissions'])) {
            $userRole->permissions()->sync($validated['permissions']);
        }

        return redirect()->route('user-roles.index')->with('success', 'Rola została utworzona.');
    }

    /**
     * Display the specified resource.
     */
    public function show(UserRole $userRole): View
    {
        $this->authorize('view', $userRole);
        
        $userRole->load(['permissions', 'users']);
        $permissions = Permission::orderBy('model')->orderBy('action')->get()->groupBy('model');
        
        return view('user-roles.show', compact('userRole', 'permissions'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(UserRole $userRole): View
    {
        $this->authorize('update', $userRole);
        
        $permissions = Permission::orderBy('model')->orderBy('action')->get()->groupBy('model');
        $userRole->load('permissions');
        
        return view('user-roles.edit', compact('userRole', 'permissions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, UserRole $userRole): RedirectResponse
    {
        $this->authorize('update', $userRole);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:user_roles,name,' . $userRole->id,
            'description' => 'nullable|string',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $slug = Str::slug($validated['name']);
        
        $userRole->update([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
        ]);

        if (isset($validated['permissions'])) {
            $userRole->permissions()->sync($validated['permissions']);
        } else {
            $userRole->permissions()->detach();
        }

        return redirect()->route('user-roles.show', $userRole)->with('success', 'Rola została zaktualizowana.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UserRole $userRole): RedirectResponse
    {
        $this->authorize('delete', $userRole);
        
        // Sprawdź czy rola nie jest przypisana do użytkowników
        if ($userRole->users()->count() > 0) {
            return redirect()->route('user-roles.index')
                ->with('error', 'Nie można usunąć roli, która jest przypisana do użytkowników.');
        }

        $userRole->permissions()->detach();
        $userRole->delete();

        return redirect()->route('user-roles.index')->with('success', 'Rola została usunięta.');
    }
}
