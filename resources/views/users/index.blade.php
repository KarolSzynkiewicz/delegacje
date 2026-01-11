<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 text-dark mb-0">Użytkownicy</h2>
            <a href="{{ route('users.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Dodaj Użytkownika
            </a>
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
                    @if($users->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-start">Nazwa</th>
                                        <th class="text-start">Email</th>
                                        <th class="text-start">Role</th>
                                        <th class="text-start">Akcje</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($users as $user)
                                        <tr>
                                            <td class="fw-medium">{{ $user->name }}</td>
                                            <td class="text-muted small">{{ $user->email }}</td>
                                            <td>
                                                @if($user->roles->count() > 0)
                                                    <div class="d-flex flex-wrap gap-1">
                                                        @foreach($user->roles as $role)
                                                            <span class="badge bg-primary">{{ $role->name }}</span>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <span class="text-muted small">Brak ról</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($user->id !== auth()->id())
                                                    <x-action-buttons
                                                        viewRoute="{{ route('users.show', $user) }}"
                                                        editRoute="{{ route('users.edit', $user) }}"
                                                        deleteRoute="{{ route('users.destroy', $user) }}"
                                                        deleteMessage="Czy na pewno chcesz usunąć tego użytkownika?"
                                                    />
                                                @else
                                                    <x-action-buttons
                                                        viewRoute="{{ route('users.show', $user) }}"
                                                        editRoute="{{ route('users.edit', $user) }}"
                                                    />
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if($users->hasPages())
                            <div class="mt-3">
                                {{ $users->links() }}
                            </div>
                        @endif
                    @else
                        <div class="text-center py-5">
                            <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                            <p class="text-muted mb-3">Brak użytkowników w systemie.</p>
                            <a href="{{ route('users.create') }}" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> Dodaj pierwszego użytkownika
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
