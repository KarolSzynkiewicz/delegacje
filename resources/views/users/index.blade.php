<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Użytkownicy">
            <x-slot name="right">
                <x-ui.button 
                    variant="primary" 
                    href="{{ route('users.create') }}"
                    routeName="users.create"
                    action="create"
                >
                    Dodaj Użytkownika
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    @if (session('success'))
        <x-alert type="success" dismissible icon="check-circle">
            {{ session('success') }}
        </x-alert>
    @endif

    @if (session('error'))
        <x-alert type="danger" dismissible icon="exclamation-triangle">
            {{ session('error') }}
        </x-alert>
    @endif

    <x-ui.card>
        @if($users->count() > 0)
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Nazwa</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Akcje</th>
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
                                                <x-ui.badge variant="primary">{{ $role->name }}</x-ui.badge>
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
            <x-ui.empty-state 
                icon="inbox" 
                message="Brak użytkowników w systemie."
            >
                <x-ui.button 
                    variant="primary" 
                    href="{{ route('users.create') }}"
                    routeName="users.create"
                    action="create"
                >
                    Dodaj pierwszego użytkownika
                </x-ui.button>
            </x-ui.empty-state>
        @endif
    </x-ui.card>
</x-app-layout>
