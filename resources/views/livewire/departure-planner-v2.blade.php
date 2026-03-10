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
    @endif
</div>
