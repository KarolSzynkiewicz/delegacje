<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Utwórz Zjazd
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

                <form method="POST" action="{{ route('return-trips.prepare-form') }}">
                    @csrf

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Pojazd powrotny</label>
                        <select name="vehicle_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                            <option value="">Brak pojazdu (opcjonalne)</option>
                            @foreach($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}" {{ old('vehicle_id') == $vehicle->id ? 'selected' : '' }}>
                                    {{ $vehicle->registration_number }} - {{ $vehicle->brand }} {{ $vehicle->model }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Pracownicy *</label>
                        <select name="employee_ids[]" multiple required size="10" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                            @foreach($employees as $employee)
                                <option value="{{ $employee->id }}" {{ in_array($employee->id, old('employee_ids', [])) ? 'selected' : '' }}>
                                    {{ $employee->full_name }} 
                                    @if($employee->assignments->count() > 0)
                                        (Projekt: {{ $employee->assignments->first()->project->name ?? '-' }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        <p class="text-sm text-gray-500 mt-1">Przytrzymaj Ctrl/Cmd aby wybrać wielu pracowników</p>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Data zjazdu *</label>
                        <input type="date" name="return_date" value="{{ old('return_date', date('Y-m-d')) }}" required
                            min="{{ date('Y-m-d') }}"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Notatki</label>
                        <textarea name="notes" rows="3" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">{{ old('notes') }}</textarea>
                    </div>

                    <div class="flex items-center justify-end">
                        <a href="{{ route('return-trips.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded mr-3">
                            Anuluj
                        </a>
                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Przygotuj Zjazd
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
