<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Pojazdy</h2>
            <a href="{{ route('vehicles.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Dodaj Pojazd</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nr Rejestracyjny</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Marka i Model</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Akcje</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($vehicles as $vehicle)
                            <tr>
                                <td class="px-6 py-4">{{ $vehicle->registration_number }}</td>
                                <td class="px-6 py-4">{{ $vehicle->brand }} {{ $vehicle->model }}</td>
                                <td class="px-6 py-4">
                                    @if($vehicle->currentAssignment())
                                        <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Zajęty</span>
                                    @else
                                        <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Wolny</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <a href="{{ route('vehicles.show', $vehicle) }}" class="text-blue-600 hover:text-blue-900 mr-3">Zobacz</a>
                                    <a href="{{ route('vehicles.edit', $vehicle) }}" class="text-indigo-600 hover:text-indigo-900">Edytuj</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-gray-500">Brak pojazdów</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
