<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Edytuj zapotrzebowanie dla projektu: {{ $demand->project->name }}
            </h2>
            <a href="{{ route('projects.demands.index', $demand->project) }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                Powrót
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form action="{{ route('demands.update', $demand) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
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

                    @if(isset($isDateInPast) && $isDateInPast)
                    <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg" id="past-date-warning">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-yellow-600 mt-0.5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <div class="flex-1">
                                <h4 class="text-sm font-semibold text-yellow-800 mb-1">Uwaga: Data w przeszłości</h4>
                                <p class="text-sm text-yellow-700 mb-2">
                                    Próbujesz edytować zapotrzebowanie dla dat w przeszłości. Czy na pewno chcesz kontynuować?
                                </p>
                                <label class="flex items-center">
                                    <input type="checkbox" id="confirm-past-date" class="rounded border-gray-300 text-yellow-600 focus:ring-yellow-500">
                                    <span class="ml-2 text-sm text-yellow-700">Tak, chcę edytować zapotrzebowanie dla dat w przeszłości</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    @endif

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Rola <span class="text-red-500">*</span></label>
                        <select name="role_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" {{ old('role_id', $demand->role_id) == $role->id ? 'selected' : '' }}>
                                    {{ $role->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('role_id')
                            <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Data od <span class="text-red-500">*</span></label>
                            <input type="date" name="date_from" id="date_from" value="{{ old('date_from', $demand->date_from->format('Y-m-d')) }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            @error('date_from')
                                <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Data do (opcjonalnie)</label>
                            <input type="date" name="date_to" id="date_to" value="{{ old('date_to', $demand->date_to ? $demand->date_to->format('Y-m-d') : '') }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('date_to')
                                <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ilość osób <span class="text-red-500">*</span></label>
                        <input 
                            type="number" 
                            name="required_count" 
                            value="{{ old('required_count', $demand->required_count) }}" 
                            min="0" 
                            step="1"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" 
                            required
                        >
                        <p class="text-xs text-gray-500 mt-1">Ustaw 0 aby usunąć zapotrzebowanie</p>
                        @error('required_count')
                            <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Uwagi (opcjonalnie)</label>
                        <textarea name="notes" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes', $demand->notes) }}</textarea>
                        @error('notes')
                            <p class="text-red-500 text-xs italic mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end items-center mt-6">
                        <a href="{{ route('projects.demands.index', $demand->project) }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded mr-2">
                            Anuluj
                        </a>
                        <button type="submit" id="submit-btn" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Zaktualizuj zapotrzebowanie
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dateFromInput = document.getElementById('date_from');
            const dateToInput = document.getElementById('date_to');
            const form = document.querySelector('form');
            const submitBtn = document.getElementById('submit-btn');
            let pastDateWarning = null;

            function checkDates() {
                const dateFrom = dateFromInput.value;
                const dateTo = dateToInput.value;
                const today = new Date().toISOString().split('T')[0];
                
                const isDateFromPast = dateFrom && dateFrom < today;
                const isDateToPast = dateTo && dateTo < today;
                const isPast = isDateFromPast || isDateToPast;

                // Usuń istniejący warning jeśli jest
                if (pastDateWarning) {
                    pastDateWarning.remove();
                    pastDateWarning = null;
                }

                // Jeśli data jest w przeszłości, dodaj warning
                if (isPast) {
                    const warningDiv = document.createElement('div');
                    warningDiv.className = 'mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg';
                    warningDiv.id = 'past-date-warning-dynamic';
                    warningDiv.innerHTML = `
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-yellow-600 mt-0.5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <div class="flex-1">
                                <h4 class="text-sm font-semibold text-yellow-800 mb-1">Uwaga: Data w przeszłości</h4>
                                <p class="text-sm text-yellow-700 mb-2">
                                    Próbujesz edytować zapotrzebowanie dla dat w przeszłości. Czy na pewno chcesz kontynuować?
                                </p>
                                <label class="flex items-center">
                                    <input type="checkbox" id="confirm-past-date-dynamic" class="rounded border-gray-300 text-yellow-600 focus:ring-yellow-500">
                                    <span class="ml-2 text-sm text-yellow-700">Tak, chcę edytować zapotrzebowanie dla dat w przeszłości</span>
                                </label>
                            </div>
                        </div>
                    `;
                    
                    // Wstaw warning przed datami
                    const dateInputsDiv = dateFromInput.closest('.grid');
                    dateInputsDiv.parentNode.insertBefore(warningDiv, dateInputsDiv.nextSibling);
                    pastDateWarning = warningDiv;
                }
            }

            // Sprawdzaj daty przy zmianie
            if (dateFromInput) {
                dateFromInput.addEventListener('change', checkDates);
            }
            if (dateToInput) {
                dateToInput.addEventListener('change', checkDates);
            }

            // Blokuj submit jeśli data w przeszłości i nie potwierdzono
            if (form) {
                form.addEventListener('submit', function(e) {
                    const confirmCheckbox = document.getElementById('confirm-past-date');
                    const confirmCheckboxDynamic = document.getElementById('confirm-past-date-dynamic');
                    const isConfirmed = (confirmCheckbox && confirmCheckbox.checked) || (confirmCheckboxDynamic && confirmCheckboxDynamic.checked);
                    
                    const dateFrom = dateFromInput.value;
                    const dateTo = dateToInput.value;
                    const today = new Date().toISOString().split('T')[0];
                    const isDateFromPast = dateFrom && dateFrom < today;
                    const isDateToPast = dateTo && dateTo < today;
                    const isPast = isDateFromPast || isDateToPast;
                    
                    if (isPast && !isConfirmed) {
                        e.preventDefault();
                        alert('Musisz potwierdzić, że chcesz edytować zapotrzebowanie dla dat w przeszłości.');
                        return false;
                    }
                });
            }

            // Sprawdź daty przy załadowaniu strony
            checkDates();
        });
    </script>
</x-app-layout>
