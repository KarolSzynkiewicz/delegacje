<?php

namespace App\Http\Controllers;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        
        $users = User::with('roles')->orderBy('name')->paginate(15);
        return view('users.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        
        $roles = Role::orderBy('name')->get();
        return view('users.create', compact('roles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'roles' => 'nullable|array',
            'roles.*' => 'exists:user_roles,id',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        if (isset($validated['roles'])) {
            $roles = Role::whereIn('id', $validated['roles'])->get();
            $user->syncRoles($roles);
        }

        // Clear menu cache for this user since roles changed
        app(\App\Services\MenuService::class)->clearMenuCacheForUser($user->id);

        return redirect()->route('users.index')->with('success', 'Użytkownik został utworzony.');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user): View
    {
        
        $user->load(['roles', 'permissions']);
        return view('users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user): View
    {
        
        $roles = Role::orderBy('name')->get();
        $user->load('roles');
        
        return view('users.edit', compact('user', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'roles' => 'nullable|array',
            'roles.*' => 'exists:user_roles,id',
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if (isset($validated['roles'])) {
            $roles = Role::whereIn('id', $validated['roles'])->get();
            $user->syncRoles($roles);
        } else {
            $user->syncRoles([]);
        }

        // Clear menu cache for this user since roles changed
        app(\App\Services\MenuService::class)->clearMenuCacheForUser($user->id);

        return redirect()->route('users.show', $user)->with('success', 'Użytkownik został zaktualizowany.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user): RedirectResponse
    {
        
        // Nie można usunąć samego siebie
        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')
                ->with('error', 'Nie można usunąć własnego konta.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Użytkownik został usunięty.');
    }
}
