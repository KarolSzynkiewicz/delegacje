<style>
    .weekly-logistics-metric {
        background: rgba(255, 255, 255, 0.06);
        border: 1px solid var(--glass-border) !important;
        border-radius: 12px;
        transition: box-shadow 0.15s ease, transform 0.15s ease, border-color 0.15s ease, background-color 0.15s ease;
    }
    button.weekly-logistics-metric {
        cursor: pointer;
    }
    button.weekly-logistics-metric:hover {
        background: rgba(255, 255, 255, 0.09);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.22);
        transform: translateY(-1px);
        border-color: rgba(148, 163, 184, 0.35) !important;
    }
    button.weekly-logistics-metric:focus-visible {
        outline: 2px solid var(--bs-primary);
        outline-offset: 2px;
    }
    .weekly-logistics-metric__icon {
        font-size: 1.35rem;
        line-height: 1;
        margin-bottom: 0.4rem;
        color: var(--text-muted);
        opacity: 0.92;
    }
    .weekly-logistics-metric__label {
        font-size: 0.7rem;
        font-weight: 600;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: var(--text-muted) !important;
        margin-bottom: 0.35rem;
    }
    .weekly-logistics-metric__value {
        font-size: 1.65rem;
        font-weight: 600;
        line-height: 1.2;
        color: var(--text-main) !important;
        font-variant-numeric: tabular-nums;
    }
    .weekly-logistics-popover-panel {
        background: rgba(30, 41, 59, 0.97) !important;
        backdrop-filter: none !important;
        -webkit-backdrop-filter: none !important;
        box-shadow: 0 16px 48px rgba(0, 0, 0, 0.55);
    }
    .weekly-summary-popover-line {
        border-bottom: 1px solid rgba(148, 163, 184, 0.2);
        padding-bottom: 0.65rem;
        margin-bottom: 0.65rem;
    }
    .weekly-summary-popover-line:last-child {
        border-bottom: 0;
        padding-bottom: 0;
        margin-bottom: 0;
    }
</style>

@php
    $vehiclesList = collect();
    foreach ($expiringItems['vehicle_inspections'] as $vehicle) {
        $vehiclesList->push([
            'vehicle' => $vehicle,
            'type' => 'inspection',
            'date' => $vehicle->inspection_valid_to,
        ]);
    }
    foreach ($expiringItems['vehicle_insurance'] as $vehicle) {
        $vehiclesList->push([
            'vehicle' => $vehicle,
            'type' => 'insurance',
            'date' => $vehicle->insurance_valid_to,
        ]);
    }
    $vehiclesList = $vehiclesList->sortBy('date');

    $expiringDocsCount = $expiringItems['documents']->count();
    $expiringLeasesCount = $expiringItems['accommodations']->count();
    $expiringVehicleIssuesCount = $vehiclesList->count();
    $projectsEndingThisMonthCount = $projectsEndingThisMonth->count();

    $hasExpiringItems = $expiringDocsCount > 0
        || $expiringVehicleIssuesCount > 0
        || $expiringLeasesCount > 0
        || $projectsEndingThisMonthCount > 0;
@endphp

<div
    class="weekly-summary-popover-root"
    x-data="weeklySummaryPopover"
    @keydown.escape.window="active && close()"
