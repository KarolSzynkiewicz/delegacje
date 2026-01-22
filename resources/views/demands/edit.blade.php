<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Edytuj Zapotrzebowanie: {{ $demand->role->name }}">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('demands.show', $demand) }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Edytuj Zapotrzebowanie">
                <x-ui.errors />

                @if(isset($isDateInPast) && $isDateInPast)
                <x-ui.alert variant="warning" title="Uwaga: Data w przeszłości" class="mb-4" id="past-date-warning">
                    <p class="mb-2">
                        Próbujesz edytować zapotrzebowanie dla dat w przeszłości. Czy na pewno chcesz kontynuować?
                    </p>
                    <div class="form-check">
                        <x-ui.input 
                            type="checkbox" 
                            id="confirm-past-date"
                            label="Tak, chcę edytować zapotrzebowanie dla dat w przeszłości"
                        />
                    </div>
                </x-ui.alert>
                @endif

                <form action="{{ route('demands.update', $demand) }}" method="POST" id="demand-form">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <x-ui.input 
                            type="select" 
                            name="project_id" 
                            label="Projekt"
                            required="true"
                        >
                            @foreach($projects as $proj)
                                <option value="{{ $proj->id }}" {{ old('project_id', $demand->project_id) == $proj->id ? 'selected' : '' }}>
                                    {{ $proj->name }}
                                    @if($proj->location)
                                        - {{ $proj->location->name }}
                                    @endif
                                </option>
                            @endforeach
                        </x-ui.input>
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="select" 
                            name="role_id" 
                            label="Rola"
                            required="true"
                        >
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" {{ old('role_id', $demand->role_id) == $role->id ? 'selected' : '' }}>
                                    {{ $role->name }}
                                </option>
                            @endforeach
                        </x-ui.input>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <x-ui.input 
                                type="date" 
                                name="start_date" 
                                id="start_date"
                                label="Data od"
                                value="{{ old('start_date', $demand->start_date->format('Y-m-d')) }}"
                                required="true"
                            />
                        </div>
                        <div class="col-md-6">
                            <x-ui.input 
                                type="date" 
                                name="end_date" 
                                id="end_date"
                                label="Data do (opcjonalnie)"
                                value="{{ old('end_date', $demand->end_date ? $demand->end_date->format('Y-m-d') : '') }}"
                            />
                        </div>
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="number" 
                            name="required_count" 
                            label="Ilość osób"
                            value="{{ old('required_count', $demand->required_count) }}"
                            min="0"
                            step="1"
                            required="true"
                        />
                        <small class="text-muted">Ustaw 0 aby usunąć zapotrzebowanie</small>
                    </div>

                    <div class="mb-4">
                        <x-ui.input 
                            type="textarea" 
                            name="notes" 
                            label="Uwagi (opcjonalnie)"
                            value="{{ old('notes', $demand->notes) }}"
                            rows="3"
                        />
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <x-ui.button 
                            variant="primary" 
                            type="submit"
                            action="save"
                        >
                            Zaktualizuj zapotrzebowanie
                        </x-ui.button>
                        <x-ui.button 
                            variant="ghost" 
                            href="{{ route('demands.show', $demand) }}"
                            action="cancel"
                        >
                            Anuluj
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dateFromInput = document.getElementById('start_date');
            const dateToInput = document.getElementById('end_date');
            const form = document.getElementById('demand-form');
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
                                    Próbujesz edytować zapotrzebowanie dla dat w przeszłości. Czy na pewno chcesz kontynuować?
                                </p>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="confirm-past-date-dynamic">
                                    <label class="form-check-label" for="confirm-past-date-dynamic">
                                        Tak, chcę edytować zapotrzebowanie dla dat w przeszłości
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
