<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $this->authorize('viewAny', Role::class);
        
        $roles = Role::orderBy('name')->get();
        return view('roles.index', compact('roles'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $this->authorize('create', Role::class);
        
        return view('roles.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $this->authorize('create', Role::class);
        
        Role::create($request->validated());

        return redirect()->route('roles.index')->with('success', 'Rola została dodana.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role): View
    {
        $this->authorize('view', $role);
        
        $role->load('employees', 'projectDemands', 'projectAssignments');
        return view('roles.show', compact('role'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $role): View
    {
        $this->authorize('update', $role);
        
        return view('roles.edit', compact('role'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $this->authorize('update', $role);
        
        $role->update($request->validated());

        return redirect()->route('roles.index')->with('success', 'Rola została zaktualizowana.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role): RedirectResponse
    {
        $this->authorize('delete', $role);
        
        $role->delete();

        return redirect()->route('roles.index')->with('success', 'Rola została usunięta.');
    }
}
