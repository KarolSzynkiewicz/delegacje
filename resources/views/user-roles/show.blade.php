<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Rola: {{ $userRole->name }}</h2>
            <div>
                <a href="{{ route('user-roles.edit', $userRole) }}" class="bg-yellow-500 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded mr-2">Edytuj</a>
                <a href="{{ route('user-roles.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">Powrót</a>
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
                    <h3 class="text-lg font-semibold mb-4">Informacje o roli</h3>
                    <dl class="space-y-2">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Nazwa</dt>
                            <dd class="text-sm text-gray-900">{{ $userRole->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Slug</dt>
                            <dd class="text-sm text-gray-900">{{ $userRole->slug }}</dd>
                        </div>
                        @if($userRole->description)
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Opis</dt>
                                <dd class="text-sm text-gray-900">{{ $userRole->description }}</dd>
                            </div>
                        @endif
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Liczba uprawnień</dt>
                            <dd class="text-sm text-gray-900">{{ $userRole->permissions->count() }}</dd>
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
                <h3 class="text-lg font-semibold mb-4">Uprawnienia</h3>
                @if($userRole->permissions->count() > 0)
                    <div class="space-y-4">
                        @foreach($permissions as $model => $modelPermissions)
                            @php
                                $rolePermissionsForModel = $userRole->permissions->where('model', $model);
                            @endphp
                            @if($rolePermissionsForModel->count() > 0)
                                <div class="mb-4 border-b border-gray-200 pb-4 last:border-b-0">
                                    <h4 class="font-semibold text-gray-800 mb-3 capitalize">{{ $model }}</h4>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($rolePermissionsForModel as $permission)
                                            <span class="px-3 py-1 text-sm rounded-full bg-blue-100 text-blue-800">
                                                {{ $permission->action }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500">Brak przypisanych uprawnień</p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
