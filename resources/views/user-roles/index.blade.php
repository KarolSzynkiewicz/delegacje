<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Role Użytkowników</h2>
            <a href="{{ route('user-roles.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Dodaj Rolę</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nazwa</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Uprawnienia</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Użytkownicy</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Akcje</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($userRoles as $userRole)
                            <tr>
                                <td class="px-6 py-4 font-medium">{{ $userRole->name }}</td>
                                <td class="px-6 py-4">
                                    @if($userRole->name === 'administrator')
                                        <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800" title="Administrator ma wszystkie uprawnienia przez logikę biznesową">
                                            Wszystkie ({{ \Spatie\Permission\Models\Permission::count() }})
                                        </span>
                                    @else
                                        <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                                            {{ $userRole->permissions->count() }} uprawnień
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                                        {{ $userRole->users->count() }} użytkowników
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <a href="{{ route('user-roles.show', $userRole->name) }}" class="text-blue-600 hover:text-blue-900 mr-3">Zobacz</a>
                                    <a href="{{ route('user-roles.edit', $userRole->name) }}" class="text-yellow-600 hover:text-yellow-900 mr-3">Edytuj</a>
                                    <form action="{{ route('user-roles.destroy', $userRole) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Czy na pewno chcesz usunąć tę rolę?')">Usuń</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-gray-500">Brak ról</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
