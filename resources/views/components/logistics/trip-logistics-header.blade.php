{{--
  Wspólny nagłówek: daty + tryb transportu + szczegóły (lotniska / pojazd).
  Używany w widokach Livewire wyjazd/zjazd — wywołaj przez @include z tablicą $tripLogisticsHeader.

  Wymaga w scope rodzica: $endDate, $transportMode, $sharedStartAirportLocationId, $sharedEndAirportLocationId,
  $vehicleId, $vehicleSeats (opcj.), oraz computed $this->availableAirports, $this->availableVehicles.
  Dodatkowo nazwa pierwszej daty: departureDate albo returnDate (zgodnie z firstWire).
--}}
@php
    $cfg = $tripLogisticsHeader ?? [];
    $fw = $cfg['firstWire'] ?? 'departureDate';
    $missingFirst = empty($$fw);
    $missingEnd = empty($endDate);
    $datesIncomplete = $missingFirst || $missingEnd;
@endphp

<x-logistics.section-header :title="$cfg['title'] ?? 'Szczegóły'" />

<div class="row g-3 align-items-stretch">
    {{-- Daty --}}
    <div class="col-md-4 d-flex">
        <div class="rounded-3 p-2 transition-all logistics-trip-header-card d-flex flex-column w-100"
             style="min-height: 106px; justify-content: space-between; {{ $datesIncomplete ? 'border: 1px solid rgba(239,68,68,0.65) !important; background: rgba(239,68,68,0.12) !important; box-shadow: 0 0 0 1px rgba(239,68,68,0.15);' : '' }}">
            <div class="row g-2 align-items-end flex-grow-1">
                <div class="col-6 min-w-0">
                    <label class="form-label small mb-1 {{ $missingFirst ? 'text-danger fw-semibold' : 'text-muted' }}">
                        {{ $cfg['firstLabel'] ?? 'Data' }} <span class="text-danger">*</span>
                    </label>
                    <input type="date"
                           wire:model.live="{{ $fw }}"
                           class="form-control form-control-sm w-100 logistics-trip-header-control @if($errors->has($fw) || $missingFirst) is-invalid @endif"
                           style="min-height: 2.125rem;">
                </div>
                <div class="col-6 min-w-0">
                    <label class="form-label small mb-1 {{ $missingEnd ? 'text-danger fw-semibold' : 'text-muted' }}">
                        Data zakończenia <span class="text-danger">*</span>
                    </label>
                    <input type="date"
                           wire:model.live="endDate"
                           class="form-control form-control-sm w-100 logistics-trip-header-control @if($errors->has('endDate') || $missingEnd) is-invalid @endif"
                           style="min-height: 2.125rem;"
                           @if(! empty($$fw)) min="{{ $$fw }}" @endif>
                </div>
                @if($datesIncomplete)
                    <div class="col-12">
                        <div class="small text-danger mb-0" style="font-size: 0.72rem;">
                            <i class="bi bi-exclamation-circle me-1"></i>{{ $cfg['datesHelp'] ?? 'Wybierz obie daty.' }}</div>
                    </div>
                @else
                    <div class="col-12 flex-shrink-0" style="min-height: 16px;"></div>
                @endif
                @error($fw)
                    <div class="col-12">
                        <div class="invalid-feedback d-block" style="font-size:.72rem;">{{ $message }}</div>
                    </div>
                @enderror
                @error('endDate')
                    <div class="col-12">
                        <div class="invalid-feedback d-block" style="font-size:.72rem;">{{ $message }}</div>
                    </div>
                @enderror
            </div>
        </div>
    </div>

    <div class="col-md-3 d-flex">
        <x-logistics.transport-mode-toggle :mode="$transportMode" class="flex-fill" />
    </div>

    <div class="col-md-5 d-flex">
        @if($transportMode === null)
            <div class="rounded-3 p-2 transition-all logistics-trip-header-card d-flex flex-column w-100"
                 style="min-height: 106px; justify-content: space-between; border: 1px solid rgba(239,68,68,0.65) !important; background: rgba(239,68,68,0.12) !important; box-shadow: 0 0 0 1px rgba(239,68,68,0.15);">
                <label class="form-label small mb-1 text-danger fw-semibold">
                    Szczegóły <span class="text-danger">*</span>
                </label>
                <div class="flex-grow-1 d-flex align-items-center justify-content-center text-center logistics-trip-header-control-row"
                     style="min-height: 2.125rem;">
                    <div>
                        <i class="bi bi-arrow-left-right text-muted" style="font-size: 1.25rem; opacity: .35;"></i>
                        <div class="small text-muted mt-1" style="font-size: 0.72rem; opacity:.75;">
                            Wybierz <span class="fw-semibold">Publiczny</span> lub <span class="fw-semibold">Własny</span>
                        </div>
                    </div>
                </div>
                <div class="small text-danger mt-1 mb-0 flex-shrink-0" style="font-size: 0.72rem; min-height: 16px;">
                    <i class="bi bi-exclamation-circle me-1"></i>Najpierw wybierz sposób transportu.
                </div>
            </div>
        @elseif($transportMode === 'public')
            @php
                $missingStartAirport = empty($sharedStartAirportLocationId);
                $missingEndAirport = empty($sharedEndAirportLocationId);
                $airportsIncomplete = $missingStartAirport || $missingEndAirport;
            @endphp
            <div class="row g-2 rounded-3 p-2 transition-all w-100"
                 style="{{ $airportsIncomplete ? 'border: 1px solid rgba(239,68,68,0.65) !important; background: rgba(239,68,68,0.12) !important; box-shadow: 0 0 0 1px rgba(239,68,68,0.15);' : '' }}">
                <div class="col-6">
                    <label class="form-label small mb-1 {{ $missingStartAirport ? 'text-danger fw-semibold' : 'text-muted' }}">
                        Start (lotnisko / dworzec) <span class="text-danger">*</span>
                    </label>
                    <select wire:model.live="sharedStartAirportLocationId"
                            class="form-select form-select-sm logistics-trip-header-control @if($errors->has('sharedStartAirportLocationId') || $missingStartAirport) is-invalid @endif"
                            style="min-height: 2.125rem;">
                        <option value="">— wybierz —</option>
                        @foreach($this->availableAirports as $airport)
                            <option value="{{ $airport->id }}"
                                @disabled(!empty($sharedEndAirportLocationId) && (int)$sharedEndAirportLocationId === (int)$airport->id)>
                                {{ $airport->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('sharedStartAirportLocationId') <div class="invalid-feedback" style="font-size:.72rem;">{{ $message }}</div> @enderror
                </div>
                <div class="col-6">
                    <label class="form-label small mb-1 {{ $missingEndAirport ? 'text-danger fw-semibold' : 'text-muted' }}">
                        Cel (lotnisko / dworzec) <span class="text-danger">*</span>
                    </label>
                    <select wire:model.live="sharedEndAirportLocationId"
                            class="form-select form-select-sm logistics-trip-header-control @if($errors->has('sharedEndAirportLocationId') || $missingEndAirport) is-invalid @endif"
                            style="min-height: 2.125rem;">
                        <option value="">— wybierz —</option>
                        @foreach($this->availableAirports as $airport)
                            <option value="{{ $airport->id }}"
                                @disabled(!empty($sharedStartAirportLocationId) && (int)$sharedStartAirportLocationId === (int)$airport->id)>
                                {{ $airport->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('sharedEndAirportLocationId') <div class="invalid-feedback" style="font-size:.72rem;">{{ $message }}</div> @enderror
                </div>
                @if($airportsIncomplete)
                    <div class="col-12">
                        <div class="small text-danger" style="font-size: 0.72rem;">
                            <i class="bi bi-exclamation-circle me-1"></i>Wybierz punkt startowy i docelowy (wymagane przy transporcie publicznym).
                        </div>
                    </div>
                @endif
            </div>
        @else
            <div class="w-100">
                <label class="form-label small text-muted mb-1">Pojazd <span class="text-danger">*</span></label>
                <select wire:model.live="vehicleId" class="form-select form-select-sm logistics-trip-header-control @error('vehicleId') is-invalid @enderror" style="min-height: 2.125rem;">
                    <option value="" disabled {{ empty($vehicleId) ? 'selected' : '' }}>— wybierz pojazd —</option>
                    @foreach($this->availableVehicles as $v)
                        <option value="{{ $v->id }}">
                            {{ $v->registration_number }} – {{ $v->brand }} {{ $v->model }}
                            @if($v->capacity) ({{ $v->capacity }} m.) @endif
                        </option>
                    @endforeach
                </select>
                @error('vehicleId') <div class="invalid-feedback d-block" style="font-size:.72rem;">{{ $message }}</div> @enderror
                @if(!empty($vehicleId))
                    @php
                        $headerTransportVehicle = $this->availableVehicles->firstWhere('id', (int) $vehicleId);
                        $headerExpiredOc = $headerTransportVehicle && $headerTransportVehicle->hasExpiredInsurance();
                        $headerExpiredPrzeglad = $headerTransportVehicle && $headerTransportVehicle->hasExpiredInspection();
                        $headerDocWarning = '';
                        if ($headerExpiredOc) {
                            $headerDocWarning .= 'nieważne OC';
                        }
                        if ($headerExpiredPrzeglad) {
                            $headerDocWarning .= ($headerDocWarning !== '' ? ' oraz ' : '').'nieważny przegląd';
                        }
                    @endphp
                    @if($headerDocWarning !== '')
                        <small class="text-warning d-block mt-1">
                            <i class="bi bi-exclamation-triangle me-1"></i>Uwaga: {{ $headerDocWarning }}
                        </small>
                    @endif
                @endif
                @if($this->availableVehicles->isEmpty())
                    @if(($cfg['vehiclePoolHint'] ?? 'departure') === 'return')
                        @if(! empty($returnDate))
                            <div class="small text-warning mt-1">
                                <i class="bi bi-exclamation-triangle me-1"></i>Brak aut dostępnych dla tego okresu (poza bazą i wolnych w wyjeździe/zjeździe).
                            </div>
                        @endif
                    @else
                        <div class="small text-warning mt-1">
                            <i class="bi bi-exclamation-triangle me-1"></i>Brak aut dostępnych w bazie
                        </div>
                    @endif
                @endif
            </div>
        @endif
    </div>
</div>
