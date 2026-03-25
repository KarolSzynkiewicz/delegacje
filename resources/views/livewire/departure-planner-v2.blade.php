<div>
    <!-- Form Header: Dates and Vehicle -->
    <x-ui.card class="mb-4">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label fw-semibold">Data wyjazdu <span class="text-danger">*</span></label>
                <input 
                    type="date" 
                    wire:model.live="departureDate" 
                    class="form-control"
                    required
                >
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Data przybycia <span class="text-danger">*</span></label>
                <input 
                    type="date" 
                    wire:model.live="endDate" 
                    class="form-control"
                    min="{{ $departureDate }}"
                    required
                >
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold">Pojazd</label>
                <select 
                    wire:model.live="vehicleId" 
                    class="form-select"
                >
                    <option value="">Brak pojazdu</option>
                    @foreach($this->availableVehicles as $v)
                        <option value="{{ $v->id }}">
                            {{ $v->registration_number }} - {{ $v->brand }} {{ $v->model }}
                            @if($v->capacity) ({{ $v->capacity }} miejsc) @endif
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        @if(empty($vehicleId))
            <hr class="my-4">
            <h6 class="fw-semibold mb-3">Koszty biletów (osobno dla każdej osoby)</h6>
            @if($this->selectedEmployees->isEmpty())
                <div class="alert alert-info mb-0">
                    Najpierw przypisz pracowników do wyjazdu, aby dodać koszty biletów.
                </div>
            @else
                <div class="vstack gap-3">
                    @foreach($this->selectedEmployees as $employee)
                        <div class="border rounded p-3">
                            <div class="fw-semibold mb-3">{{ $employee->full_name }}</div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold">Koszt biletu <span class="text-danger">*</span></label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        wire:model.live="ticketCostsByEmployee.{{ $employee->id }}.amount"
                                        class="form-control @error('ticketCostsByEmployee.' . $employee->id . '.amount') is-invalid @enderror"
                                        placeholder="np. 120.50"
                                    >
                                    @error('ticketCostsByEmployee.' . $employee->id . '.amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fw-semibold">Waluta <span class="text-danger">*</span></label>
                                    <select
                                        wire:model.live="ticketCostsByEmployee.{{ $employee->id }}.currency"
                                        class="form-select @error('ticketCostsByEmployee.' . $employee->id . '.currency') is-invalid @enderror"
                                    >
                                        <option value="PLN">PLN</option>
                                        <option value="EUR">EUR</option>
                                        <option value="USD">USD</option>
                                    </select>
                                    @error('ticketCostsByEmployee.' . $employee->id . '.currency')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Lokalizacja przystanku docelowego <span class="text-danger">*</span></label>
                                    <select
                                        wire:model.live="ticketCostsByEmployee.{{ $employee->id }}.destination_stop_location_id"
                                        class="form-select @error('ticketCostsByEmployee.' . $employee->id . '.destination_stop_location_id') is-invalid @enderror"
                                    >
                                        <option value="">Wybierz lokalizację</option>
                                        @foreach($this->availableLocations as $location)
                                            <option value="{{ $location->id }}">{{ $location->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('ticketCostsByEmployee.' . $employee->id . '.destination_stop_location_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-semibold">Załącznik do kosztu (bilet/faktura)</label>
                                    <input
                                        type="file"
                                        wire:model="ticketCostsByEmployee.{{ $employee->id }}.attachment"
                                        class="form-control @error('ticketCostsByEmployee.' . $employee->id . '.attachment') is-invalid @enderror"
                                    >
                                    @error('ticketCostsByEmployee.' . $employee->id . '.attachment')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        @endif
    </x-ui.card>

    <!-- Step Navigation -->
    <x-ui.tabs 
        :tabs="[
            1 => [
                'label' => 'Krok 1: Przypisania do projektów',
                'wireClick' => 'goToStep(1)',
            ],
            2 => [
                'label' => 'Krok 2: Przypisania do mieszkań',
                'wireClick' => 'goToStep(2)',
            ],
            3 => [
                'label' => 'Krok 3: Przypisania do pojazdów',
                'wireClick' => 'goToStep(3)',
            ],
            4 => [
                'label' => 'Krok 4: Planowanie trasy',
                'wireClick' => 'goToStep(4)',
            ],
        ]"
        :activeTab="$currentStep"
        id="departureStepsTabs"
    />

    <!-- Step Content -->
    @if($currentStep === 1)
        <livewire:steps.step1-project-assignments
            :departure-date="$departureDate"
            :end-date="$endDate"
            :vehicle-id="$vehicleId"
            :assignments="$assignments"
            :assignment-ranges="$assignmentRanges"
            :vehicle-seats="$vehicleSeats"
            key="step1-{{ $departureDate }}-{{ $endDate }}-{{ $vehicleId }}-{{ md5(json_encode($assignments)) }}-{{ md5(json_encode($assignmentRanges)) }}-{{ md5(json_encode($vehicleSeats)) }}"
        />
    @elseif($currentStep === 2)
        <livewire:steps.step2-accommodation-assignments
            :departure-date="$departureDate"
            :end-date="$endDate"
            :assignments="$assignments"
            :assignment-ranges="$assignmentRanges"
            :accommodation-assignments="$accommodationAssignments"
            key="step2-{{ $departureDate }}-{{ md5(json_encode($assignments)) }}-{{ md5(json_encode($assignmentRanges)) }}-{{ md5(json_encode($accommodationAssignments)) }}"
        />
    @elseif($currentStep === 3)
        <livewire:steps.step3-vehicle-assignments
            :departure-date="$departureDate"
            :end-date="$endDate"
            :vehicle-id="$vehicleId"
            :assignments="$assignments"
            :assignment-ranges="$assignmentRanges"
            :accommodation-assignments="$accommodationAssignments"
            :vehicle-assignments="$vehicleAssignments"
            key="step3-{{ $departureDate }}-{{ md5(json_encode($assignments)) }}-{{ md5(json_encode($assignmentRanges)) }}-{{ md5(json_encode($vehicleAssignments)) }}"
        />
    @elseif($currentStep === 4)
        <livewire:steps.step4-route-planning
            :departure-date="$departureDate"
            :end-date="$endDate"
            :accommodation-assignments="$accommodationAssignments"
            :assignment-ranges="$assignmentRanges"
            :vehicle-assignments="$vehicleAssignments"
            key="step4-{{ $departureDate }}-{{ md5(json_encode($accommodationAssignments)) }}-{{ md5(json_encode($assignmentRanges)) }}-{{ md5(json_encode($vehicleAssignments)) }}"
        />
    @endif
</div>
