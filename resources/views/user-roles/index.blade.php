<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Role Użytkowników">
            <x-slot name="right">
                <x-ui.button 
                    variant="primary" 
                    href="{{ route('user-roles.create') }}"
                    routeName="user-roles.create"
                    action="create"
                >
                    Dodaj Rolę
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
        @if($userRoles->count() > 0)
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Nazwa</th>
                            <th>Uprawnienia</th>
                            <th>Użytkownicy</th>
                            <th>Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($userRoles as $userRole)
                            <tr>
                                <td class="fw-medium">{{ $userRole->name }}</td>
                                <td>
                                    @if($userRole->name === 'administrator')
                                        <x-ui.badge variant="primary" title="Administrator ma wszystkie uprawnienia przez logikę biznesową">
                                            Wszystkie ({{ \Spatie\Permission\Models\Permission::count() }})
                                        </x-ui.badge>
                                    @else
                                        <x-ui.badge variant="primary">
                                            {{ $userRole->permissions->count() }} uprawnień
                                        </x-ui.badge>
                                    @endif
                                </td>
                                <td>
                                    <x-ui.badge variant="success">
                                        {{ $userRole->users->count() }} użytkowników
                                    </x-ui.badge>
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
            <x-ui.empty-state 
                icon="inbox" 
                message="Brak ról w systemie."
            >
                <x-ui.button 
                    variant="primary" 
                    href="{{ route('user-roles.create') }}"
                    routeName="user-roles.create"
                    action="create"
                >
                    Dodaj pierwszą rolę
                </x-ui.button>
            </x-ui.empty-state>
        @endif
    </x-ui.card>
</x-app-layout>
