<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $equipment->name }}
            </h2>
            <div>
                <a href="{{ route('equipment.edit', $equipment) }}" class="bg-indigo-500 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded mr-2">
                    Edytuj
                </a>
                <a href="{{ route('equipment.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    Powrót
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="mb-6">
                    <h3 class="text-lg font-semibold mb-4">Informacje podstawowe</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500">Nazwa</p>
                            <p class="font-semibold">{{ $equipment->name }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Kategoria</p>
                            <p class="font-semibold">{{ $equipment->category ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Ilość w magazynie</p>
                            <p class="font-semibold">{{ $equipment->quantity_in_stock }} {{ $equipment->unit }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Dostępne</p>
                            <p class="font-semibold">{{ $equipment->available_quantity }} {{ $equipment->unit }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Minimalna ilość</p>
                            <p class="font-semibold">{{ $equipment->min_quantity }} {{ $equipment->unit }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Status</p>
                            @if($equipment->isLowStock())
                                <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Niski stan</span>
                            @else
                                <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">OK</span>
                            @endif
                        </div>
                        @if($equipment->unit_cost)
                        <div>
                            <p class="text-sm text-gray-500">Koszt jednostkowy</p>
                            <p class="font-semibold">{{ number_format($equipment->unit_cost, 2) }} PLN</p>
                        </div>
                        @endif
                        @if($equipment->description)
                        <div class="col-span-2">
                            <p class="text-sm text-gray-500">Opis</p>
                            <p>{{ $equipment->description }}</p>
                        </div>
                        @endif
                    </div>
                </div>

                <div class="mb-6">
                    <h3 class="text-lg font-semibold mb-4">Wymagania dla ról</h3>
                    @if($equipment->requirements->count() > 0)
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rola</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Wymagana ilość</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Obowiązkowe</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($equipment->requirements as $requirement)
                                    <tr>
                                        <td class="px-6 py-4">{{ $requirement->role->name }}</td>
                                        <td class="px-6 py-4">{{ $requirement->required_quantity }} {{ $equipment->unit }}</td>
                                        <td class="px-6 py-4">
                                            @if($requirement->is_mandatory)
                                                <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Tak</span>
                                            @else
                                                <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">Nie</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="text-gray-500">Brak wymagań</p>
                    @endif
                </div>

                <div>
                    <h3 class="text-lg font-semibold mb-4">Ostatnie wydania</h3>
                    @if($equipment->issues->count() > 0)
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pracownik</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ilość</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data wydania</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($equipment->issues->take(10) as $issue)
                                    <tr>
                                        <td class="px-6 py-4">{{ $issue->employee->full_name }}</td>
                                        <td class="px-6 py-4">{{ $issue->quantity_issued }} {{ $equipment->unit }}</td>
                                        <td class="px-6 py-4">{{ $issue->issue_date->format('Y-m-d') }}</td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-1 text-xs rounded-full 
                                                @if($issue->status === 'issued') bg-blue-100 text-blue-800
                                                @elseif($issue->status === 'returned') bg-green-100 text-green-800
                                                @else bg-gray-100 text-gray-800
                                                @endif">
                                                {{ ucfirst($issue->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="text-gray-500">Brak wydań</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
