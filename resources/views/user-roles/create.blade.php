<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Dodaj Nową Rolę">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('user-roles.index') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <x-ui.card label="Dodaj Nową Rolę">
                <x-ui.errors />
                <form method="POST" action="{{ route('user-roles.store') }}">
                    @csrf

                    <!-- Nazwa roli -->
                    <div class="mb-4">
                        <label for="name" class="form-label fw-semibold">
                            Nazwa <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               name="name" 
                               id="name"
                               value="{{ old('name') }}" 
                               required
                               class="form-control @error('name') is-invalid @enderror"
                               placeholder="np. kierownik, pracownik-biurowy">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Uprawnienia - Tabelka CRUD -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold mb-3">Uprawnienia</label>
                        
                        @php
                            // Pobierz wybrane uprawnienia (z old() po błędzie walidacji)
                            $selectedPermissions = old('permissions', []);
                            
                            // Grupuj uprawnienia z bazy po resource
                            $groupedPermissions = [];
                            
                            foreach ($allPermissions as $permission) {
                                // Parsuj nazwę: projects.view → resource: projects, action: view
                                $parts = explode('.', $permission->name);
                                
                                if (count($parts) !== 2) {
                                    continue; // Skip invalid format
                                }
                                
                                $resource = $parts[0];
                                $action = $parts[1];
                                
                                // Skip viewAny (nie wyświetlamy w UI)
                                if ($action === 'viewAny') {
                                    continue;
                                }
                                
                                // Inicjalizuj grupę jeśli nie istnieje
                                if (!isset($groupedPermissions[$resource])) {
                                    // Generuj polską nazwę z resource name
                                    $resourceLabel = ucfirst(str_replace(['-', '_'], ' ', $resource));
                                    
                                    $groupedPermissions[$resource] = [
                                        'name' => $resourceLabel,
                                        'permissions' => [
                                            'create' => null,
                                            'view' => null,
                                            'update' => null,
                                            'delete' => null
                                        ]
                                    ];
                                }
                                
                                // Dodaj uprawnienie do odpowiedniej kolumny
                                if (in_array($action, ['create', 'view', 'update', 'delete'])) {
                                    $groupedPermissions[$resource]['permissions'][$action] = [
                                        'name' => $permission->name,
                                        'checked' => in_array($permission->name, $selectedPermissions)
                                    ];
                                }
                            }
                            
                            // Sortuj alfabetycznie po nazwie zasobu
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
                                            
                                            @foreach(['create', 'view', 'update', 'delete'] as $action)
                                                <td class="text-center">
                                                    @if($data['permissions'][$action])
                                                        <div class="form-check d-inline-block">
                                                            <input 
                                                                type="checkbox" 
                                                                name="permissions[]" 
                                                                value="{{ $data['permissions'][$action]['name'] }}"
                                                                id="perm-{{ md5($data['permissions'][$action]['name']) }}"
                                                                {{ $data['permissions'][$action]['checked'] ? 'checked' : '' }}
                                                                class="form-check-input"
                                                            >
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
                    </div>

                <!-- Przyciski -->
                <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                    <x-ui.button 
                        variant="primary" 
                        type="submit"
                        action="save"
                    >
                        Zapisz
                    </x-ui.button>
                    <x-ui.button 
                        variant="ghost" 
                        href="{{ route('user-roles.index') }}"
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
