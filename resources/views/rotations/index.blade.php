<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Wszystkie Rotacje Pracowników
            </h2>
            <a href="{{ route('rotations.create') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Dodaj Rotację
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if(session('success'))
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                            {{ session('success') }}
                        </div>
                    @endif

                    <!-- Formularz filtrowania -->
                    <div class="mb-6 bg-gray-50 border border-gray-200 rounded-lg p-4">
                        <form method="GET" action="{{ route('rotations.index') }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <!-- Filtrowanie po pracowniku -->
                            <div>
                                <label for="employee_id" class="block text-sm font-medium text-gray-700 mb-1">
                                    Pracownik
                                </label>
                                <select name="employee_id" id="employee_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Wszyscy</option>
                                    @foreach($employees as $employee)
                                        <option value="{{ $employee->id }}" {{ request('employee_id') == $employee->id ? 'selected' : '' }}>
                                            {{ $employee->full_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Filtrowanie po statusie -->
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">
                                    Status
                                </label>
                                <select name="status" id="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Wszystkie</option>
                                    <option value="scheduled" {{ request('status') === 'scheduled' ? 'selected' : '' }}>Zaplanowana</option>
                                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktywna</option>
                                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Zakończona</option>
                                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Anulowana</option>
                                </select>
                            </div>

                            <!-- Filtrowanie po dacie rozpoczęcia (od) -->
                            <div>
                                <label for="start_date_from" class="block text-sm font-medium text-gray-700 mb-1">
                                    Data rozpoczęcia od
                                </label>
                                <input type="date" name="start_date_from" id="start_date_from" value="{{ request('start_date_from') }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>

                            <!-- Filtrowanie po dacie rozpoczęcia (do) -->
                            <div>
                                <label for="start_date_to" class="block text-sm font-medium text-gray-700 mb-1">
                                    Data rozpoczęcia do
                                </label>
                                <input type="date" name="start_date_to" id="start_date_to" value="{{ request('start_date_to') }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>

                            <!-- Filtrowanie po dacie zakończenia (od) -->
                            <div>
                                <label for="end_date_from" class="block text-sm font-medium text-gray-700 mb-1">
                                    Data zakończenia od
                                </label>
                                <input type="date" name="end_date_from" id="end_date_from" value="{{ request('end_date_from') }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>

                            <!-- Filtrowanie po dacie zakończenia (do) -->
                            <div>
                                <label for="end_date_to" class="block text-sm font-medium text-gray-700 mb-1">
                                    Data zakończenia do
                                </label>
                                <input type="date" name="end_date_to" id="end_date_to" value="{{ request('end_date_to') }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>

                            <!-- Przyciski -->
                            <div class="flex items-end gap-2">
                                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                    Filtruj
                                </button>
                                <a href="{{ route('rotations.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                    Wyczyść
                                </a>
                            </div>
                        </form>
                    </div>

                    <!-- Informacja o liczbie wyników -->
                    @if(request()->hasAny(['employee_id', 'status', 'start_date_from', 'start_date_to', 'end_date_from', 'end_date_to']))
                        <div class="mb-4 text-sm text-gray-600">
                            Znaleziono <strong>{{ $rotations->total() }}</strong> rotacji
                            @if(request('employee_id'))
                                dla pracownika: <strong>{{ $employees->find(request('employee_id'))?->full_name }}</strong>
                            @endif
                        </div>
                    @endif

                    @if($rotations->count() > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Pracownik
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Data rozpoczęcia
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Data zakończenia
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Notatki
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Akcje
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($rotations as $rotation)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <a href="{{ route('employees.show', $rotation->employee) }}" 
                                                   class="text-blue-600 hover:text-blue-900 font-medium">
                                                    {{ $rotation->employee->full_name }}
                                                </a>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $rotation->start_date->format('Y-m-d') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $rotation->end_date->format('Y-m-d') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @php
                                                    $status = $rotation->status;
                                                @endphp
                                                @if($status === 'active')
                                                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                                                        Aktywna
                                                    </span>
                                                @elseif($status === 'scheduled')
                                                    <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                                                        Zaplanowana
                                                    </span>
                                                @elseif($status === 'completed')
                                                    <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">
                                                        Zakończona
                                                    </span>
                                                @elseif($status === 'cancelled')
                                                    <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">
                                                        Anulowana
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-500">
                                                {{ $rotation->notes ? Str::limit($rotation->notes, 50) : '-' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <a href="{{ route('employees.rotations.edit', [$rotation->employee, $rotation]) }}" 
                                                   class="text-indigo-600 hover:text-indigo-900 mr-3">
                                                    Edytuj
                                                </a>
                                                <form action="{{ route('employees.rotations.destroy', [$rotation->employee, $rotation]) }}" 
                                                      method="POST" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" 
                                                            class="text-red-600 hover:text-red-900"
                                                            onclick="return confirm('Czy na pewno chcesz usunąć tę rotację?')">
                                                        Usuń
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            {{ $rotations->links() }}
                        </div>
                    @else
                        <div class="text-center py-8">
                            <p class="text-gray-500 mb-4">Brak rotacji w systemie.</p>
                            <a href="{{ route('rotations.create') }}" 
                               class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Dodaj pierwszą rotację
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
