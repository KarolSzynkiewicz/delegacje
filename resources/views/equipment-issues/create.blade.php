<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Wydaj Sprzęt
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                @if ($errors->any())
                    <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('equipment-issues.store') }}">
                    @csrf

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Sprzęt *</label>
                        <select name="equipment_id" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                            <option value="">Wybierz sprzęt</option>
                            @foreach($equipment as $item)
                                <option value="{{ $item->id }}" {{ old('equipment_id') == $item->id ? 'selected' : '' }}>
                                    {{ $item->name }} (dostępne: {{ $item->available_quantity }} {{ $item->unit }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Pracownik *</label>
                        <select name="employee_id" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                            <option value="">Wybierz pracownika</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" {{ old('employee_id') == $employee->id ? 'selected' : '' }}>
                                    {{ $employee->full_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Przypisanie do projektu (opcjonalne)</label>
                        <select name="project_assignment_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                            <option value="">Brak</option>
                            @foreach($assignments as $assignment)
                                <option value="{{ $assignment->id }}" {{ old('project_assignment_id') == $assignment->id ? 'selected' : '' }}>
                                    {{ $assignment->employee->full_name }} - {{ $assignment->project->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Ilość *</label>
                        <input type="number" name="quantity_issued" value="{{ old('quantity_issued', 1) }}" min="1" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Data wydania *</label>
                        <input type="date" name="issue_date" value="{{ old('issue_date', date('Y-m-d')) }}" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Oczekiwana data zwrotu</label>
                        <input type="date" name="expected_return_date" value="{{ old('expected_return_date') }}"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Notatki</label>
                        <textarea name="notes" rows="3" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">{{ old('notes') }}</textarea>
                    </div>

                    <div class="flex items-center justify-end">
                        <a href="{{ route('equipment-issues.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded mr-3">
                            Anuluj
                        </a>
                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Wydaj Sprzęt
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
