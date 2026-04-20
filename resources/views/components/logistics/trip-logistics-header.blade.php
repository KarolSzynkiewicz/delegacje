{{--
  Wspólny nagłówek: daty + tryb transportu + szczegóły (lotniska / pojazd).
  Używany w widokach Livewire wyjazd/zjazd — wywołaj przez @include z tablicą $tripLogisticsHeader.

  Wymaga w scope: $endDate, $transportMode, $publicTransportHubKind, $sharedStartAirportLocationId, $sharedEndAirportLocationId,
  $vehicleId, $availableVehicles, $availablePublicTransportHubs (collections), $departureDate / $returnDate wg firstWire.
  Przekazuj z widoku Livewire przez trip-details-panel lub inny @include — nie polegaj na $this w zagnieżdżonym komponencie Blade.
--}}
@php
    $cfg = $tripLogisticsHeader ?? [];
    $fw = $cfg['firstWire'] ?? 'departureDate';
    $missingFirst = empty($$fw);
    $missingEnd = empty($endDate);
    $datesIncomplete = $missingFirst || $missingEnd;
@endphp

<x-logistics.section-header :title="$cfg['title'] ?? 'Szczegóły'" />

<div class="row g-3 g-lg-4 align-items-stretch logistics-trip-header-row">
    {{-- Daty — poniżej lg pełna szerokość (unikamy nachodzenia kolumn na ~md) --}}
    <div class="col-12 col-lg-4 d-flex min-w-0">
        <div class="rounded-3 p-2 transition-all logistics-trip-header-card d-flex flex-column w-100 h-100{{ $datesIncomplete ? ' logistics-trip-header-card--invalid' : '' }}"
             style="justify-content: flex-start;">
            <div class="row g-2 align-items-end flex-grow-1">
                <div class="col-12 col-sm-6 min-w-0">
                    <label class="form-label small mb-1 {{ $missingFirst ? 'text-danger fw-semibold' : 'text-muted' }}">
                        {{ $cfg['firstLabel'] ?? 'Data' }} <span class="text-danger">*</span>
                    </label>
                    <input type="date"
                           wire:model.live="{{ $fw }}"
                           class="form-control w-100 logistics-trip-header-control @if($errors->has($fw) || $missingFirst) is-invalid @endif">
                </div>
                <div class="col-12 col-sm-6 min-w-0">
                    <label class="form-label small mb-1 {{ $missingEnd ? 'text-danger fw-semibold' : 'text-muted' }}">
                        Data zakończenia <span class="text-danger">*</span>
                    </label>
                    <input type="date"
                           wire:model.live="endDate"
                           class="form-control w-100 logistics-trip-header-control @if($errors->has('endDate') || $missingEnd) is-invalid @endif"
                           @if(! empty($$fw)) min="{{ $$fw }}" @endif>
                </div>
                @if($datesIncomplete)
                    <div class="col-12">
                        <div class="small text-danger mb-0 logistics-trip-header-hint">
                            <i class="bi bi-exclamation-circle me-1"></i>{{ $cfg['datesHelp'] ?? 'Wybierz obie daty.' }}</div>
                    </div>
                @else
                    <div class="col-12 flex-shrink-0" style="min-height: 16px;"></div>
                @endif
                @foreach([$fw, 'endDate'] as $dateField)
                    @error($dateField)
                        <div class="col-12">
                            <div class="invalid-feedback d-block logistics-trip-header-hint">{{ $message }}</div>
                        </div>
                    @enderror
                @endforeach
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-3 d-flex min-w-0">
        <x-logistics.transport-mode-toggle
            :mode="$transportMode"
            :hub-kind="$publicTransportHubKind"
            class="flex-fill"
        />
    </div>

    <div class="col-12 col-lg-5 d-flex min-w-0">
        @if($transportMode === null)
            <div class="rounded-3 p-2 transition-all logistics-trip-header-card logistics-trip-header-card--invalid d-flex flex-column w-100"
                 style="justify-content: space-between;">
                <label class="form-label small mb-1 text-danger fw-semibold">
                    Szczegóły <span class="text-danger">*</span>
                </label>
                <div class="flex-grow-1 d-flex align-items-center justify-content-center text-center min-w-0 small text-muted rounded-2 px-2 logistics-trip-header-control-row border border-secondary border-opacity-25"
                     style="min-height: 2.375rem;">
                    <div>
                        <i class="bi bi-arrow-left-right text-muted" style="font-size: 1.25rem; opacity: .35;"></i>
                        <div class="small text-muted mt-1" style="font-size: 0.72rem; opacity:.75;">
                            Wybierz <span class="fw-semibold">Publiczny</span> lub <span class="fw-semibold">Własny</span>
                        </div>
                    </div>
                </div>
                <div class="small text-danger mt-1 mb-0 flex-shrink-0 logistics-trip-header-hint" style="min-height: 16px;">
                    <i class="bi bi-exclamation-circle me-1"></i>Najpierw wybierz sposób transportu.
                </div>
            </div>
        @elseif($transportMode === 'public')
            @php
                $hubKind = $publicTransportHubKind ?? null;
                $missingStartAirport = empty($sharedStartAirportLocationId);
                $missingEndAirport = empty($sharedEndAirportLocationId);
                $airportsIncomplete = $hubKind !== null && ($missingStartAirport || $missingEndAirport);
                $pickKindIncomplete = $hubKind === null;
            @endphp
            @if($pickKindIncomplete)
                {{-- Krok 1: tylko wybór typu — potem ten blok znika i pokazują się listy --}}
                <div class="d-flex flex-column w-100 rounded-3 p-3 transition-all logistics-trip-header-card logistics-trip-header-card--invalid-soft">
                    <span class="form-label small text-muted mb-2 d-block">Typ punktu <span class="text-danger">*</span></span>
                    <div class="btn-group w-100 logistics-trip-header-control-row" role="group" aria-label="Lotnisko lub dworzec">
                        <button type="button"
                                class="btn btn-outline-primary logistics-trip-header-control"
                                wire:click="$set('publicTransportHubKind', 'airport')">
                            <i class="bi bi-airplane me-1"></i>Lotnisko
                        </button>
                        <button type="button"
                                class="btn btn-outline-primary logistics-trip-header-control"
                                wire:click="$set('publicTransportHubKind', 'station')">
                            <i class="bi bi-train-front me-1"></i>Dworzec
                        </button>
                    </div>
                    @error('publicTransportHubKind')
                        <div class="small text-danger mt-2 mb-0 logistics-trip-header-hint">{{ $message }}</div>
                    @enderror
                    <p class="small text-muted mt-2 mb-0 logistics-trip-header-hint">
                        Po wyborze wskażesz konkretne lokalizacje start i cel.
                    </p>
                </div>
            @else
                {{-- Krok 2: Start/Cel — ta sama wysokość pól co daty (bez -sm). Zmiana typu lotnisko/dworzec: przycisk „Samolot” / „Bus…”. --}}
                <div class="d-flex flex-column w-100 h-100 rounded-3 p-2 transition-all logistics-trip-header-card{{ $airportsIncomplete ? ' logistics-trip-header-card--invalid' : '' }}">
                    @if($availablePublicTransportHubs->isEmpty())
                        <div class="small text-warning mb-2">
                            <i class="bi bi-info-circle me-1"></i>Brak lokalizacji tego typu — dodaj cel w kartotece lokalizacji.
                        </div>
                    @endif
                    <div class="row g-2 align-items-end flex-grow-1">
                        <div class="col-12 col-sm-6 min-w-0">
                            @include('components.logistics.partials.trip-header-hub-select', [
                                'label' => 'Start',
                                'wireField' => 'sharedStartAirportLocationId',
                                'peerLocationId' => $sharedEndAirportLocationId ?? null,
                                'missing' => $missingStartAirport,
                            ])
                        </div>
                        <div class="col-12 col-sm-6 min-w-0">
                            @include('components.logistics.partials.trip-header-hub-select', [
                                'label' => 'Cel',
                                'wireField' => 'sharedEndAirportLocationId',
                                'peerLocationId' => $sharedStartAirportLocationId ?? null,
                                'missing' => $missingEndAirport,
                            ])
                        </div>
                        @if($airportsIncomplete)
                            <div class="col-12">
                                <div class="small text-danger logistics-trip-header-hint">
                                    <i class="bi bi-exclamation-circle me-1"></i>Wybierz punkt startowy i docelowy (wymagane przy transporcie publicznym).
                                </div>
                            </div>
                        @else
                            {{-- Ten sam „oddech” co kolumna dat — bez tego jednowierszowy wiersz rozciąga się w pionie i centruje pola względem sąsiadów. --}}
                            <div class="col-12 flex-shrink-0" style="min-height: 16px;"></div>
                        @endif
                    </div>
                </div>
            @endif
        @else
            <div class="w-100 h-100 d-flex flex-column rounded-3 p-2 transition-all logistics-trip-header-card">
                <label class="form-label small text-muted mb-1">Pojazd <span class="text-danger">*</span></label>
                <select wire:model.live="vehicleId" class="form-select logistics-trip-header-control @error('vehicleId') is-invalid @enderror">
                    <option value="" disabled {{ empty($vehicleId) ? 'selected' : '' }}>— wybierz pojazd —</option>
                    @foreach($availableVehicles as $v)
                        <option value="{{ $v->id }}">
                            {{ $v->registration_number }} – {{ $v->brand }} {{ $v->model }}
                            @if($v->capacity) ({{ $v->capacity }} m.) @endif
                        </option>
                    @endforeach
                </select>
                @error('vehicleId') <div class="invalid-feedback d-block logistics-trip-header-hint">{{ $message }}</div> @enderror
                @if(!empty($vehicleId))
                    @php
                        $headerTransportVehicle = $availableVehicles->firstWhere('id', (int) $vehicleId);
                        $headerDocWarning = $headerTransportVehicle?->documentComplianceWarningMessage() ?? '';
                    @endphp
                    @if($headerDocWarning !== '')
                        <small class="text-warning d-block mt-1">
                            <i class="bi bi-exclamation-triangle me-1"></i>Uwaga: {{ $headerDocWarning }}
                        </small>
                    @endif
                @endif
                @if($datesIncomplete)
                    <div class="small text-muted mt-1 logistics-trip-header-hint">
                        <i class="bi bi-info-circle me-1"></i>
                        @if(($cfg['vehiclePoolHint'] ?? 'departure') === 'return')
                            Wybierz datę zjazdu i datę zakończenia — wtedy wczytamy listę pojazdów.
                        @else
                            Wybierz datę wyjazdu i datę zakończenia — wtedy wczytamy listę pojazdów.
                        @endif
                    </div>
                @elseif($availableVehicles->isEmpty())
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
