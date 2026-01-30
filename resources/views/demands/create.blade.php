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



                    <!-- Wspólne daty dla wszystkich ról -->
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <x-ui.input 
                                type="date" 
                                name="start_date" 
                                id="start_date"
                                label="Data od"
                                value="{{ old('start_date', $startDate ?? '') }}"
                                required="true"
                            />
                        </div>
                        <div class="col-md-6">
                            <x-ui.input 
                                type="date" 
                                name="end_date" 
                                id="end_date"
                                label="Data do (opcjonalnie)"
                                value="{{ old('end_date', $endDate ?? '') }}"
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
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end align-items-center gap-2">
                        @if(isset($isDateInPast) && $isDateInPast)
                            <div class="form-check me-2">
                                <input class="form-check-input" type="checkbox" id="confirm-past-date" required>
                                <label class="form-check-label text-warning" for="confirm-past-date">
                                    <i class="bi bi-exclamation-triangle-fill"></i> Uwaga: Data w przeszłości - chcesz edytować wstecz?
                                </label>
                            </div>
                        @endif
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

            // Walidacja checkboxa dla dat w przeszłości
            const form = document.getElementById('demands-form');
            const confirmCheckbox = document.getElementById('confirm-past-date');
            
            if (form && confirmCheckbox) {
                form.addEventListener('submit', function(e) {
                    if (!confirmCheckbox.checked) {
                        e.preventDefault();
                        alert('Musisz zaznaczyć checkbox, aby zapisać zapotrzebowanie dla dat w przeszłości.');
                        confirmCheckbox.focus();
                        return false;
                    }
                });
            }
        });
    </script>
</x-app-layout>
