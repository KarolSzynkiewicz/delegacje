<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Dopisz uczestnika do transferu">
            <x-slot name="left">
                <x-ui.button
                    variant="ghost"
                    href="{{ route('transfers.show', $transfer) }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    @if($errors->any())
        <x-ui.alert variant="danger" dismissible class="mb-3">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-ui.alert>
    @endif

    <x-ui.card label="Nowy uczestnik">
        <p class="small text-muted mb-4">
            Transfer #{{ $transfer->id }} · {{ $transfer->event_date?->format('d.m.Y') }}
            @if($transfer->has_reassignment)
                · ze zmianą przypisań (projekt / opcjonalnie dom i auto)
            @else
                · bez zmiany przypisań (tylko udział w przejeździe)
            @endif
        </p>

        <form method="POST" action="{{ route('transfers.participants.store', $transfer) }}" class="d-flex flex-column gap-3">
            @csrf

            <div>
                <label class="form-label small text-muted">Pracownik</label>
                <select name="employee_id" class="form-select" required>
                    <option value="">— wybierz —</option>
                    @foreach(\App\Models\Employee::query()->orderBy('last_name')->orderBy('first_name')->get(['id', 'first_name', 'last_name']) as $emp)
                        @if(! in_array($emp->id, $existingEmployeeIds, true))
                            <option value="{{ $emp->id }}" @selected((int) old('employee_id') === (int) $emp->id)>
                                {{ $emp->full_name }}
                            </option>
                        @endif
                    @endforeach
                </select>
            </div>

            @if($transfer->has_reassignment)
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="keep_current" value="1" id="keepCurrent"
                           @checked(old('keep_current'))
                           onchange="document.getElementById('reassignmentFields').style.display = this.checked ? 'none' : 'block'">
                    <label class="form-check-label" for="keepCurrent">Bez zmiany przypisań (tylko dojazd)</label>
                </div>

                <div id="reassignmentFields" @if(old('keep_current')) style="display:none" @endif class="d-flex flex-column gap-3">
                    <div>
                        <label class="form-label small text-muted">Projekt docelowy</label>
                        <select name="project_id" class="form-select">
                            <option value="">— wybierz —</option>
                            @foreach($projects as $project)
                                <option value="{{ $project->id }}" @selected((int) old('project_id') === (int) $project->id)>
                                    {{ $project->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label small text-muted">Rola</label>
                        <select name="role_id" class="form-select">
                            <option value="">— domyślna / z poprzedniego —</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" @selected((int) old('role_id') === (int) $role->id)>
                                    {{ $role->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small text-muted">Start przypisania</label>
                            <input type="date" name="start_date" class="form-control" value="{{ old('start_date', $defaultStart) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-muted">Koniec (opcjonalnie)</label>
                            <input type="date" name="end_date" class="form-control" value="{{ old('end_date') }}">
                        </div>
                    </div>
                    <div>
                        <label class="form-label small text-muted">Nowe mieszkanie (opcjonalnie)</label>
                        <select name="accommodation_id" class="form-select">
                            <option value="">— bez zmiany domu —</option>
                            @foreach($accommodations as $acc)
                                <option value="{{ $acc->id }}" @selected((int) old('accommodation_id') === (int) $acc->id)>
                                    {{ $acc->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label small text-muted">Nowy pojazd (opcjonalnie)</label>
                            <select name="vehicle_id" class="form-select">
                                <option value="">— bez zmiany auta —</option>
                                @foreach($vehicles as $vehicle)
                                    <option value="{{ $vehicle->id }}" @selected((int) old('vehicle_id') === (int) $vehicle->id)>
                                        {{ $vehicle->registration_number }}
                                        @if($vehicle->brand || $vehicle->model)
                                            — {{ trim($vehicle->brand.' '.$vehicle->model) }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small text-muted">Pozycja</label>
                            <select name="vehicle_position" class="form-select">
                                <option value="passenger" @selected(old('vehicle_position', 'passenger') === 'passenger')>Pasażer</option>
                                <option value="driver" @selected(old('vehicle_position') === 'driver')>Kierowca</option>
                            </select>
                        </div>
                    </div>
                </div>
            @endif

            <div class="d-flex gap-2 pt-2">
                <x-ui.button variant="primary" type="submit">
                    <i class="bi bi-person-plus me-1"></i> Dopisz
                </x-ui.button>
                <x-ui.button variant="ghost" href="{{ route('transfers.show', $transfer) }}">Anuluj</x-ui.button>
            </div>
        </form>
    </x-ui.card>
</x-app-layout>
