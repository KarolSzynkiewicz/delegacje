<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 mb-0">
                Edytuj zapotrzebowanie dla projektu: {{ $demand->project->name }}
            </h2>
            <x-ui.button variant="ghost" href="{{ route('projects.demands.index', $demand->project) }}">
                <i class="bi bi-arrow-left"></i> Powrót
            </x-ui.button>
        </div>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Edytuj Zapotrzebowanie">
                <form action="{{ route('demands.update', $demand) }}" method="POST" id="demand-form">
                    @csrf
                    @method('PUT')
                    
                    @if ($errors->any())
                        <div class="alert alert-danger mb-4" role="alert">
                            <h5 class="alert-heading mb-2">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>Wystąpiły błędy:
                            </h5>
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if(isset($isDateInPast) && $isDateInPast)
                    <div class="alert alert-warning mb-4" id="past-date-warning" role="alert">
                        <div class="d-flex align-items-start">
                            <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                            <div class="flex-grow-1">
                                <h5 class="alert-heading mb-2">Uwaga: Data w przeszłości</h5>
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
                        </div>
                    </div>
                    @endif

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
                                name="date_from" 
                                id="date_from"
                                label="Data od"
                                value="{{ old('date_from', $demand->date_from->format('Y-m-d')) }}"
                                required="true"
                            />
                        </div>
                        <div class="col-md-6">
                            <x-ui.input 
                                type="date" 
                                name="date_to" 
                                id="date_to"
                                label="Data do (opcjonalnie)"
                                value="{{ old('date_to', $demand->date_to ? $demand->date_to->format('Y-m-d') : '') }}"
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

                    <div class="d-flex justify-content-end align-items-center gap-2">
                        <x-ui.button variant="ghost" href="{{ route('projects.demands.index', $demand->project) }}">
                            Anuluj
                        </x-ui.button>
                        <x-ui.button variant="primary" type="submit" id="submit-btn">
                            <i class="bi bi-save me-1"></i> Zaktualizuj zapotrzebowanie
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dateFromInput = document.getElementById('date_from');
            const dateToInput = document.getElementById('date_to');
            const form = document.getElementById('demand-form');
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
                                    <x-ui.input 
                                        type="checkbox" 
                                        id="confirm-past-date-dynamic"
                                        label="Tak, chcę edytować zapotrzebowanie dla dat w przeszłości"
                                    />
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
