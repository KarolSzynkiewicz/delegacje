<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use App\Support\ProfileDevices;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return view('users.index');
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
        $user->load(['roles', 'permissions', 'managedProjects', 'officeProfile', 'employee']);

        $permissionSource = $user->isAdmin()
            ? Permission::query()->orderBy('name')->get()
            : $user->getAllPermissions()->sortBy('name')->values();

        $permissionGroups = $this->groupPermissions($permissionSource);
        $sessions = ProfileDevices::sessions($user, includeCurrentFallback: false);
        $pushDevices = ProfileDevices::pushDevices($user);

        return view('users.show', compact(
            'user',
            'permissionGroups',
            'sessions',
            'pushDevices',
        ));
    }

    /**
     * @param  Collection<int, Permission>  $permissions
     * @return Collection<int, array{key: string, label: string, actions: Collection<int, string>, count: int}>
     */
    private function groupPermissions(Collection $permissions): Collection
    {
        return $permissions
            ->groupBy(function (Permission $permission): string {
                $parts = explode('.', $permission->name);

                return count($parts) > 1
                    ? implode('.', array_slice($parts, 0, -1))
                    : $permission->name;
            })
            ->map(function (Collection $group, string $module): array {
                $actions = $group
                    ->map(function (Permission $permission) use ($module): string {
                        if ($permission->name === $module) {
                            return 'dostęp';
                        }

                        return Str::afterLast($permission->name, '.');
                    })
                    ->unique()
                    ->sort()
                    ->values();

                return [
                    'key' => $module,
                    'label' => Str::of($module)->replace(['-', '_', '.'], ' ')->title()->toString(),
                    'actions' => $actions,
                    'count' => $group->count(),
                ];
            })
            ->sortBy('label')
            ->values();
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user): View
    {

        $roles = Role::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $user->load(['roles', 'managedProjects']);

        return view('users.edit', compact('user', 'roles', 'projects'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user): RedirectResponse
    {

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'roles' => 'nullable|array',
            'roles.*' => 'exists:user_roles,id',
            'projects' => 'nullable|array',
            'projects.*' => 'exists:projects,id',
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

        // Sync managed projects
        if (isset($validated['projects'])) {
            $user->managedProjects()->sync($validated['projects']);
        } else {
            $user->managedProjects()->sync([]);
        }

        // Clear menu cache for this user since roles or projects changed
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

        return redirect()->route('users.index')->with('success', 'Użytkownik został dezaktywowany.');
    }
}
