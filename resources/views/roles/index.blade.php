<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Role</h2>
            <a href="{{ route('roles.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Dodaj Rolę</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nazwa</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Opis</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pracownicy</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Akcje</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($roles as $role)
                            <tr>
                                <td class="px-6 py-4 font-medium">{{ $role->name }}</td>
                                <td class="px-6 py-4">{{ $role->description ?? '-' }}</td>
                                <td class="px-6 py-4">{{ $role->employees->count() }}</td>
                                <td class="px-6 py-4">
                                    <a href="{{ route('roles.show', $role) }}" class="text-blue-600 hover:text-blue-900 mr-3">Zobacz</a>
                                    <a href="{{ route('roles.edit', $role) }}" class="text-yellow-600 hover:text-yellow-900 mr-3">Edytuj</a>
                                    <form action="{{ route('roles.destroy', $role) }}" method="POST" class="inline">
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

