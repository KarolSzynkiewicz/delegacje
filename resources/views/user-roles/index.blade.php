<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 text-dark mb-0">Role Użytkowników</h2>
            <x-ui.button variant="primary" href="{{ route('user-roles.create') }}">
                <i class="bi bi-plus-circle"></i> Dodaj Rolę
            </x-ui.button>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="container-xxl">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="card shadow-sm border-0">
                <div class="card-body">
                    @if($userRoles->count() > 0)
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th class="text-start">Nazwa</th>
                                        <th class="text-start">Uprawnienia</th>
                                        <th class="text-start">Użytkownicy</th>
                                        <th class="text-start">Akcje</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($userRoles as $userRole)
                                        <tr>
                                            <td class="fw-medium">{{ $userRole->name }}</td>
                                            <td>
                                                @if($userRole->name === 'administrator')
                                                    <span class="badge bg-primary" title="Administrator ma wszystkie uprawnienia przez logikę biznesową">
                                                        Wszystkie ({{ \Spatie\Permission\Models\Permission::count() }})
                                                    </span>
                                                @else
                                                    <span class="badge bg-primary">
                                                        {{ $userRole->permissions->count() }} uprawnień
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-success">
                                                    {{ $userRole->users->count() }} użytkowników
                                                </span>
                                            </td>
                                            <td>
                                                <x-action-buttons
                                                    viewRoute="{{ route('user-roles.show', $userRole->name) }}"
                                                    editRoute="{{ route('user-roles.edit', $userRole->name) }}"
                                                    deleteRoute="{{ route('user-roles.destroy', $userRole) }}"
                                                    deleteMessage="Czy na pewno chcesz usunąć tę rolę?"
                                                />
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                            <p class="text-muted mb-3">Brak ról w systemie.</p>
                            <x-ui.button variant="primary" href="{{ route('user-roles.create') }}">
                                <i class="bi bi-plus-circle"></i> Dodaj pierwszą rolę
                            </x-ui.button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
