<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Lokalizacje</h2>
            <a href="{{ route('locations.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Dodaj Lokalizację</a>
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Adres</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Miasto</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kontakt</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Akcje</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($locations as $location)
                            <tr>
                                <td class="px-6 py-4 font-medium">{{ $location->name }}</td>
                                <td class="px-6 py-4">{{ $location->address }}</td>
                                <td class="px-6 py-4">{{ $location->city ?? '-' }}</td>
                                <td class="px-6 py-4">
                                    @if($location->contact_person)
                                        <div>{{ $location->contact_person }}</div>
                                        @if($location->phone)
                                            <div class="text-sm text-gray-500">{{ $location->phone }}</div>
                                        @endif
                                        @if($location->email)
                                            <div class="text-sm text-gray-500">{{ $location->email }}</div>
                                        @endif
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <a href="{{ route('locations.show', $location) }}" class="text-blue-600 hover:text-blue-900 mr-3">Zobacz</a>
                                    <a href="{{ route('locations.edit', $location) }}" class="text-yellow-600 hover:text-yellow-900 mr-3">Edytuj</a>
                                    <form action="{{ route('locations.destroy', $location) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Czy na pewno chcesz usunąć tę lokalizację?')">Usuń</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500">Brak lokalizacji</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>

