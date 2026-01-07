<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Rola: {{ $userRole->name }}</h2>
            <div>
                <a href="{{ route('user-roles.edit', $userRole->name) }}" class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded mr-2">Edytuj</a>
                <a href="{{ route('user-roles.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">Powrót</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" id="success-message">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" id="error-message">
                    {{ session('error') }}
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold mb-4">Informacje o roli</h3>
                    <dl class="space-y-2">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Nazwa</dt>
                            <dd class="text-sm text-gray-900">{{ $userRole->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Liczba uprawnień</dt>
                            <dd class="text-sm text-gray-900">
                                @if($userRole->name === 'administrator')
                                    <span class="text-blue-600 font-semibold">Wszystkie ({{ \Spatie\Permission\Models\Permission::count() }})</span>
                                    <span class="text-xs text-gray-500 block mt-1">Administrator ma wszystkie uprawnienia przez logikę biznesową</span>
                                @else
                                    <span id="permission-count">{{ $userRole->permissions->count() }}</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Liczba użytkowników</dt>
                            <dd class="text-sm text-gray-900">{{ $userRole->users->count() }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold mb-4">Użytkownicy z tą rolą</h3>
                    @if($userRole->users->count() > 0)
                        <ul class="space-y-2">
                            @foreach($userRole->users as $user)
                                <li class="text-sm text-gray-900">{{ $user->name }} ({{ $user->email }})</li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-sm text-gray-500">Brak użytkowników z tą rolą</p>
                    @endif
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold">Uprawnienia</h3>
                    @if($userRole->name !== 'administrator')
                        <button type="button" id="save-permissions-btn" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                            Zapisz zmiany
                        </button>
                    @endif
                </div>

                @if($userRole->name === 'administrator')
                    <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700">
                                    <strong>Rola Administrator</strong> - użytkownicy z tą rolą mają <strong>wszystkie uprawnienia</strong> w systemie przez logikę biznesową (nie wymaga przypisanych uprawnień).
                                </p>
                            </div>
                        </div>
                    </div>
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

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Zasób</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Twórz</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Czytaj</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Aktualizuj</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Usuwaj</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($groupedPermissions as $resource => $data)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $data['name'] }}
                                        </td>
                                        @foreach(['C', 'R', 'U', 'D'] as $action)
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                @if($data['permissions'][$action])
                                                    <input 
                                                        type="checkbox" 
                                                        class="permission-checkbox h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                                        data-permission-id="{{ $data['permissions'][$action]['id'] }}"
                                                        data-permission-name="{{ $data['permissions'][$action]['name'] }}"
                                                        {{ $data['permissions'][$action]['checked'] ? 'checked' : '' }}
                                                    >
                                                @else
                                                    <span class="text-gray-300">-</span>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if($userRole->name !== 'administrator')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const checkboxes = document.querySelectorAll('.permission-checkbox');
                const saveBtn = document.getElementById('save-permissions-btn');
                const roleName = '{{ $userRole->name }}';
                let hasChanges = false;

                // Śledź zmiany
                checkboxes.forEach(checkbox => {
                    checkbox.addEventListener('change', function() {
                        hasChanges = true;
                        saveBtn.disabled = false;
                        saveBtn.classList.remove('disabled:opacity-50', 'disabled:cursor-not-allowed');
                    });
                });

                // Zapisz zmiany
                saveBtn.addEventListener('click', function() {
                    if (!hasChanges) return;

                    const checkedPermissions = Array.from(checkboxes)
                        .filter(cb => cb.checked)
                        .map(cb => cb.dataset.permissionId);

                    saveBtn.disabled = true;
                    saveBtn.textContent = 'Zapisywanie...';

                    fetch(`/user-roles/${roleName}/permissions`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            permissions: checkedPermissions
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Pokaż komunikat sukcesu
                            const successMsg = document.getElementById('success-message') || document.createElement('div');
                            successMsg.id = 'success-message';
                            successMsg.className = 'bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4';
                            successMsg.textContent = 'Uprawnienia zostały zaktualizowane.';
                            
                            const container = document.querySelector('.py-12 > .max-w-7xl');
                            if (!document.getElementById('success-message')) {
                                container.insertBefore(successMsg, container.firstChild);
                            }

                            // Ukryj komunikat po 3 sekundach
                            setTimeout(() => {
                                successMsg.remove();
                            }, 3000);

                            // Zaktualizuj licznik
                            const countElement = document.getElementById('permission-count');
                            if (countElement) {
                                countElement.textContent = checkedPermissions.length;
                            }

                            hasChanges = false;
                            saveBtn.disabled = true;
                            saveBtn.textContent = 'Zapisz zmiany';
                            saveBtn.classList.add('disabled:opacity-50', 'disabled:cursor-not-allowed');
                        } else {
                            throw new Error(data.message || 'Błąd podczas zapisywania');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Wystąpił błąd podczas zapisywania uprawnień: ' + error.message);
                        saveBtn.disabled = false;
                        saveBtn.textContent = 'Zapisz zmiany';
                    });
                });
            });
        </script>
    @endif
</x-app-layout>
