<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Role">
            <x-slot name="right">
                <x-ui.button 
                    variant="primary" 
                    href="{{ route('roles.create') }}"
                    routeName="roles.create"
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

    <x-ui.card>
        @if($roles->count() > 0)
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Nazwa</th>
                            <th>Opis</th>
                            <th>Pracownicy</th>
                            <th>Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($roles as $role)
                            <tr>
                                <td class="fw-medium">{{ $role->name }}</td>
                                <td>{{ $role->description ?? '-' }}</td>
                                <td>{{ $role->employees->count() }}</td>
                                <td>
                                    <x-action-buttons
                                        viewRoute="{{ route('roles.show', $role) }}"
                                        editRoute="{{ route('roles.edit', $role) }}"
                                        deleteRoute="{{ route('roles.destroy', $role) }}"
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
                    href="{{ route('roles.create') }}"
                    routeName="roles.create"
                    action="create"
                >
                    Dodaj pierwszą rolę
                </x-ui.button>
            </x-ui.empty-state>
        @endif
    </x-ui.card>
</x-app-layout>
