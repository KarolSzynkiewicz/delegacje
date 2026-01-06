<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edytuj Rotację dla: {{ $employee->full_name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4">
                <a href="{{ route('employees.rotations.index', $employee) }}" class="text-blue-600 hover:text-blue-900">
                    ← Wróć do listy rotacji
                </a>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('employees.rotations.update', [$employee, $rotation]) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">
                            Pracownik
                        </label>
                        <input type="text" value="{{ $employee->full_name }}" disabled
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 bg-gray-100">
                    </div>

                    <div class="mb-4">
                        <label for="start_date" class="block text-gray-700 text-sm font-bold mb-2">
                            Data rozpoczęcia *
                        </label>
                        <input type="date" 
                               name="start_date" 
                               id="start_date"
                               value="{{ old('start_date', $rotation->start_date->format('Y-m-d')) }}"
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
                               value="{{ old('end_date', $rotation->end_date->format('Y-m-d')) }}"
                               required
                               class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 @error('end_date') border-red-500 @enderror">
                        @error('end_date')
                            <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">
                            Aktualny status
                        </label>
                        <div class="bg-gray-50 border rounded p-3">
                            @php
                                $currentStatus = $rotation->status;
                                $statusLabels = [
                                    'scheduled' => ['Zaplanowana', 'bg-blue-100 text-blue-800'],
                                    'active' => ['Aktywna', 'bg-green-100 text-green-800'],
                                    'completed' => ['Zakończona', 'bg-gray-100 text-gray-800'],
                                    'cancelled' => ['Anulowana', 'bg-red-100 text-red-800'],
                                ];
                                $label = $statusLabels[$currentStatus] ?? ['Nieznany', 'bg-gray-100 text-gray-800'];
                            @endphp
                            <span class="px-3 py-1 rounded-full text-sm font-semibold {{ $label[1] }}">
                                {{ $label[0] }}
                            </span>
                            <p class="text-xs text-gray-600 mt-2">
                                Status jest automatycznie obliczany na podstawie dat. Możesz tylko anulować rotację ręcznie.
                            </p>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="status" class="block text-gray-700 text-sm font-bold mb-2">
                            Anuluj rotację
                        </label>
                        <select name="status" 
                                id="status"
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 @error('status') border-red-500 @enderror">
                            <option value="">Nie anuluj (status automatyczny)</option>
                            <option value="cancelled" {{ old('status', $rotation->getAttributes()['status'] ?? null) === 'cancelled' ? 'selected' : '' }}>
                                Anuluj rotację
                            </option>
                        </select>
                        @error('status')
                            <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="notes" class="block text-gray-700 text-sm font-bold mb-2">
                            Notatki
                        </label>
                        <textarea name="notes" 
                                  id="notes"
                                  rows="4"
                                  class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 @error('notes') border-red-500 @enderror">{{ old('notes', $rotation->notes) }}</textarea>
                        @error('notes')
                            <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-between">
                        <button type="submit" 
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Zaktualizuj
                        </button>
                        <a href="{{ route('employees.rotations.index', $employee) }}" 
                           class="text-gray-600 hover:text-gray-900">
                            Anuluj
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
