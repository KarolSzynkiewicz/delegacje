<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Użytkownik: {{ $user->name }}</h2>
            <div>
                <a href="{{ route('users.edit', $user) }}" class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded mr-2">Edytuj</a>
                <a href="{{ route('users.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">Powrót</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold mb-4">Informacje o użytkowniku</h3>
                    <dl class="space-y-2">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Nazwa</dt>
                            <dd class="text-sm text-gray-900">{{ $user->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Email</dt>
                            <dd class="text-sm text-gray-900">{{ $user->email }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Data rejestracji</dt>
                            <dd class="text-sm text-gray-900">{{ $user->created_at->format('d.m.Y H:i') }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold mb-4">Role</h3>
                    @if($user->roles->count() > 0)
                        <div class="flex flex-wrap gap-2">
                            @foreach($user->roles as $role)
                                <span class="px-3 py-1 text-sm rounded-full bg-blue-100 text-blue-800">
                                    {{ $role->name }}
                                </span>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-500">Brak przypisanych ról</p>
                    @endif
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-4">Uprawnienia</h3>
                @if($user->isAdmin())
                    <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-blue-700">
                                    <strong>Administrator</strong> - użytkownik ma <strong>wszystkie uprawnienia</strong> w systemie przez logikę biznesową (nie wymaga przypisanych uprawnień w rolach).
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
                
                @php
                    // Pobierz wszystkie uprawnienia użytkownika (przez role + bezpośrednie)
                    $allPermissions = $user->getAllPermissions();
                @endphp
                
                @if($user->isAdmin())
                    @php
                        // Dla admina pokaż wszystkie dostępne uprawnienia w systemie
                        $allSystemPermissions = \Spatie\Permission\Models\Permission::all();
                    @endphp
                    <p class="text-sm text-gray-600 mb-2">
                        Administrator ma dostęp do wszystkich <strong>{{ $allSystemPermissions->count() }}</strong> uprawnień w systemie przez logikę biznesową.
                    </p>
                    @if($allSystemPermissions->count() > 0)
                        <div class="flex flex-wrap gap-2">
                            @foreach($allSystemPermissions as $permission)
                                <span class="px-3 py-1 text-sm rounded-full bg-green-100 text-green-800">
                                    {{ $permission->name }}
                                </span>
                            @endforeach
                        </div>
                    @endif
                @elseif($allPermissions->count() > 0)
                    <div class="mb-2">
                        <p class="text-sm text-gray-600 mb-2">
                            Łącznie: <strong>{{ $allPermissions->count() }}</strong> uprawnień
                            @if($user->permissions->count() > 0)
                                ({{ $user->permissions->count() }} bezpośrednio przypisanych, {{ $allPermissions->count() - $user->permissions->count() }} przez role)
                            @else
                                (wszystkie przez role)
                            @endif
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @foreach($allPermissions as $permission)
                            <span class="px-3 py-1 text-sm rounded-full bg-green-100 text-green-800">
                                {{ $permission->name }}
                            </span>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500">Brak przypisanych uprawnień</p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
