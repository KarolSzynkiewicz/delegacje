<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Użytkownik: {{ $user->name }}">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('users.index') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
            <x-slot name="right">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('users.edit', $user) }}"
                    routeName="users.edit"
                    action="edit"
                >
                    Edytuj
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    @if (session('success'))
        <x-alert type="success" dismissible icon="check-circle">
            {{ session('success') }}
        </x-alert>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <x-ui.card label="Informacje o użytkowniku">
                <div class="row g-3">
                    <div class="col-12">
                        <h6 class="text-muted small mb-1">Nazwa</h6>
                        <p class="fw-semibold mb-0">{{ $user->name }}</p>
                    </div>
                    <div class="col-12">
                        <h6 class="text-muted small mb-1">Email</h6>
                        <p class="fw-semibold mb-0">{{ $user->email }}</p>
                    </div>
                    <div class="col-12">
                        <h6 class="text-muted small mb-1">Data rejestracji</h6>
                        <p class="fw-semibold mb-0">{{ $user->created_at->format('d.m.Y H:i') }}</p>
                    </div>
                </div>
            </x-ui.card>
        </div>

        <div class="col-md-6">
            <x-ui.card label="Role">
                @if($user->roles->count() > 0)
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($user->roles as $role)
                            <x-ui.badge variant="primary">{{ $role->name }}</x-ui.badge>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted small mb-0">Brak przypisanych ról</p>
                @endif
            </x-ui.card>
        </div>
    </div>

    <x-ui.card label="Uprawnienia">
        @if($user->isAdmin())
            <x-alert type="info" dismissible icon="info-circle" class="mb-3">
                <strong>Administrator</strong> - użytkownik ma <strong>wszystkie uprawnienia</strong> w systemie przez logikę biznesową (nie wymaga przypisanych uprawnień w rolach).
            </x-alert>
        @endif
        
        @php
            $allPermissions = $user->getAllPermissions();
        @endphp
        
        @if($user->isAdmin())
            @php
                $allSystemPermissions = \Spatie\Permission\Models\Permission::all();
            @endphp
            <p class="text-muted small mb-2">
                Administrator ma dostęp do wszystkich <strong>{{ $allSystemPermissions->count() }}</strong> uprawnień w systemie przez logikę biznesową.
            </p>
            @if($allSystemPermissions->count() > 0)
                <div class="d-flex flex-wrap gap-2">
                    @foreach($allSystemPermissions as $permission)
                        <x-ui.badge variant="success">{{ $permission->name }}</x-ui.badge>
                    @endforeach
                </div>
            @endif
        @elseif($allPermissions->count() > 0)
            <div class="mb-2">
                <p class="text-muted small mb-2">
                    Łącznie: <strong>{{ $allPermissions->count() }}</strong> uprawnień
                    @if($user->permissions->count() > 0)
                        ({{ $user->permissions->count() }} bezpośrednio przypisanych, {{ $allPermissions->count() - $user->permissions->count() }} przez role)
                    @else
                        (wszystkie przez role)
                    @endif
                </p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                @foreach($allPermissions as $permission)
                    <x-ui.badge variant="success">{{ $permission->name }}</x-ui.badge>
                @endforeach
            </div>
        @else
            <p class="text-muted small mb-0">Brak przypisanych uprawnień</p>
        @endif
    </x-ui.card>
</x-app-layout>
