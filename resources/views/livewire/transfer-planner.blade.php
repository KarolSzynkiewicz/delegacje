<div>
    @if($errors->any())
        <x-ui.alert variant="danger" title="Popraw błędy formularza" dismissible class="mb-4">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li class="text-white">{{ $error }}</li>
                @endforeach
            </ul>
        </x-ui.alert>
    @endif

    <!-- Uczestnicy (na górze) -->
    <x-ui.card label="Uczestnicy" class="mb-4">
        <p class="text-muted small mb-3">
            Lista zawiera wyłącznie osoby <strong>poza bazą</strong> w dniu transferu (w bazie są wyjazd i zjazd, nie transfer).
        </p>
        @error('selectedEmployeeIds') <div class="alert alert-danger py-2 mb-2">{{ $message }}</div> @enderror

        <div class="row g-2 mb-3">
            <div class="col-md-4">
                <x-ui.input
                    type="text"
                    name="employeeSearch"
                    label="Szukaj pracownika"
                    placeholder="Imię, nazwisko, telefon..."
                    wire:model.live.debounce.300ms="employeeSearch"
                />
            </div>

            <div class="col-md-4">
                <x-ui.input
                    type="select"
                    name="filterProjectId"
                    label="Szukaj w projekcie (na dzień transferu)"
                    wire:model.live="filterProjectId"
                >
                    <option value="">— dowolny —</option>
                    @foreach($this->filteredProjectsForParticipantFilter as $p)
                        <option value="{{ $p->id }}">
                            {{ $p->name }}@if($p->location) ({{ $p->location->name }})@endif
                        </option>
                    @endforeach
                </x-ui.input>
            </div>

            <div class="col-md-4">
                <x-ui.input
                    type="select"
                    name="filterAccommodationId"
                    label="Szukaj w domu (na dzień transferu)"
                    wire:model.live="filterAccommodationId"
                >
                    <option value="">— dowolny —</option>
                    @foreach($this->filteredAccommodationsForParticipantFilter as $a)
                        <option value="{{ $a->id }}">
                            {{ $a->name }}@if($a->location) ({{ $a->location->name }})@endif
                        </option>
                    @endforeach
                </x-ui.input>
            </div>
        </div>

        <div class="row g-2">
            @foreach($this->employeesPage as $employee)
                @php $selected = in_array($employee->id, $selectedEmployeeIds); @endphp
                <div class="col-md-4 col-lg-3">
                    <div
                        class="p-2 border rounded-3 user-select-none {{ $selected ? 'border-primary bg-primary bg-opacity-10' : 'border-secondary-subtle' }}"
                        wire:click="toggleEmployee({{ $employee->id }})"
                        style="cursor: pointer;"
                    >
                        <div class="d-flex align-items-center gap-2">
                            <div class="flex-grow-1">
                                <x-ui.input
                                    type="checkbox"
                                    name="emp_{{ $employee->id }}"
                                    :label="$employee->full_name"
                                    :value="$selected"
                                    class="mb-0"
                                    wire:click.stop="toggleEmployee({{ $employee->id }})"
                                />
                                @if($employee->phone)
                                    <div class="text-muted ms-4" style="font-size: 0.75rem;">
                                        {{ $employee->phone }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-3">
            {{ $this->employeesPage->links('vendor.livewire.simple-pagination') }}
        </div>

        @if(count($selectedEmployeeIds) > 0)
            <div class="mt-2">
                <small class="text-muted">Wybrani: {{ count($selectedEmployeeIds) }} pracownik(ów)</small>
            </div>
        @endif

        @if($this->selectedEmployees->isNotEmpty())
            <hr class="my-3">
            <div class="row g-2">
                @foreach($this->selectedEmployees as $emp)
                    @php
                        $home = $participantHomeLocations[$emp->id] ?? null;
                    @endphp
                    <div class="col-md-6 col-lg-4">
                        <div class="p-2 border rounded-3 d-flex align-items-center justify-content-between gap-2">
                            <div class="min-w-0">
                                <div class="fw-semibold small text-truncate">{{ $emp->full_name }}</div>
                                <div class="text-muted small">
                                    <i class="bi bi-house me-1"></i>
                                    @if($home)
                                        {{ $home['accommodation_name'] ?? 'Dom' }}
                                        @if(!empty($home['location_name']))
                                            · {{ $home['location_name'] }}
                                        @endif
                                        @if(!empty($home['city'])) ({{ $home['city'] }}) @endif
                                        @if(empty($home['location_id']))
                                            <span class="text-warning">(brak lokalizacji)</span>
                                        @endif
                                    @else
                                        Brak przypisanego domu (na dzień transferu)
                                    @endif
                                </div>
                            </div>
                            @if($home && !empty($home['is_base']))
                                <x-ui.badge variant="warning">Baza</x-ui.badge>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-ui.card>

    <!-- Podstawowe dane (niżej) -->
    <x-ui.card label="Dane transferu" class="mb-4">
        <div class="row g-3">
            <div class="col-md-4">
                <x-ui.input
                    type="datetime-local"
                    name="transferDate"
                    label="Data i godzina"
                    :required="true"
                    wire:model.live="transferDate"
                />
            </div>

            <div class="col-md-4">
                <x-ui.input
                    type="select"
                    name="vehicleId"
                    label="Pojazd"
                    wire:model.live="vehicleId"
                >
                    <option value="">Brak pojazdu / transport własny</option>
                    @foreach($this->vehicles as $v)
                        <option value="{{ $v->id }}">
                            {{ $v->registration_number }}
                            @if($v->brand || $v->model) — {{ trim($v->brand . ' ' . $v->model) }} @endif
                        </option>
                    @endforeach
                </x-ui.input>
            </div>

            <div class="col-md-4">
                <x-ui.input
                    type="textarea"
                    name="notes"
                    label="Notatki"
                    rows="3"
                    placeholder="Opcjonalne uwagi..."
                    wire:model.live.debounce.500ms="notes"
                />
            </div>
        </div>

        <hr class="my-3">

        <div class="form-check form-switch">
            <input
                class="form-check-input"
                type="checkbox"
                id="hasReassignment"
                wire:model.live="hasReassignment"
            >
            <label class="form-check-label fw-semibold" for="hasReassignment">
                Transfer obejmuje zmianę przypisań (przeniesienie)
            </label>
        </div>
        <div class="form-text text-muted">
            Gdy włączone: zamykamy stare przypisania (projekt, dom, auto) i możesz wskazać nowe dla każdej osoby.
        </div>
    </x-ui.card>

    <!-- Przeniesienie (zmiana przypisań) -->
    <x-ui.card label="Zmiana przypisań (przeniesienie)" class="mb-4">
        @if(! $hasReassignment)
            <p class="text-muted small mb-0">
                Transfer logistyczny — bez zmian w przypisaniach do projektów i mieszkań.
                Używaj tego dla transferów typu lotnisko→dom, dom→warsztat itp.
            </p>
        @endif

        @if($hasReassignment && count($selectedEmployeeIds) > 0)
            @php
                $wizardDate = \Carbon\Carbon::parse($transferDate)->format('Y-m-d');
                $wizardKey = md5(json_encode($selectedEmployeeIds));
            @endphp

            <p class="text-muted small mb-3">
                Ten sam układ co przy wyjeździe (kroki 1–3): projekt i rola, mieszkanie, pojazd służbowy.
                Na liście po lewej w kroku 1 są uczestnicy transferu. Daty w modalach ustawiają zakres nowych przypisań.
            </p>

            @error('reassignWizard')
                <x-ui.alert variant="danger" class="mb-3" dismissible>
                    {{ $message }}
                </x-ui.alert>
            @enderror

            <x-ui.tabs
                :tabs="[
                    1 => [
                        'label' => 'Krok 1: Przypisania do projektów',
                        'wireClick' => 'goToReassignStep(1)',
                    ],
                    2 => [
                        'label' => 'Krok 2: Przypisania do mieszkań',
                        'wireClick' => 'goToReassignStep(2)',
                    ],
                    3 => [
                        'label' => 'Krok 3: Przypisania do pojazdów',
                        'wireClick' => 'goToReassignStep(3)',
                    ],
                ]"
                :activeTab="$reassignStep"
                id="transferReassignStepsTabs"
            />

            @if($reassignStep === 1)
                <livewire:steps.step1-project-assignments
                    :departure-date="$wizardDate"
                    :end-date="$wizardDate"
                    :vehicle-id="$vehicleId"
                    :assignments="$assignments"
                    :assignment-ranges="$assignmentRanges"
                    :vehicle-seats="$vehicleSeats"
                    :for-transfer="true"
                    :allowed-employee-ids="$selectedEmployeeIds"
                    :key="'tr-s1-'.$wizardKey.'-'.md5(json_encode($assignmentRanges)).'-'.md5(json_encode($vehicleSeats))"
                />
            @elseif($reassignStep === 2)
                <livewire:steps.step2-accommodation-assignments
                    :departure-date="$wizardDate"
                    :end-date="$wizardDate"
                    :assignments="$assignments"
                    :assignment-ranges="$assignmentRanges"
                    :accommodation-assignments="$accommodationAssignments"
                    :for-transfer="true"
                    :allowed-employee-ids="$selectedEmployeeIds"
                    :key="'tr-s2-'.$wizardKey.'-'.md5(json_encode($accommodationAssignments)).'-'.md5(json_encode($assignmentRanges))"
                />
            @elseif($reassignStep === 3)
                <livewire:steps.step3-vehicle-assignments
                    :departure-date="$wizardDate"
                    :end-date="$wizardDate"
                    :vehicle-id="$vehicleId"
                    :assignments="$assignments"
                    :assignment-ranges="$assignmentRanges"
                    :accommodation-assignments="$accommodationAssignments"
                    :vehicle-assignments="$vehicleAssignments"
                    :for-transfer="true"
                    :allowed-employee-ids="$selectedEmployeeIds"
                    :key="'tr-s3-'.$wizardKey.'-'.md5(json_encode($vehicleAssignments)).'-'.md5(json_encode($assignmentRanges))"
                />
            @endif
        @elseif($hasReassignment && count($selectedEmployeeIds) === 0)
            <x-ui.alert variant="info" class="mt-3">
                Najpierw wybierz uczestników transferu.
            </x-ui.alert>
        @endif
    </x-ui.card>

    <!-- Trasa (pod przeniesieniem — punkty z domów starych i nowych przypisań) -->
    <x-ui.card label="Trasa" class="mb-4">
        <div class="row g-4">
            <!-- Left: route builder -->
            <div class="col-md-6">
                <p class="text-muted small mb-3">
                    Lista punktów uzupełnia się automatycznie o lokalizacje <strong>domów</strong>: najpierw obecne (na dzień transferu), potem — przy przeniesieniu — domy z nowych przypisań w kreatorze.
                    Potrzebujesz co najmniej 2 punkty, żeby trasa się policzyła. Możesz dodać lub usunąć punkty ręcznie.
                </p>

                @error('waypointLocationIds') <div class="alert alert-danger py-2 mb-3">{{ $message }}</div> @enderror

                <!-- Aktualna trasa -->
                @if(count($waypointLocationIds) > 0)
                    <div class="mb-3">
                        @foreach($this->waypointLocations as $index => $loc)
                            <div class="d-flex align-items-center gap-2 mb-2 p-2 border rounded-3
                                {{ $index === 0 ? 'border-primary bg-primary bg-opacity-10' : ($index === count($waypointLocationIds) - 1 ? 'border-success bg-success bg-opacity-10' : 'border-secondary-subtle') }}">
                                <div class="d-flex align-items-center justify-content-center rounded-circle fw-bold text-white
                                    {{ $index === 0 ? 'bg-primary' : ($index === count($waypointLocationIds) - 1 ? 'bg-success' : 'bg-secondary') }}"
                                    style="width: 28px; height: 28px; font-size: 0.8rem; flex-shrink: 0;">
                                    {{ $index + 1 }}
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold small">{{ $loc->name }}</div>
                                    @if($loc->city)
                                        <div class="text-muted" style="font-size: 0.75rem;">{{ $loc->city }}</div>
                                    @endif
                                    @if(!$loc->hasCoordinates())
                                        <div class="text-danger" style="font-size: 0.75rem;">
                                            <i class="bi bi-exclamation-triangle"></i> Brak współrzędnych — trasa może nie działać
                                        </div>
                                    @endif
                                </div>
                                <div class="d-flex gap-1">
                                    @if($index > 0)
                                        <button type="button" wire:click="moveWaypointUp({{ $index }})"
                                            class="btn btn-sm btn-outline-secondary py-0 px-1" title="Przesuń w górę">
                                            <i class="bi bi-arrow-up"></i>
                                        </button>
                                    @endif
                                    @if($index < count($waypointLocationIds) - 1)
                                        <button type="button" wire:click="moveWaypointDown({{ $index }})"
                                            class="btn btn-sm btn-outline-secondary py-0 px-1" title="Przesuń w dół">
                                            <i class="bi bi-arrow-down"></i>
                                        </button>
                                    @endif
                                    <button type="button" wire:click="removeWaypoint({{ $index }})"
                                        class="btn btn-sm btn-outline-danger py-0 px-1" title="Usuń">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <!-- Dodaj lokalizację -->
                <div class="d-flex gap-2 align-items-end">
                    <div class="flex-grow-1">
                        <x-ui.input
                            type="select"
                            name="addLocationId"
                            label="Dodaj lokalizację"
                            wire:model.live="addLocationId"
                        >
                            <option value="">— wybierz —</option>
                            @foreach($this->filteredLocationsForPicker as $loc)
                                <option value="{{ $loc->id }}">
                                    {{ $loc->name }}@if($loc->city), {{ $loc->city }}@endif
                                    @if(!$loc->hasCoordinates()) ⚠ @endif
                                </option>
                            @endforeach
                        </x-ui.input>
                    </div>
                    <x-ui.button
                        variant="primary"
                        class="btn-sm"
                        wire:click="addWaypoint"
                        :disabled="!$addLocationId"
                    >
                        Dodaj
                    </x-ui.button>
                </div>

                <!-- Wynik trasy -->
                @if($isPlanningRoute)
                    <div class="mt-3 text-muted small">
                        <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                        Obliczam trasę...
                    </div>
                @elseif($routeError)
                    <x-ui.alert variant="warning" class="mt-3">
                        <i class="bi bi-exclamation-triangle me-1"></i>{{ $routeError }}
                    </x-ui.alert>
                @elseif($routeData)
                    <div class="mt-3 p-3 border rounded-3 bg-success bg-opacity-10 border-success">
                        <div class="d-flex gap-4">
                            <div>
                                <div class="text-muted small">Dystans</div>
                                <div class="fw-semibold">
                                    {{ number_format($routeData['distance'], 1) }} km
                                </div>
                            </div>
                            <div>
                                <div class="text-muted small">Czas jazdy</div>
                                <div class="fw-semibold">
                                    @php
                                        $h = floor($routeData['duration'] / 3600);
                                        $m = floor(($routeData['duration'] % 3600) / 60);
                                    @endphp
                                    @if($h > 0){{ $h }} h @endif{{ $m }} min
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Right: selected participants preview -->
            <div class="col-md-6">
                <h6 class="mb-3 fw-semibold">Uczestnicy</h6>
                @if($this->selectedEmployees->isEmpty())
                    <x-ui.empty-state icon="people" message="Brak wybranych uczestników" />
                @else
                    <div class="row g-2">
                        @foreach($this->selectedEmployees as $emp)
                            @php
                                $initials = strtoupper(substr($emp->first_name ?? '', 0, 1) . substr($emp->last_name ?? '', 0, 1));
                            @endphp
                            <div class="col-md-6">
                                <div class="p-2 border rounded-3 d-flex align-items-center gap-2">
                                    <x-ui.avatar
                                        :image-url="$emp->image_url"
                                        :alt="$emp->full_name"
                                        :initials="$initials"
                                        size="40px"
                                    />
                                    <div class="min-w-0">
                                        <div class="fw-semibold small text-truncate">{{ $emp->full_name }}</div>
                                        @if($emp->phone)
                                            <div class="text-muted" style="font-size: 0.75rem;">{{ $emp->phone }}</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </x-ui.card>

    <!-- Kierowca i wynagrodzenie -->
    <x-ui.card label="Kierowca i wynagrodzenie" class="mb-4">
        <p class="text-muted small mb-3">
            Opcjonalne. Bonus zostanie zapisany bez payrollu — można go przypisać później w widoku Kary/Nagrody.
        </p>

        <div class="row g-3">
            <div class="col-md-4">
                <x-ui.input
                    type="select"
                    name="driverEmployeeId"
                    label="Kierowca"
                    wire:model.live="driverEmployeeId"
                    :disabled="count($selectedEmployeeIds) === 0"
                >
                    <option value="">— brak / nie dotyczy —</option>
                    @foreach($this->selectedEmployees as $emp)
                        <option value="{{ $emp->id }}">{{ $emp->full_name }}</option>
                    @endforeach
                </x-ui.input>
                @if(count($selectedEmployeeIds) === 0)
                    <div class="form-text text-muted">Najpierw wybierz uczestników.</div>
                @endif
            </div>

            @if($driverEmployeeId)
                <div class="col-md-3">
                    <x-ui.input
                        type="number"
                        name="driverPaymentAmount"
                        label="Kwota wynagrodzenia"
                        placeholder="0.00"
                        min="0"
                        step="0.01"
                        wire:model.live.debounce.300ms="driverPaymentAmount"
                    />
                </div>

                <div class="col-md-2">
                    <x-ui.input
                        type="select"
                        name="driverPaymentCurrency"
                        label="Waluta"
                        wire:model.live="driverPaymentCurrency"
                    >
                        @foreach($this->currencies as $currency)
                            <option value="{{ $currency->value }}">{{ $currency->label() }}</option>
                        @endforeach
                    </x-ui.input>
                </div>

                <div class="col-md-3">
                    <x-ui.input
                        type="select"
                        name="driverPayrollId"
                        label="Payroll"
                        wire:model.live="driverPayrollId"
                    >
                        <option value="">— przypisz później —</option>
                        @foreach($this->driverPayrolls as $payroll)
                            <option value="{{ $payroll->id }}">{{ $payroll->display_name }}</option>
                        @endforeach
                    </x-ui.input>
                    <div class="form-text text-muted">
                        Opcjonalnie. Jeśli payroll nie istnieje, zostaw puste.
                    </div>
                </div>
            @endif
        </div>
    </x-ui.card>

    <!-- Akcje -->
    <div class="d-flex justify-content-end gap-2">
        <x-ui.button variant="ghost" href="{{ route('transfers.index') }}">
            Anuluj
        </x-ui.button>
        <x-ui.button variant="primary" wire:click="save" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="save">
                <i class="bi bi-check-lg me-1"></i> Zapisz transfer
            </span>
            <span wire:loading wire:target="save">
                <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                Zapisywanie...
            </span>
        </x-ui.button>
    </div>
</div>
