<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Przypisz Dom do Pracownika</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('employees.accommodations.store', $employee) }}">
                    @csrf

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Pracownik</label>
                        <input type="text" value="{{ $employee->full_name }}" disabled
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 bg-gray-100">
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Mieszkanie</label>
                        <select name="accommodation_id" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                            <option value="">Wybierz mieszkanie</option>
                            @foreach($accommodations as $accommodation)
                                <option value="{{ $accommodation->id }}" {{ old('accommodation_id') == $accommodation->id ? 'selected' : '' }}>
                                    {{ $accommodation->name }} ({{ $accommodation->capacity }} miejsc)
                                </option>
                            @endforeach
                        </select>
                        @error('accommodation_id')
                            <p class="text-red-500 text-xs italic">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Data Rozpoczęcia</label>
                        <input type="date" 
                               name="start_date" 
                               value="{{ old('start_date', $dateFrom ?? '') }}" 
                               required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                        @error('start_date')
                            <p class="text-red-500 text-xs italic">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Data Zakończenia (opcjonalnie)</label>
                        <input type="date" 
                               name="end_date" 
                               value="{{ old('end_date', $dateTo ?? '') }}"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                        @error('end_date')
                            <p class="text-red-500 text-xs italic">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Uwagi</label>
                        <textarea name="notes" rows="3"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">{{ old('notes') }}</textarea>
                    </div>

                    <div class="flex items-center justify-between">
                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Zapisz
                        </button>
                        <a href="{{ route('employees.accommodations.index', $employee) }}" class="text-gray-600 hover:text-gray-900">Anuluj</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
