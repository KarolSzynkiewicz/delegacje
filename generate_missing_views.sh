#!/bin/bash

BASE_DIR="/home/ubuntu/delegacje/resources/views"

# 1. Employees Index
cat > "$BASE_DIR/employees/index.blade.php" << 'EOF'
<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Pracownicy</h2>
            <a href="{{ route('employees.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Dodaj Pracownika</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Imię i Nazwisko</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rola</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Akcje</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($employees as $employee)
                            <tr>
                                <td class="px-6 py-4">{{ $employee->full_name }}</td>
                                <td class="px-6 py-4">{{ $employee->role->name ?? '-' }}</td>
                                <td class="px-6 py-4">{{ $employee->email }}</td>
                                <td class="px-6 py-4">
                                    <a href="{{ route('employees.show', $employee) }}" class="text-blue-600 hover:text-blue-900 mr-3">Zobacz</a>
                                    <a href="{{ route('employees.edit', $employee) }}" class="text-indigo-600 hover:text-indigo-900">Edytuj</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-gray-500">Brak pracowników</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
EOF

# 2. Vehicles Index
cat > "$BASE_DIR/vehicles/index.blade.php" << 'EOF'
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
EOF

# 3. Accommodations Index
cat > "$BASE_DIR/accommodations/index.blade.php" << 'EOF'
<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Mieszkania</h2>
            <a href="{{ route('accommodations.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Dodaj Mieszkanie</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nazwa</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Adres</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pojemność</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Akcje</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($accommodations as $accommodation)
                            <tr>
                                <td class="px-6 py-4">{{ $accommodation->name }}</td>
                                <td class="px-6 py-4">{{ $accommodation->address }}, {{ $accommodation->city }}</td>
                                <td class="px-6 py-4">{{ $accommodation->currentAssignments()->count() }} / {{ $accommodation->capacity }}</td>
                                <td class="px-6 py-4">
                                    <a href="{{ route('accommodations.show', $accommodation) }}" class="text-blue-600 hover:text-blue-900 mr-3">Zobacz</a>
                                    <a href="{{ route('accommodations.edit', $accommodation) }}" class="text-indigo-600 hover:text-indigo-900">Edytuj</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-gray-500">Brak mieszkań</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
EOF

# 4. Vehicle Assignments Index
cat > "$BASE_DIR/vehicle-assignments/index.blade.php" << 'EOF'
<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Przypisania Pojazdów</h2>
            <a href="{{ route('vehicle-assignments.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Dodaj Przypisanie</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pracownik</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pojazd</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Od - Do</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Akcje</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($assignments as $assignment)
                            <tr>
                                <td class="px-6 py-4">{{ $assignment->employee->full_name }}</td>
                                <td class="px-6 py-4">{{ $assignment->vehicle->registration_number }}</td>
                                <td class="px-6 py-4">{{ $assignment->start_date->format('Y-m-d') }} - {{ $assignment->end_date ? $assignment->end_date->format('Y-m-d') : 'Bieżące' }}</td>
                                <td class="px-6 py-4">
                                    <a href="{{ route('vehicle-assignments.show', $assignment) }}" class="text-blue-600 hover:text-blue-900 mr-3">Zobacz</a>
                                    <form action="{{ route('vehicle-assignments.destroy', $assignment) }}" method="POST" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Czy na pewno?')">Usuń</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-gray-500">Brak przypisań pojazdów</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
EOF

# 5. Accommodation Assignments Index
cat > "$BASE_DIR/accommodation-assignments/index.blade.php" << 'EOF'
<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Przypisania Mieszkań</h2>
            <a href="{{ route('accommodation-assignments.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Dodaj Przypisanie</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pracownik</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mieszkanie</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Od - Do</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Akcje</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($assignments as $assignment)
                            <tr>
                                <td class="px-6 py-4">{{ $assignment->employee->full_name }}</td>
                                <td class="px-6 py-4">{{ $assignment->accommodation->name }}</td>
                                <td class="px-6 py-4">{{ $assignment->start_date->format('Y-m-d') }} - {{ $assignment->end_date ? $assignment->end_date->format('Y-m-d') : 'Bieżące' }}</td>
                                <td class="px-6 py-4">
                                    <a href="{{ route('accommodation-assignments.show', $assignment) }}" class="text-blue-600 hover:text-blue-900 mr-3">Zobacz</a>
                                    <form action="{{ route('accommodation-assignments.destroy', $assignment) }}" method="POST" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Czy na pewno?')">Usuń</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-gray-500">Brak przypisań mieszkań</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
EOF

echo "Brakujące widoki zostały wygenerowane!"
