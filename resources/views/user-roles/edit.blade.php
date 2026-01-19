<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Edytuj Rolę: {{ $userRole->name }}">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('user-roles.show', $userRole) }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    @if(session('success'))
        <x-alert type="success" dismissible icon="check-circle">
            {{ session('success') }}
        </x-alert>
    @endif

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <x-ui.card label="Edytuj Rolę">
                <x-ui.errors />
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
                            // Get selected permissions by name (from route permissions)
                            $selectedPermissions = old('permissions', $userRole->permissions->pluck('name')->toArray());
                            
                            // Get permissions from routes (already filtered to exclude viewAny)
                            $allPermissions = $routePermissions;
                            
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
                                'dashboard' => 'Dashboard',
                                'weekly-overview' => 'Planer tygodniowy',
                                'profitability' => 'Dashboard rentowności',
                                'user-roles' => 'Role użytkowników',
                                'users' => 'Użytkownicy',
                                'logistics-events' => 'Zdarzenia logistyczne',
                                'equipment' => 'Sprzęt',
                                'equipment-issues' => 'Wydania sprzętu',
                                'transport-costs' => 'Koszty transportu',
                                'time-logs' => 'Ewidencje godzin',
                                'adjustments' => 'Kary i nagrody',
                                'advances' => 'Zaliczki',
                                'documents' => 'Dokumenty',
                                'employee-documents' => 'Dokumenty pracowników',
                                'employee-rates' => 'Stawki pracowników',
                                'fixed-costs' => 'Koszty stałe',
                                'payrolls' => 'Payroll',
                                'project-variable-costs' => 'Koszty zmienne projektów',
                                'rotations' => 'Rotacje',
                                // Action resources
                                'return-trips.prepare' => 'Przygotowanie zjazdu',
                                'return-trips.cancel' => 'Anulowanie zjazdu',
                                'equipment-issues.return' => 'Zwrot sprzętu',
                                'time-logs.monthly-grid' => 'Siatka miesięczna ewidencji',
                                'time-logs.bulk-update' => 'Masowa aktualizacja ewidencji',
                                'payrolls.generate-batch' => 'Generowanie batcha payrolli',
                                'payrolls.recalculate-all' => 'Przeliczanie wszystkich payrolli',
                                'payrolls.recalculate' => 'Przeliczanie payrolla',
                                'assignments.time-logs' => 'Ewidencje godzin przypisania',
                            ];
                            
                            $groupedPermissions = [];
                            foreach ($allPermissions as $permission) {
                                // Permission is now an array from RoutePermissionService
                                $permissionName = $permission['name'];
                                $type = $permission['type'];
                                $resource = $permission['resource'];
                                $action = $permission['action'];
                                
                                // Skip viewAny - should not appear, but filter just in case
                                if (str_ends_with($permissionName, '.viewAny')) {
                                    continue;
                                }
                                
                                if (!isset($groupedPermissions[$resource])) {
                                    $resourceName = $resourceNames[$resource] ?? ucfirst(str_replace('-', ' ', $resource));
                                    $groupedPermissions[$resource] = [
                                        'name' => $resourceName,
                                        'type' => $type,
                                        'permissions' => ['C' => null, 'R' => null, 'U' => null, 'D' => null]
                                    ];
                                }
                                
                                // Map actions to CRUD columns based on type
                                $crud = null;
                                if ($type === 'view') {
                                    // VIEW: only .view -> R (Czytaj)
                                    if ($action === 'view') {
                                        $crud = 'R';
                                    }
                                } elseif ($type === 'action') {
                                    // ACTION: only .update -> U (Edycja)
                                    if ($action === 'update') {
                                        $crud = 'U';
                                    }
                                } else {
                                    // RESOURCE: full CRUD
                                    $crudMap = [
                                        'view' => 'R',
                                        'create' => 'C',
                                        'update' => 'U',
                                        'delete' => 'D',
                                    ];
                                    $crud = $crudMap[$action] ?? null;
                                }
                                
                                if ($crud) {
                                    $groupedPermissions[$resource]['permissions'][$crud] = [
                                        'name' => $permissionName,
                                        'checked' => in_array($permissionName, $selectedPermissions)
                                    ];
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
                                        @php
                                            $type = $data['type'] ?? 'resource';
                                        @endphp
                                        <tr>
                                            <td class="fw-medium">{{ $data['name'] }}</td>
                                            @foreach(['C', 'R', 'U', 'D'] as $action)
                                                @php
                                                    // Determine if this column should be shown/active based on type
                                                    $shouldShow = false;
                                                    if ($type === 'view' && $action === 'R') {
                                                        $shouldShow = true; // VIEW: only Czytaj
                                                    } elseif ($type === 'action' && $action === 'U') {
                                                        $shouldShow = true; // ACTION: only Edycja
                                                    } elseif ($type === 'resource') {
                                                        $shouldShow = true; // RESOURCE: all columns
                                                    }
                                                @endphp
                                                <td class="text-center {{ !$shouldShow ? 'text-muted' : '' }}">
                                                    @if($data['permissions'][$action] && $shouldShow)
                                                        <div class="form-check d-inline-block">
                                                            <input 
                                                                type="checkbox" 
                                                                name="permissions[]" 
                                                                value="{{ $data['permissions'][$action]['name'] }}"
                                                                id="perm-{{ md5($data['permissions'][$action]['name']) }}"
                                                                {{ $data['permissions'][$action]['checked'] ? 'checked' : '' }}
                                                                class="form-check-input"
                                                            >
                                                            <label class="form-check-label" for="perm-{{ md5($data['permissions'][$action]['name']) }}"></label>
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
                    <x-ui.button 
                        variant="primary" 
                        type="submit"
                        action="save"
                    >
                        Zaktualizuj
                    </x-ui.button>
                    <x-ui.button 
                        variant="ghost" 
                        href="{{ route('user-roles.show', $userRole) }}"
                        action="cancel"
                    >
                        Anuluj
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</div>
</x-app-layout>
