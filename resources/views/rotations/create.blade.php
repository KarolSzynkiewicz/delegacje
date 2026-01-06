<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Dodaj Nową Rotację
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4">
                <a href="{{ route('rotations.index') }}" class="text-blue-600 hover:text-blue-900">
                    ← Wróć do listy rotacji
                </a>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('rotations.store') }}">
                    @csrf

                    <div class="mb-4">
                        <label for="employee_id" class="block text-gray-700 text-sm font-bold mb-2">
                            Pracownik *
                        </label>
                        <select name="employee_id" 
                                id="employee_id"
                                required
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 @error('employee_id') border-red-500 @enderror">
                            <option value="">Wybierz pracownika</option>
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" {{ old('employee_id') == $employee->id ? 'selected' : '' }}>
                                    {{ $employee->full_name }} ({{ $employee->email }})
                                </option>
                            @endforeach
                        </select>
                        @error('employee_id')
                            <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="start_date" class="block text-gray-700 text-sm font-bold mb-2">
                            Data rozpoczęcia *
                        </label>
                        <input type="date" 
                               name="start_date" 
                               id="start_date"
                               value="{{ old('start_date') }}"
                               required
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 @error('start_date') border-red-500 @enderror">
                        @error('start_date')
                            <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="end_date" class="block text-gray-700 text-sm font-bold mb-2">
                            Data zakończenia *
                        </label>
                        <input type="date" 
                               name="end_date" 
                               id="end_date"
                               value="{{ old('end_date') }}"
                               required
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 @error('end_date') border-red-500 @enderror">
                        @error('end_date')
                            <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4 bg-blue-50 border border-blue-200 rounded p-3">
                        <p class="text-sm text-blue-800">
                            <strong>Uwaga:</strong> Status rotacji jest automatycznie określany na podstawie dat:
                        </p>
                        <ul class="text-xs text-blue-700 mt-2 list-disc list-inside">
                            <li><strong>Zaplanowana</strong> - jeśli data rozpoczęcia jest w przyszłości</li>
                            <li><strong>Aktywna</strong> - jeśli trwa obecnie (dzisiaj jest między datą rozpoczęcia a zakończenia)</li>
                            <li><strong>Zakończona</strong> - jeśli data zakończenia jest w przeszłości</li>
                        </ul>
                    </div>

                    <div class="mb-4">
                        <label for="notes" class="block text-gray-700 text-sm font-bold mb-2">
                            Notatki
                        </label>
                        <textarea name="notes" 
                                  id="notes"
                                  rows="4"
                                  class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 @error('notes') border-red-500 @enderror">{{ old('notes') }}</textarea>
                        @error('notes')
                            <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-between">
                        <button type="submit" 
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Zapisz
                        </button>
                        <a href="{{ route('rotations.index') }}" 
                           class="text-gray-600 hover:text-gray-900">
                            Anuluj
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
