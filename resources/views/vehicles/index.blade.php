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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Zdjęcie</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nr Rejestracyjny</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Marka i Model</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stan Techniczny</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pojemność</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Akcje</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($vehicles as $vehicle)
                            <tr>
                                <td class="px-6 py-4">
                                    @if($vehicle->image_path)
                                        <img src="{{ $vehicle->image_url }}" alt="{{ $vehicle->brand }} {{ $vehicle->model }}" class="rounded" style="width: 50px; height: 50px; object-fit: cover;">
                                    @else
                                        <div class="rounded bg-gray-300 flex items-center justify-center" style="width: 50px; height: 50px;">
                                            <span class="text-gray-600 text-xs">{{ substr($vehicle->registration_number, 0, 2) }}</span>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ $vehicle->registration_number }}</td>
                                <td class="px-6 py-4">{{ ($vehicle->brand ?? '') . ' ' . ($vehicle->model ?? '') }}</td>
                                <td class="px-6 py-4">
                                    @php
                                        $conditionLabels = [
                                            'excellent' => 'Doskonały',
                                            'good' => 'Dobry',
                                            'fair' => 'Zadowalający',
                                            'poor' => 'Słaby'
                                        ];
                                        $conditionColors = [
                                            'excellent' => 'green',
                                            'good' => 'blue',
                                            'fair' => 'yellow',
                                            'poor' => 'red'
                                        ];
                                    @endphp
                                    <span class="px-2 py-1 text-xs rounded-full bg-{{ $conditionColors[$vehicle->technical_condition] ?? 'gray' }}-100 text-{{ $conditionColors[$vehicle->technical_condition] ?? 'gray' }}-800">
                                        {{ $conditionLabels[$vehicle->technical_condition] ?? $vehicle->technical_condition }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">{{ $vehicle->capacity ?? '-' }} osób</td>
                                <td class="px-6 py-4">
                                    @if($vehicle->currentAssignment())
                                        <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Zajęty</span>
                                    @else
                                        <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Wolny</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <a href="{{ route('vehicles.show', $vehicle) }}" class="text-blue-600 hover:text-blue-900 mr-3">Zobacz</a>
                                    <a href="{{ route('vehicles.edit', $vehicle) }}" class="text-indigo-600 hover:text-indigo-900">Edytuj</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-4 text-center text-gray-500">Brak pojazdów</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