>
    <x-ui.card label="Podsumowanie tygodnia" class="mb-4 mt-3">
        <div class="row g-3">
            <div class="col-6 col-lg-3">
                <button
                    type="button"
                    class="weekly-logistics-metric p-3 text-center h-100 w-100 border-0 d-flex flex-column align-items-center justify-content-center"
                    title="Lista transferów"
                    @click.prevent="openPanel('transfers', $event)"
                >
                    <i class="bi bi-arrow-left-right weekly-logistics-metric__icon" aria-hidden="true"></i>
                    <div class="weekly-logistics-metric__label">Transfery</div>
                    <div class="weekly-logistics-metric__value">{{ $transferEvents->count() }}</div>
                </button>
            </div>
            <div class="col-6 col-lg-3">
                <button
                    type="button"
                    class="weekly-logistics-metric p-3 text-center h-100 w-100 border-0 d-flex flex-column align-items-center justify-content-center"
                    title="Lista wyjazdów"
                    @click.prevent="openPanel('departures', $event)"
                >
                    <i class="bi bi-arrow-right-circle weekly-logistics-metric__icon" aria-hidden="true"></i>
                    <div class="weekly-logistics-metric__label">Wyjazdy</div>
                    <div class="weekly-logistics-metric__value">{{ $allDepartures->count() }}</div>
                </button>
            </div>
            <div class="col-6 col-lg-3">
                <button
                    type="button"
                    class="weekly-logistics-metric p-3 text-center h-100 w-100 border-0 d-flex flex-column align-items-center justify-content-center"
                    title="Lista zjazdów"
                    @click.prevent="openPanel('returns', $event)"
                >
                    <i class="bi bi-arrow-return-left weekly-logistics-metric__icon" aria-hidden="true"></i>
                    <div class="weekly-logistics-metric__label">Zjazdy</div>
                    <div class="weekly-logistics-metric__value">{{ $returnTrips->count() }}</div>
                </button>
            </div>
            <div class="col-6 col-lg-3">
                <div
                    class="weekly-logistics-metric p-3 text-center h-100 d-flex flex-column align-items-center justify-content-center"
                    style="cursor: help;"
                    title="Liczba unikalnych pracowników przypisanych do projektów przecinających ten tydzień."
                >
                    <i class="bi bi-people weekly-logistics-metric__icon" aria-hidden="true"></i>
                    <div class="weekly-logistics-metric__label">Pracownicy</div>
                    <div class="weekly-logistics-metric__value">{{ $employeesInFieldCount }}</div>
                </div>
            </div>
        </div>

        <div class="mt-3 pt-3 border-top" style="border-color: var(--glass-border) !important;">
            <p class="small text-muted mb-3 mb-lg-2">Terminy wygasające oraz projekty kończące się w <strong>tym miesiącu</strong> (dokumenty, najmy, OC/przegląd aut, data końca projektu)</p>
            <div class="row g-3">
                <div class="col-6 col-lg-3">
                    <button
                        type="button"
                        class="weekly-logistics-metric p-3 text-center h-100 w-100 border-0 d-flex flex-column align-items-center justify-content-center"
                        title="Lista dokumentów"
                        @click.prevent="openPanel('expiring-documents', $event)"
                    >
                        <i class="bi bi-file-earmark-text weekly-logistics-metric__icon" aria-hidden="true"></i>
                        <div class="weekly-logistics-metric__label">Dokumenty</div>
                        <div class="weekly-logistics-metric__value">{{ $expiringDocsCount }}</div>
                    </button>
                </div>
                <div class="col-6 col-lg-3">
                    <button
                        type="button"
                        class="weekly-logistics-metric p-3 text-center h-100 w-100 border-0 d-flex flex-column align-items-center justify-content-center"
                        title="Lista najmów"
                        @click.prevent="openPanel('expiring-leases', $event)"
                    >
                        <i class="bi bi-house-door weekly-logistics-metric__icon" aria-hidden="true"></i>
                        <div class="weekly-logistics-metric__label">Najmy</div>
                        <div class="weekly-logistics-metric__value">{{ $expiringLeasesCount }}</div>
                    </button>
                </div>
                <div class="col-6 col-lg-3">
                    <button
                        type="button"
                        class="weekly-logistics-metric p-3 text-center h-100 w-100 border-0 d-flex flex-column align-items-center justify-content-center"
                        title="OC i przegląd — liczba terminów (auto może mieć oba)"
                        @click.prevent="openPanel('expiring-vehicles', $event)"
                    >
                        <i class="bi bi-car-front weekly-logistics-metric__icon" aria-hidden="true"></i>
                        <div class="weekly-logistics-metric__label">Auta</div>
                        <div class="weekly-logistics-metric__value">{{ $expiringVehicleIssuesCount }}</div>
                    </button>
                </div>
                <div class="col-6 col-lg-3">
                    <button
                        type="button"
                        class="weekly-logistics-metric p-3 text-center h-100 w-100 border-0 d-flex flex-column align-items-center justify-content-center"
                        title="Lista projektów z datą zakończenia w tym miesiącu"
                        @click.prevent="openPanel('expiring-projects', $event)"
                    >
                        <i class="bi bi-kanban weekly-logistics-metric__icon" aria-hidden="true"></i>
                        <div class="weekly-logistics-metric__label">Projekty</div>
                        <div class="weekly-logistics-metric__value">{{ $projectsEndingThisMonthCount }}</div>
                    </button>
                </div>
            </div>
        </div>

        @if(! $hasExpiringItems)
            <p class="text-muted small text-center mb-0 mt-3">Brak dokumentów, ubezpieczeń, najmów i projektów kończących się w tym miesiącu.</p>
        @endif
    </x-ui.card>

    <template x-if="active">
        <div
            class="position-fixed top-0 start-0 w-100 h-100"
            style="background: rgba(0, 0, 0, 0.55); z-index: 10050;"
            @click.self="close()"
        >
            <div
                x-ref="panel"
                class="weekly-logistics-popover-panel rounded-3 border p-3"
                style="
                    width: min(420px, calc(100vw - 24px));
                    max-height: min(70vh, 560px);
                    overflow-y: auto;
                    border-color: var(--glass-border) !important;
                    color: var(--text-main);
                "
                @click.stop
            >
                <div class="d-flex justify-content-between align-items-start gap-2 border-bottom pb-2 mb-2" style="border-color: var(--glass-border) !important;">
                    {{-- Jeden tytuł: x-show + Bootstrap d-inline-flex psowało ukrywanie (display !important) --}}
                    <h6 class="fw-semibold mb-0 small d-flex align-items-center gap-2 flex-wrap" style="letter-spacing: 0.02em;">
                        <i class="bi flex-shrink-0" :class="panelIconClass()"></i>
                        <span x-text="panelTitle()"></span>
                    </h6>
                    <button type="button" class="btn-close flex-shrink-0" aria-label="Zamknij" @click="close()"></button>
                </div>

                <div x-show="active === 'transfers'" x-cloak>
                    @if($transferEvents->isNotEmpty())
                        <ul class="mb-0 small list-unstyled">
                            @foreach($transferEvents as $transfer)
                                @php
                                    $transferParticipantsCount = $transfer->participants->pluck('employee_id')->filter()->unique()->count();
                                @endphp
                                <li class="mb-1">
                                    <a href="{{ route('transfers.show', $transfer) }}" class="text-decoration-none">
                                        <strong>{{ $transfer->event_date->format('d.m.Y') }}</strong>
                                        @if($transferParticipantsCount > 0)
                                            ({{ $transferParticipantsCount }} {{ $transferParticipantsCount === 1 ? 'osoba' : 'osób' }})
                                        @endif
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted small mb-0">Brak transferów w tym tygodniu.</p>
                    @endif
                </div>

                <div x-show="active === 'returns'" x-cloak>
                    @if($returnTrips->isNotEmpty())
                        <ul class="mb-0 small list-unstyled">
                            @foreach($returnTrips as $returnTrip)
                                @php
                                    $uniqueParticipantsCount = $returnTrip->participants->pluck('employee_id')->unique()->count();
                                @endphp
                                <li class="mb-1">
                                    <a href="{{ route('return-trips.show', $returnTrip) }}" class="text-decoration-none">
                                        <strong>{{ $returnTrip->event_date->format('d.m.Y') }}</strong>
                                        @if($uniqueParticipantsCount > 0)
                                            ({{ $uniqueParticipantsCount }} {{ $uniqueParticipantsCount === 1 ? 'osoba' : 'osób' }})
                                        @endif
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted small mb-0">Brak zjazdów w tym tygodniu.</p>
                    @endif
                </div>

                <div x-show="active === 'departures'" x-cloak>
                    @if(isset($allDepartures) && $allDepartures->isNotEmpty())
                        <ul class="mb-0 small list-unstyled">
                            @foreach($allDepartures as $departure)
                                @php
                                    $uniqueParticipants = $departure->participants
                                        ->filter(fn ($p) => $p->employee !== null);
                                    $participantsCount = $uniqueParticipants->count();
                                    $participantNames = $uniqueParticipants
                                        ->map(fn ($p) => $p->employee->full_name)
                                        ->join(', ');
                                    $visualStatus = $departure->getVisualStatus();
                                    $dayOfWeek = $departure->event_date->locale('pl')->isoFormat('dd');
                                @endphp
                                <li class="mb-2">
                                    <a href="{{ route('departures.show', $departure) }}" class="text-decoration-none d-flex align-items-center gap-1 flex-wrap">
                                        <i class="bi bi-calendar-week"></i>
                                        <span class="text-uppercase">{{ $dayOfWeek }}</span>
                                        @if($departure->toLocation)
                                            <i class="bi bi-flag"></i>
                                            <span>{{ $departure->toLocation->name }}</span>
                                        @endif
                                        <span class="text-muted">|</span>
                                        @if($participantsCount > 0)
                                            <span data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $participantNames }}">
                                                {{ $participantsCount }} os.
                                            </span>
                                        @endif
                                        @if($departure->vehicle)
                                            <span class="text-muted">|</span>
                                            <i class="bi bi-car-front"></i>
                                            <span>{{ $departure->vehicle->registration_number }}</span>
                                        @endif
                                        <span class="text-muted">|</span>
                                        <small class="badge badge-sm {{ $visualStatus === 'oczekuje' ? 'badge-primary' : ($visualStatus === 'w trakcie' ? 'badge-warning' : 'badge-success') }}">
                                            {{ $visualStatus }}
                                        </small>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted small mb-0">Brak wyjazdów w tym tygodniu.</p>
                    @endif
                </div>

                <div x-show="active === 'expiring-documents'" x-cloak>
                    @if($expiringItems['documents']->isNotEmpty())
                        <div class="small">
                            @foreach($expiringItems['documents'] as $document)
                                <div class="weekly-summary-popover-line">
                                    <a href="{{ route('employee-documents.edit', $document) }}" class="text-decoration-none d-block">
                                        <span class="fw-semibold">{{ $document->employee->full_name }}</span>
                                        <span class="text-muted"> — {{ $document->document->name ?? 'Dokument' }}</span>
                                        @if($document->type)
                                            <span class="text-muted"> ({{ $document->type }})</span>
                                        @endif
                                        <div class="mt-1">
                                            <i class="bi bi-calendar-event text-warning"></i>
                                            <span class="text-muted">Ważny do:</span> {{ $document->valid_to->format('d.m.Y') }}
                                        </div>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                        <div class="text-center mt-2 pt-2 border-top" style="border-color: var(--glass-border) !important;">
                            <a href="{{ route('employee-documents.index', ['filterStatus' => 'wygasa_wkrotce']) }}" class="small">Pełna lista dokumentów</a>
                        </div>
                    @else
                        <p class="text-muted small mb-0">Brak dokumentów wygasających w tym miesiącu.</p>
                    @endif
                </div>

                <div x-show="active === 'expiring-leases'" x-cloak>
                    @if($expiringItems['accommodations']->isNotEmpty())
                        <div class="small">
                            @foreach($expiringItems['accommodations'] as $accommodation)
                                <div class="weekly-summary-popover-line">
                                    <a href="{{ route('accommodations.edit', $accommodation) }}" class="text-decoration-none d-block">
                                        <span class="fw-semibold">{{ $accommodation->name }}</span>
                                        @if($accommodation->address)
                                            <div class="text-muted small">{{ $accommodation->address }}</div>
                                        @endif
                                        <div class="mt-1">
                                            <i class="bi bi-calendar-event text-warning"></i>
                                            <span class="text-muted">Najem do:</span> {{ $accommodation->lease_end_date?->format('d.m.Y') ?? '—' }}
                                        </div>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted small mb-0">Brak najmów kończących się w tym miesiącu.</p>
                    @endif
                </div>

                <div x-show="active === 'expiring-vehicles'" x-cloak>
                    @if($vehiclesList->isNotEmpty())
                        <div class="small">
                            @foreach($vehiclesList as $item)
                                @php
                                    $vehicle = $item['vehicle'];
                                    $type = $item['type'];
                                    $date = $item['date'];
                                @endphp
                                <div class="weekly-summary-popover-line">
                                    <a href="{{ route('vehicles.edit', $vehicle) }}" class="text-decoration-none d-block">
                                        <span class="fw-semibold">{{ $vehicle->registration_number }}</span>
                                        <span class="text-muted small ms-1">{{ trim($vehicle->brand.' '.$vehicle->model) }}</span>
                                        <div class="mt-1 d-flex align-items-center gap-2 flex-wrap">
                                            @if($type === 'inspection')
                                                <x-ui.badge variant="warning" class="small">Przegląd</x-ui.badge>
                                            @else
                                                <x-ui.badge variant="danger" class="small">OC</x-ui.badge>
                                            @endif
                                            <span><i class="bi bi-calendar-event"></i> {{ $date->format('d.m.Y') }}</span>
                                        </div>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted small mb-0">Brak kończących się terminów OC lub przeglądu w tym miesiącu.</p>
                    @endif
                </div>

                <div x-show="active === 'expiring-projects'" x-cloak>
                    @if($projectsEndingThisMonth->isNotEmpty())
                        <div class="small">
                            @foreach($projectsEndingThisMonth as $project)
                                <div class="weekly-summary-popover-line">
                                    <a href="{{ route('projects.show', $project) }}" class="text-decoration-none d-block">
                                        <span class="fw-semibold">{{ $project->name }}</span>
                                        <div class="mt-1">
                                            <i class="bi bi-calendar-event text-warning"></i>
                                            <span class="text-muted">Koniec:</span> {{ $project->end_date->format('d.m.Y') }}
                                        </div>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted small mb-0">Brak projektów z datą zakończenia w tym miesiącu.</p>
                    @endif
                </div>
            </div>
        </div>
    </template>
