<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Dodaj zapotrzebowanie dla projektu: {{ $project->name }}
            </h2>
            <a href="{{ route('projects.demands.index', $project) }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                Powrót
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form action="{{ route('projects.demands.store', $project) }}" method="POST" id="demands-form">
                    @csrf
                    
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

                    @if($dateFrom && $dateTo)
                    <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <p class="text-sm text-blue-800">
                            <strong>Zakres dat:</strong> {{ \Carbon\Carbon::parse($dateFrom)->format('d.m.Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('d.m.Y') }}
                        </p>
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
                                    Próbujesz dodać zapotrzebowanie dla dat w przeszłości. Czy na pewno chcesz kontynuować?
                                </p>
                                <label class="flex items-center">
                                    <input type="checkbox" id="confirm-past-date" class="rounded border-gray-300 text-yellow-600 focus:ring-yellow-500">
                                    <span class="ml-2 text-sm text-yellow-700">Tak, chcę dodać zapotrzebowanie dla dat w przeszłości</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Wspólne daty dla wszystkich ról -->
                    <div class="mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Data od <span class="text-red-500">*</span></label>
                            <input type="date" name="date_from" id="date_from" value="{{ $dateFrom ?? '' }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Data do (opcjonalnie)</label>
                            <input type="date" name="date_to" id="date_to" value="{{ $dateTo ?? '' }}" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>

                    <!-- Tabela z wszystkimi rolami -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Zapotrzebowanie na role:</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 border border-gray-300">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rola</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ilość osób</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($roles as $index => $role)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <label class="text-sm font-medium text-gray-900">{{ $role->name }}</label>
                                            @if($role->description)
                                                <p class="text-xs text-gray-500 mt-1">{{ $role->description }}</p>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @php
                                                $existingDemand = $existingDemands[$role->id] ?? null;
                                                $currentValue = $existingDemand ? $existingDemand->required_count : 0;
                                            @endphp
                                            <input 
                                                type="number" 
                                                name="demands[{{ $role->id }}][required_count]" 
                                                min="0" 
                                                value="{{ old("demands.{$role->id}.required_count", $currentValue) }}" 
                                                step="1"
                                                class="w-24 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 demand-count-input"
                                                data-role-id="{{ $role->id }}"
                                            >
                                            <input type="hidden" name="demands[{{ $role->id }}][role_id]" value="{{ $role->id }}">
                                            @if($existingDemand)
                                                <p class="text-xs text-gray-500 mt-1">Istniejące: {{ $existingDemand->required_count }}</p>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Uwagi -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Uwagi (opcjonalnie)</label>
                        <textarea name="notes" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    </div>

                    <div class="flex justify-end items-center mt-6">
                        <a href="{{ route('projects.demands.index', $project) }}" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded mr-2">
                            Anuluj
                        </a>
                        <button type="submit" id="submit-btn" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Zapisz zapotrzebowania
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Podświetl wiersze z ilością > 0
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('.demand-count-input');
            inputs.forEach(input => {
                input.addEventListener('input', function() {
                    const row = this.closest('tr');
                    if (parseInt(this.value) > 0) {
                        row.classList.add('bg-green-50');
                    } else {
                        row.classList.remove('bg-green-50');
                    }
                });
            });

            // Sprawdzanie dat w przeszłości i wyświetlanie warningu
            const dateFromInput = document.getElementById('date_from');
            const dateToInput = document.getElementById('date_to');
            const form = document.getElementById('demands-form');
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
                    warningDiv.id = 'past-date-warning';
                    warningDiv.innerHTML = `
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-yellow-600 mt-0.5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <div class="flex-1">
                                <h4 class="text-sm font-semibold text-yellow-800 mb-1">Uwaga: Data w przeszłości</h4>
                                <p class="text-sm text-yellow-700 mb-2">
                                    Próbujesz dodać zapotrzebowanie dla dat w przeszłości. Czy na pewno chcesz kontynuować?
                                </p>
                                <label class="flex items-center">
                                    <input type="checkbox" id="confirm-past-date" class="rounded border-gray-300 text-yellow-600 focus:ring-yellow-500">
                                    <span class="ml-2 text-sm text-yellow-700">Tak, chcę dodać zapotrzebowanie dla dat w przeszłości</span>
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
                    if (confirmCheckbox && !confirmCheckbox.checked) {
                        e.preventDefault();
                        alert('Musisz potwierdzić, że chcesz dodać zapotrzebowanie dla dat w przeszłości.');
                        return false;
                    }
                });
            }

            // Sprawdź daty przy załadowaniu strony
            checkDates();
        });
    </script>
</x-app-layout>

