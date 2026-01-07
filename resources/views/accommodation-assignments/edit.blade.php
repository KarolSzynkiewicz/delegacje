<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edytuj Przypisanie Mieszkania</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                @if ($errors->any())
                    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                        <h4 class="text-red-800 font-semibold mb-2">Wystąpiły błędy:</h4>
                        <ul class="list-disc list-inside text-red-700">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (session('success'))
                    <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                        <p class="text-green-800">{{ session('success') }}</p>
                    </div>
                @endif

                <form method="POST" action="{{ route('accommodation-assignments.update', $accommodationAssignment) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Pracownik</label>
                        <select name="employee_id" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}" {{ old('employee_id', $accommodationAssignment->employee_id) == $emp->id ? 'selected' : '' }}>
                                    {{ $emp->full_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('employee_id')
                            <p class="text-red-500 text-xs italic">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Mieszkanie</label>
                        <select name="accommodation_id" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                            @foreach($accommodations as $acc)
                                <option value="{{ $acc->id }}" {{ old('accommodation_id', $accommodationAssignment->accommodation_id) == $acc->id ? 'selected' : '' }}>
                                    {{ $acc->name }} ({{ $acc->capacity }} miejsc) - {{ $acc->city }}
                                </option>
                            @endforeach
                        </select>
                        @error('accommodation_id')
                            <p class="text-red-500 text-xs italic">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Data Rozpoczęcia</label>
                        <input type="date" name="start_date" value="{{ old('start_date', $accommodationAssignment->start_date->format('Y-m-d')) }}" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                        @error('start_date')
                            <p class="text-red-500 text-xs italic">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Data Zakończenia (opcjonalnie)</label>
                        <input type="date" name="end_date" value="{{ old('end_date', $accommodationAssignment->end_date ? $accommodationAssignment->end_date->format('Y-m-d') : '') }}"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                        @error('end_date')
                            <p class="text-red-500 text-xs italic">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Uwagi</label>
                        <textarea name="notes" rows="3"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">{{ old('notes', $accommodationAssignment->notes) }}</textarea>
                    </div>

                    <div class="flex items-center justify-between">
                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Aktualizuj
                        </button>
                        <a href="{{ route('employees.accommodations.index', $accommodationAssignment->employee_id) }}" class="text-gray-600 hover:text-gray-900">Anuluj</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