</div>

@once
@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    if (window.__weeklySummaryPopoverDataRegistered) {
        return;
    }
    window.__weeklySummaryPopoverDataRegistered = true;
    Alpine.data('weeklySummaryPopover', () => ({
        active: null,
        clientX: 0,
        clientY: 0,
        openPanel(type, event) {
            event.preventDefault();
            event.stopPropagation();
            this.active = type;
            this.clientX = event.clientX;
            this.clientY = event.clientY;
            this.$nextTick(() => {
                this.$nextTick(() => {
                    if (this.$refs.panel) {
                        this.placePanel(this.$refs.panel);
                    }
                });
            });
        },
        close() {
            this.active = null;
        },
        panelTitle() {
            const map = {
                transfers: 'Transfery (tydzień)',
                returns: 'Zjazdy (tydzień)',
                departures: 'Wyjazdy (tydzień)',
                'expiring-documents': 'Dokumenty (miesiąc)',
                'expiring-leases': 'Najmy (miesiąc)',
                'expiring-vehicles': 'Auta (OC / przegląd)',
                'expiring-projects': 'Projekty (miesiąc)',
            };

            return map[this.active] || '';
        },
        panelIconClass() {
            const map = {
                transfers: 'bi-arrow-left-right',
                returns: 'bi-arrow-return-left',
                departures: 'bi-arrow-right',
                'expiring-documents': 'bi-file-earmark-text',
                'expiring-leases': 'bi-house',
                'expiring-vehicles': 'bi-car-front',
                'expiring-projects': 'bi-kanban',
            };

            return map[this.active] || 'bi-info-circle';
        },
        placePanel(el) {
            if (!el) {
                return;
            }
            const x = this.clientX;
            const y = this.clientY;
            el.style.position = 'fixed';
            el.style.zIndex = '10051';
            el.style.maxWidth = 'min(420px, calc(100vw - 24px))';
            let left = x;
            let top = y + 8;
            el.style.left = left + 'px';
            el.style.top = top + 'px';
            el.style.transform = 'translate(-50%, 0)';
            this.$nextTick(() => {
                const r = el.getBoundingClientRect();
                let sx = 0;
                let sy = 0;
                if (r.left < 8) {
                    sx = 8 - r.left;
                }
                if (r.right > window.innerWidth - 8) {
                    sx = window.innerWidth - 8 - r.right;
                }
                if (r.top < 8) {
                    sy = 8 - r.top;
                }
                if (r.bottom > window.innerHeight - 8) {
                    sy = window.innerHeight - 8 - r.bottom;
                }
                if (sx !== 0 || sy !== 0) {
                    el.style.left = (left + sx) + 'px';
                    el.style.top = (top + sy) + 'px';
                }
            });
        },
    }));
});
</script>
@endpush
@endonce
