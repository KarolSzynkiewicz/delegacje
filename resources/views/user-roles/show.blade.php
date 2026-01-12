<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 text-dark mb-0">Rola: {{ $userRole->name }}</h2>
            <x-ui.button variant="ghost" href="{{ route('user-roles.index') }}" class="btn-sm">
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

            @if(session('error'))
                <x-ui.alert variant="danger" dismissible>
                    {{ session('error') }}
                </x-ui.alert>
            @endif

            <!-- Informacje o roli -->
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <x-ui.card>
                        <h3 class="fs-5 fw-semibold mb-3">Informacje o roli</h3>
                        <x-ui.detail-list>
                            <x-ui.detail-item label="Nazwa">
                                {{ $userRole->name }}
                            </x-ui.detail-item>
                            <x-ui.detail-item label="Liczba uprawnień">
                                @if($userRole->name === 'administrator')
                                    <span class="text-primary fw-semibold">Wszystkie ({{ \Spatie\Permission\Models\Permission::count() }})</span>
                                    <small class="d-block text-muted mt-1">Administrator ma wszystkie uprawnienia przez logikę biznesową</small>
                                @else
                                    {{ $userRole->permissions->count() }}
                                @endif
                            </x-ui.detail-item>
                            <x-ui.detail-item label="Liczba użytkowników">
                                {{ $userRole->users->count() }}
                            </x-ui.detail-item>
                        </x-ui.detail-list>
                    </x-ui.card>
                </div>

                <div class="col-md-6">
                    <x-ui.card>
                        <h3 class="fs-5 fw-semibold mb-3">Użytkownicy z tą rolą</h3>
                        @if($userRole->users->count() > 0)
                            <ul class="list-unstyled mb-0">
                                @foreach($userRole->users as $user)
                                    <li class="mb-2">
                                        <i class="bi bi-person-circle me-2"></i>
                                        {{ $user->name }} <small class="text-muted">({{ $user->email }})</small>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-muted mb-0">Brak użytkowników z tą rolą</p>
                        @endif
                    </x-ui.card>
                </div>
            </div>

            <!-- Uprawnienia -->
            <x-ui.card>
                <h3 class="fs-5 fw-semibold mb-4">Uprawnienia</h3>

                @if($userRole->name === 'administrator')
                    <x-ui.alert variant="info">
                        <strong>Rola Administrator</strong> - użytkownicy z tą rolą mają <strong>wszystkie uprawnienia</strong> w systemie przez logikę biznesową Spatie Permission (nie wymaga przypisanych uprawnień w bazie danych).
                    </x-ui.alert>
                @else
                    @php
                        // Grupuj uprawnienia według zasobu
                        $allPermissions = \Spatie\Permission\Models\Permission::orderBy('name')->get();
                        $rolePermissions = $userRole->permissions->pluck('name')->toArray();
                        
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
                                        'checked' => in_array($permission->name, $rolePermissions)
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
                                                    @if($data['permissions'][$action]['checked'])
                                                        <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                                    @else
                                                        <i class="bi bi-circle text-muted"></i>
                                                    @endif
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
                @endif
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
