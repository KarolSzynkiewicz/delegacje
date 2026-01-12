<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 text-dark mb-0">Edytuj Rolę: {{ $userRole->name }}</h2>
            <x-ui.button variant="ghost" href="{{ route('user-roles.show', $userRole) }}" class="btn-sm">
                <i class="bi bi-arrow-left"></i> Powrót
            </x-ui.button>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="container-xxl">
            <x-ui.errors :errors="$errors" />

            @if(session('success'))
                <x-ui.alert variant="success" dismissible>
                    {{ session('success') }}
                </x-ui.alert>
            @endif

            <x-ui.card>
                <form method="POST" action="{{ route('user-roles.update', $userRole) }}">
                    @csrf
                    @method('PUT')

                    <!-- Nazwa roli -->
                    <div class="mb-4">
                        <label for="name" class="form-label fw-semibold">
                            Nazwa <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               name="name" 
                               id="name"
                               value="{{ old('name', $userRole->name) }}" 
                               required
                               class="form-control @error('name') is-invalid @enderror">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Uprawnienia - Tabelka CRUD -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold mb-3">Uprawnienia</label>
                        
                        @if($userRole->name === 'administrator')
                            <x-ui.alert variant="info">
                                <strong>Rola Administrator</strong> - użytkownicy z tą rolą mają <strong>wszystkie uprawnienia</strong> w systemie przez logikę biznesową Spatie Permission. Nie można edytować uprawnień dla tej roli.
                            </x-ui.alert>
                        @else
                        @php
                            $selectedPermissions = old('permissions', $userRole->permissions->pluck('id')->toArray());
                            
                            // Grupuj uprawnienia według zasobu
                            $allPermissions = \Spatie\Permission\Models\Permission::orderBy('name')->get();
                            
                            // Mapowanie nazw zasobów na polskie
                            $resourceNames = [
                                'projects' => 'Projekty',
                                'employees' => 'Pracownicy',
                                'vehicles' => 'Pojazdy',
                                'accommodations' => 'Mieszkania',
                                'locations' => 'Lokalizacje',
                                'roles' => 'Role',
                                'assignments' => 'Przypisania projektów',
                                'vehicle-assignments' => 'Przypisania pojazdów',
                                'accommodation-assignments' => 'Przypisania mieszkań',
                                'demands' => 'Zapotrzebowania',
                                'reports' => 'Raporty',
                                'weekly-overview' => 'Planer tygodniowy',
                                'user-roles' => 'Role użytkowników',
                                'users' => 'Użytkownicy',
                            ];
                            
                            $groupedPermissions = [];
                            foreach ($allPermissions as $permission) {
                                // Format: resource.action (np. projects.viewAny)
                                $parts = explode('.', $permission->name);
                                if (count($parts) === 2) {
                                    $resource = $parts[0];
                                    $action = $parts[1];
                                    
                                    // Mapuj akcje na CRUD
                                    $crudMap = [
                                        'viewAny' => 'R',
                                        'view' => 'R',
                                        'create' => 'C',
                                        'update' => 'U',
                                        'delete' => 'D',
                                    ];
                                    
                                    $crud = $crudMap[$action] ?? null;
                                    if ($crud) {
                                        if (!isset($groupedPermissions[$resource])) {
                                            $resourceName = $resourceNames[$resource] ?? ucfirst(str_replace('-', ' ', $resource));
                                            $groupedPermissions[$resource] = [
                                                'name' => $resourceName,
                                                'permissions' => ['C' => null, 'R' => null, 'U' => null, 'D' => null]
                                            ];
                                        }
                                        $groupedPermissions[$resource]['permissions'][$crud] = [
                                            'id' => $permission->id,
                                            'name' => $permission->name,
                                            'checked' => in_array($permission->id, $selectedPermissions)
                                        ];
                                    }
                                }
                            }
                            
                            // Sortuj zasoby alfabetycznie
                            ksort($groupedPermissions);
                        @endphp

                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th class="text-start">Zasób</th>
                                        <th class="text-center">Twórz</th>
                                        <th class="text-center">Czytaj</th>
                                        <th class="text-center">Aktualizuj</th>
                                        <th class="text-center">Usuwaj</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($groupedPermissions as $resource => $data)
                                        <tr>
                                            <td class="fw-medium">{{ $data['name'] }}</td>
                                            @foreach(['C', 'R', 'U', 'D'] as $action)
                                                <td class="text-center">
                                                    @if($data['permissions'][$action])
                                                        <div class="form-check d-inline-block">
                                                            <input 
                                                                type="checkbox" 
                                                                name="permissions[]" 
                                                                value="{{ $data['permissions'][$action]['id'] }}"
                                                                id="perm-{{ $data['permissions'][$action]['id'] }}"
                                                                {{ $data['permissions'][$action]['checked'] ? 'checked' : '' }}
                                                                class="form-check-input"
                                                            >
                                                            <label class="form-check-label" for="perm-{{ $data['permissions'][$action]['id'] }}"></label>
                                                        </div>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        @error('permissions')
                            <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror
                        @endif
                    </div>

                    <!-- Przyciski -->
                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <x-ui.button variant="primary" type="submit">
                            <i class="bi bi-check-circle"></i> Zaktualizuj
                        </x-ui.button>
                        <x-ui.button variant="ghost" href="{{ route('user-roles.show', $userRole) }}">
                            Anuluj
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
