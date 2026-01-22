<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Dodaj zapotrzebowanie">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('projects.demands.index', $project) }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <x-ui.card label="Dodaj Zapotrzebowanie">
                <form action="{{ route('projects.demands.store', $project) }}" method="POST" id="demands-form">
                    @csrf
                    
                    <x-ui.errors />
                    
                    <div class="mb-3">
                        <x-ui.input 
                            type="select" 
                            name="project_id" 
                            label="Projekt"
                            onchange="if(this.value) { const queryString = '{{ request()->getQueryString() }}'; window.location.href = '{{ url('/projects') }}/' + this.value + '/demands/create' + (queryString ? '?' + queryString : ''); }"
                        >
                            @foreach($projects as $proj)
                                <option value="{{ $proj->id }}" {{ $project->id == $proj->id ? 'selected' : '' }}>
                                    {{ $proj->name }}
                                    @if($proj->location)
                                        - {{ $proj->location->name }}
                                    @endif
                                </option>
                            @endforeach
                        </x-ui.input>
                    </div>

                    @if($dateFrom && $dateTo)
                    <div class="alert alert-info mb-4" role="alert">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Zakres dat:</strong> {{ \Carbon\Carbon::parse($dateFrom)->format('d.m.Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('d.m.Y') }}
                    </div>
                    @endif

                    @if(isset($isDateInPast) && $isDateInPast)
                    <div class="alert alert-warning mb-4" id="past-date-warning" role="alert">
                        <div class="d-flex align-items-start">
                            <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                            <div class="flex-grow-1">
                                <h5 class="alert-heading mb-2">Uwaga: Data w przeszłości</h5>
                                <p class="mb-2">
                                    Próbujesz dodać zapotrzebowanie dla dat w przeszłości. Czy na pewno chcesz kontynuować?
                                </p>
                                <x-ui.input 
                                    type="checkbox" 
                                    id="confirm-past-date"
                                    label="Tak, chcę dodać zapotrzebowanie dla dat w przeszłości"
                                />
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Wspólne daty dla wszystkich ról -->
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <x-ui.input 
                                type="date" 
                                name="start_date" 
                                id="start_date"
                                label="Data od"
                                value="{{ old('start_date', $dateFrom ?? '') }}"
                                required="true"
                            />
                        </div>
                        <div class="col-md-6">
                            <x-ui.input 
                                type="date" 
                                name="end_date" 
                                id="end_date"
                                label="Data do (opcjonalnie)"
                                value="{{ old('end_date', $dateTo ?? $existingDateTo ?? '') }}"
                            />
                        </div>
                    </div>

                    <!-- Tabela z wszystkimi rolami -->
                    <div class="mb-4">
                        <h3 class="h5 fw-semibold mb-3">Zapotrzebowanie na role:</h3>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th class="text-start">Rola</th>
                                        <th class="text-start">Ilość osób</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($roles as $index => $role)
                                    <tr class="demand-row" data-role-id="{{ $role->id }}">
                                        <td>
                                            <label class="fw-medium mb-0">{{ $role->name }}</label>
                                            @if($role->description)
                                                <p class="small text-muted mb-0 mt-1">{{ $role->description }}</p>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $existingDemand = $existingDemands[$role->id] ?? null;
                                                $currentValue = $existingDemand ? $existingDemand->required_count : 0;
                                            @endphp
                                            <div class="d-flex align-items-center gap-2">
                                                <input 
                                                    type="number" 
                                                    name="demands[{{ $role->id }}][required_count]" 
                                                    min="0" 
                                                    value="{{ old("demands.{$role->id}.required_count", $currentValue) }}" 
                                                    step="1"
                                                    class="form-control demand-count-input" 
                                                    style="width: 100px;"
                                                    data-role-id="{{ $role->id }}"
                                                >
                                                <input type="hidden" name="demands[{{ $role->id }}][role_id]" value="{{ $role->id }}">
                                            </div>
                                            @if($existingDemand)
                                                <p class="small text-muted mb-0 mt-1">Istniejące: {{ $existingDemand->required_count }}</p>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Uwagi -->
                    <div class="mb-4">
                        <x-ui.input 
                            type="textarea" 
                            name="notes" 
                            label="Uwagi (opcjonalnie)"
                            rows="3"
                        />
                    </div>

                    <div class="d-flex justify-content-end align-items-center gap-2">
                        <x-ui.button variant="ghost" href="{{ route('projects.demands.index', $project) }}">
                            Anuluj
                        </x-ui.button>
                        <x-ui.button variant="primary" type="submit" id="submit-btn">
                            <i class="bi bi-save me-1"></i> Zapisz zapotrzebowania
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>
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
                        row.style.backgroundColor = 'rgba(16, 185, 129, 0.1)';
                    } else {
                        row.style.backgroundColor = '';
                    }
                });
            });

            // Sprawdzanie dat w przeszłości i wyświetlanie warningu
            const dateFromInput = document.getElementById('start_date');
            const dateToInput = document.getElementById('end_date');
            const form = document.getElementById('demands-form');
            let pastDateWarning = null;
            
            // Sprawdź czy już jest warning z PHP
            const existingWarning = document.getElementById('past-date-warning');

            function checkDates() {
                // Jeśli już jest warning z PHP, nie dodawaj kolejnego z JS
                if (existingWarning) {
                    return;
                }
                
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
                    warningDiv.className = 'alert alert-warning mb-4';
                    warningDiv.id = 'past-date-warning-dynamic';
                    warningDiv.setAttribute('role', 'alert');
                    warningDiv.innerHTML = `
                        <div class="d-flex align-items-start">
                            <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                            <div class="flex-grow-1">
                                <h5 class="alert-heading mb-2">Uwaga: Data w przeszłości</h5>
                                <p class="mb-2">
                                    Próbujesz dodać zapotrzebowanie dla dat w przeszłości. Czy na pewno chcesz kontynuować?
                                </p>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="confirm-past-date-dynamic">
                                    <label class="form-check-label" for="confirm-past-date-dynamic">
                                        Tak, chcę dodać zapotrzebowanie dla dat w przeszłości
                                    </label>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    // Wstaw warning przed datami
                    const dateInputsDiv = dateFromInput.closest('.row');
                    dateInputsDiv.parentNode.insertBefore(warningDiv, dateInputsDiv);
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
