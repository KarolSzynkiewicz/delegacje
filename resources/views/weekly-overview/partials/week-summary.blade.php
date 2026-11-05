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
        z-index: 5;
        position: relative;
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
    .weekly-logistics-metric__sentence {
        font-size: 0.95rem;
        font-weight: 600;
        line-height: 1.35;
        color: var(--text-main) !important;
        font-variant-numeric: tabular-nums;
    }
    .weekly-logistics-metric__hint {
        font-size: 0.68rem;
        color: var(--text-muted);
        margin-top: 0.3rem;
    }
    .weekly-summary-popover-backdrop {
        position: fixed;
        inset: 0;
        z-index: 10050;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: max(0.85rem, env(safe-area-inset-top)) 0.85rem max(0.85rem, env(safe-area-inset-bottom));
        background: rgba(2, 6, 14, 0.72);
        backdrop-filter: blur(6px);
        -webkit-backdrop-filter: blur(6px);
    }
    .weekly-logistics-popover-panel {
        position: relative;
        width: min(440px, calc(100vw - 1.7rem));
        max-height: min(78vh, 640px);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        padding: 0 !important;
        border-radius: 16px !important;
        border: 1px solid rgba(148, 163, 184, 0.28) !important;
        background:
            linear-gradient(180deg, rgba(30, 41, 59, 0.98), rgba(11, 16, 28, 0.98)) !important;
        box-shadow:
            0 0 0 1px rgba(255, 255, 255, 0.04) inset,
            0 24px 64px rgba(0, 0, 0, 0.55),
            0 8px 24px rgba(0, 0, 0, 0.35);
        color: var(--text-main);
        backdrop-filter: none !important;
        -webkit-backdrop-filter: none !important;
    }
    .weekly-logistics-popover-panel::before {
        content: '';
        position: absolute;
        left: 0;
        right: 0;
        top: 0;
        height: 2px;
        background: linear-gradient(90deg, var(--primary), var(--accent));
        pointer-events: none;
    }
    .weekly-summary-popover-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 1rem 1.05rem 0.85rem;
        border-bottom: 1px solid rgba(148, 163, 184, 0.18);
        flex-shrink: 0;
    }
    .weekly-summary-popover-head__title {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        min-width: 0;
    }
    .weekly-summary-popover-head__icon {
        width: 2.1rem;
        height: 2.1rem;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.22), rgba(168, 85, 247, 0.18));
        border: 1px solid rgba(59, 130, 246, 0.28);
        color: #93c5fd;
        font-size: 1rem;
    }
    .weekly-summary-popover-head__icon i {
        color: inherit !important;
    }
    .weekly-summary-popover-head h6 {
        margin: 0;
        font-size: 0.98rem;
        font-weight: 650;
        letter-spacing: -0.01em;
        line-height: 1.25;
        color: var(--text-main) !important;
    }
    .weekly-summary-popover-body {
        padding: 0.85rem 1.05rem 1.05rem;
        overflow-y: auto;
        overscroll-behavior: contain;
        min-height: 0;
    }
    .weekly-summary-popover-line {
        border-bottom: 1px solid rgba(148, 163, 184, 0.14);
        padding: 0.7rem 0.1rem;
        margin: 0;
        gap: 0.75rem;
    }
    .weekly-summary-popover-line:last-child {
        border-bottom: 0;
        padding-bottom: 0.15rem;
    }
    .weekly-summary-popover-line a,
    .weekly-summary-popover-line .fw-semibold {
        color: var(--text-main) !important;
    }
    .weekly-summary-popover-line a:hover {
        color: #93c5fd !important;
    }
    .weekly-summary-popover-value {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 3.25rem;
        padding: 0.22rem 0.55rem;
        border-radius: 8px;
        border: 1px solid rgba(148, 163, 184, 0.22);
        background: rgba(255, 255, 255, 0.04);
        color: var(--text-main) !important;
        font-family: 'JetBrains Mono', ui-monospace, monospace;
        font-size: 0.78rem;
        font-weight: 650;
        font-variant-numeric: tabular-nums;
        letter-spacing: 0.01em;
        flex-shrink: 0;
        line-height: 1.2;
    }
    .weekly-summary-popover-lead {
        margin: 0 0 0.75rem;
        font-size: 0.8rem;
        line-height: 1.45;
        color: var(--text-muted) !important;
    }
    .weekly-summary-popover-tip {
        margin: 0.85rem 0 0;
        padding: 0.75rem 0.85rem;
        border-radius: 12px;
        border: 1px solid rgba(59, 130, 246, 0.22);
        background: rgba(59, 130, 246, 0.08);
        font-size: 0.78rem;
        line-height: 1.5;
        color: #cbd5e1 !important;
    }
    .weekly-summary-popover-section {
        margin: 0 0 0.45rem;
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: var(--text-muted) !important;
    }
    .wo-dep-hero {
        display: flex;
        align-items: baseline;
        gap: 0.4rem;
        line-height: 1;
        letter-spacing: -0.02em;
    }
    .wo-dep-hero__n {
        font-size: 2.15rem;
        font-weight: 650;
        font-variant-numeric: tabular-nums;
    }
    .wo-dep-hero__verb {
        font-size: 1.15rem;
        font-weight: 650;
    }
    .wo-dep-table-wrap {
        overflow: hidden;
    }
    .wo-dep-table {
        width: 100%;
        border-collapse: collapse;
    }
    .wo-dep-table th {
        font-size: 0.65rem;
        font-weight: 600;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: var(--text-muted);
        padding: 0.15rem 0.45rem 0.55rem;
        border-bottom: 1px solid var(--glass-border);
        white-space: nowrap;
    }
    .wo-dep-table td {
        padding: 0.7rem 0.45rem;
        border-bottom: 1px solid rgba(148, 163, 184, 0.16);
        vertical-align: middle;
    }
    .wo-dep-table tr:last-child td {
        border-bottom: 0;
    }
    .wo-dep-mode {
        width: 2rem;
        height: 2rem;
        border-radius: 0.55rem;
        border: 1px solid var(--glass-border);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        color: var(--text-muted);
        background: rgba(255, 255, 255, 0.04);
    }
    .wo-dep-people {
        font-size: 1rem;
        font-weight: 650;
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }
    .weekly-logistics-popover-panel.wo-dep-panel {
        width: min(720px, calc(100vw - 1.7rem)) !important;
        max-width: min(720px, calc(100vw - 1.7rem)) !important;
        max-height: min(88vh, 780px) !important;
    }
    @media (max-width: 575.98px) {
        .weekly-summary-popover-backdrop {
            align-items: flex-end;
            padding: 0;
        }
        .weekly-logistics-popover-panel,
        .weekly-logistics-popover-panel.wo-dep-panel {
            width: 100% !important;
            max-width: 100% !important;
            max-height: min(88vh, 720px) !important;
            border-radius: 16px 16px 0 0 !important;
        }
    }
</style>

@php
    $pl = function (int $n, string $one, string $few, string $many): string {
        $mod10 = $n % 10;
        $mod100 = $n % 100;
        if ($n === 1) {
            return $one;
        }
        if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 12 || $mod100 > 14)) {
            return $few;
        }

        return $many;
    };

    $peopleOnEvents = function ($events): int {
        return collect($events)
            ->flatMap(fn ($event) => $event->participants ?? collect())
            ->pluck('employee_id')
            ->filter()
            ->unique()
            ->count();
    };

    // Plan zmian zapisuje kilka wierszy uczestnika na jedną osobę (stare i nowe
    // przypisanie). Liczymy osoby raz na transfer i sumujemy, żeby kafelek
    // zgadzał się z listą. Osoba na dwóch transferach liczy się w każdym.
    $peopleOnOneTransfer = function ($event): int {
        return collect($event->participants ?? [])
            ->filter(fn ($participant) => $participant->employee_id && $participant->employee)
            ->unique('employee_id')
            ->count();
    };

    $departureEventsCount = collect($allDepartures ?? [])->count();
    $returnEventsCount = collect($returnTrips ?? [])->count();
    $transferEvents = collect($allTransfers ?? []);
    $transferEventsCount = $transferEvents->count();
    $departurePeopleCount = $peopleOnEvents($allDepartures ?? collect());
    $returnPeopleCount = $peopleOnEvents($returnTrips ?? collect());
    $transferPeopleCount = $transferEvents->sum(fn ($event) => $peopleOnOneTransfer($event));
    $rotationsEndingCount = collect($rotationsEnding ?? [])->count();
    $employeesNeededCount = (int) ($employeesNeededCount ?? 0);
    $housingOccupied = (int) ($housingOccupied ?? 0);
    $housingCapacity = (int) ($housingCapacity ?? 0);
    $vehicleOccupied = (int) ($vehicleOccupied ?? 0);
    $vehicleCapacity = (int) ($vehicleCapacity ?? 0);
    $benchWithRotation = (int) ($benchWithRotation ?? 0);
    $benchWithoutRotation = (int) ($benchWithoutRotation ?? 0);
    $benchDayLabel = $benchDayLabel ?? 'sobotę';
    $toSendCount = (int) ($toSendCount ?? 0);
    $toSendNextDemand = (int) ($toSendNextDemand ?? 0);
    $toSendStaying = (int) ($toSendStaying ?? 0);
    $rotationWindow = $rotationHorizonLabel ?? 'poniedziałku tego tygodnia do środy następnego';
    $housingHouses = collect($housingHouses ?? []);
    $fieldVehicles = collect($fieldVehicles ?? []);
    $benchWithPeople = collect($benchWithPeople ?? []);
    $benchWithoutPeople = collect($benchWithoutPeople ?? []);
    $toSendDemandByProject = collect($toSendDemandByProject ?? []);
    $toSendStayingPeople = collect($toSendStayingPeople ?? []);
    $toSendLeavingPeople = collect($toSendLeavingPeople ?? []);
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
                    data-tip="Lista transferów w tym tygodniu. Osoba liczy się raz na transfer, a osobno na każdym kolejnym."
                    @click.prevent="openPanel('transfers', $event)"
                >
                    <i class="bi bi-arrow-left-right weekly-logistics-metric__icon" aria-hidden="true"></i>
                    <div class="weekly-logistics-metric__label">Transfery</div>
                    <div class="weekly-logistics-metric__sentence">
                        Transfer: {{ $transferPeopleCount }} {{ $pl($transferPeopleCount, 'osoba', 'osoby', 'osób') }}
                        / {{ $transferEventsCount }} {{ $pl($transferEventsCount, 'transfer', 'transfery', 'transferów') }}
                    </div>
                </button>
            </div>
            <div class="col-6 col-lg-3">
                <button
                    type="button"
                    class="weekly-logistics-metric p-3 text-center h-100 w-100 border-0 d-flex flex-column align-items-center justify-content-center"
                    data-tip="Lista wyjazdów"
                    @click.prevent="openPanel('departures', $event)"
                >
                    <i class="bi bi-arrow-right-circle weekly-logistics-metric__icon" aria-hidden="true"></i>
                    <div class="weekly-logistics-metric__label">Wyjazdy</div>
                    <div class="weekly-logistics-metric__sentence">
                        Wyjeżdża: {{ $departurePeopleCount }} {{ $pl($departurePeopleCount, 'osoba', 'osoby', 'osób') }}
                        / {{ $departureEventsCount }} {{ $pl($departureEventsCount, 'wyjazd', 'wyjazdy', 'wyjazdów') }}
                    </div>
                </button>
            </div>
            <div class="col-6 col-lg-3">
                <button
                    type="button"
                    class="weekly-logistics-metric p-3 text-center h-100 w-100 border-0 d-flex flex-column align-items-center justify-content-center"
                    data-tip="Lista zjazdów"
                    @click.prevent="openPanel('returns', $event)"
                >
                    <i class="bi bi-arrow-return-left weekly-logistics-metric__icon" aria-hidden="true"></i>
                    <div class="weekly-logistics-metric__label">Zjazdy</div>
                    <div class="weekly-logistics-metric__sentence">
                        Zjeżdża: {{ $returnPeopleCount }} {{ $pl($returnPeopleCount, 'osoba', 'osoby', 'osób') }}
                        / {{ $returnEventsCount }} {{ $pl($returnEventsCount, 'zjazd', 'zjazdy', 'zjazdów') }}
                    </div>
                </button>
            </div>
            <div class="col-6 col-lg-3">
                <button
                    type="button"
                    class="weekly-logistics-metric p-3 text-center h-100 w-100 border-0 d-flex flex-column align-items-center justify-content-center"
                    data-tip="Przypisani / zapotrzebowanie we wszystkich kierunkach"
                    @click.prevent="openPanel('employees-by-project', $event)"
                >
                    <i class="bi bi-people weekly-logistics-metric__icon" aria-hidden="true"></i>
                    <div class="weekly-logistics-metric__label">Pracownicy</div>
                    <div class="weekly-logistics-metric__value">{{ $employeesInFieldCount }}/{{ $employeesNeededCount }}</div>
                    <div
                        class="weekly-logistics-metric__hint"
                        role="button"
                        tabindex="0"
                        data-tip="Osoby, których rotacja kończy się w tym tygodniu albo najpóźniej w przyszłą środę (włącznie){{ ! empty($rotationHorizonLabel) ? ' — '.$rotationHorizonLabel : '' }}"
                        @click.stop.prevent="openPanel('rotations-ending', $event)"
                        @keydown.enter.stop.prevent="openPanel('rotations-ending', $event)"
                    >
                        {{ $rotationsEndingCount }} {{ $pl($rotationsEndingCount, 'kończy rotację', 'kończą rotację', 'kończy rotację') }}
                    </div>
                </button>
            </div>
        </div>

        <div class="mt-3 pt-3 border-top" style="border-color: var(--glass-border) !important;">
            <div class="row g-3">
                <div class="col-6 col-lg-3">
                    <button
                        type="button"
                        class="weekly-logistics-metric p-3 text-center h-100 w-100 border-0 d-flex flex-column align-items-center justify-content-center"
                        data-tip="Osoby w domach w tym tygodniu / miejsca we wszystkich domach, które wtedy mamy — własnych i wynajętych, także pustych. Kto zmienia dom w trakcie tygodnia, liczy się w każdym."
                        @click.prevent="openPanel('housing', $event)"
                    >
                        <i class="bi bi-house-door weekly-logistics-metric__icon" aria-hidden="true"></i>
                        <div class="weekly-logistics-metric__label">Domy</div>
                        <div class="weekly-logistics-metric__value">{{ $housingOccupied }}/{{ $housingCapacity }}</div>
                    </button>
                </div>
                <div class="col-6 col-lg-3">
                    <button
                        type="button"
                        class="weekly-logistics-metric p-3 text-center h-100 w-100 border-0 d-flex flex-column align-items-center justify-content-center"
                        data-tip="Osoby z przypisań do aut poza bazą / fotele tych aut. Auto w serwisie w tym tygodniu albo na wyjeździe, zjeździe lub transferze nie wchodzi. Puste auto poza bazą wchodzi do miejsc."
                        @click.prevent="openPanel('field-vehicles', $event)"
                    >
                        <i class="bi bi-car-front weekly-logistics-metric__icon" aria-hidden="true"></i>
                        <div class="weekly-logistics-metric__label">Auta</div>
                        <div class="weekly-logistics-metric__value">{{ $vehicleOccupied }}/{{ $vehicleCapacity }}</div>
                    </button>
                </div>
                <div class="col-6 col-lg-3">
                    <button
                        type="button"
                        class="weekly-logistics-metric p-3 text-center h-100 w-100 border-0 d-flex flex-column align-items-center justify-content-center"
                        data-tip="Stan na {{ $benchDayLabel }} tego tygodnia. Pierwsza liczba: w bazie i mają rotację obejmującą ten dzień albo zaczynającą się do końca okna {{ $rotationWindow }}. Druga: w bazie bez takiej rotacji. Na projekcie i w drodze nie wchodzą."
                        @click.prevent="openPanel('bench', $event)"
                    >
                        <i class="bi bi-person-workspace weekly-logistics-metric__icon" aria-hidden="true"></i>
                        <div class="weekly-logistics-metric__label">Ławka</div>
                        <div class="weekly-logistics-metric__value">{{ $benchWithRotation }}/{{ $benchWithoutRotation }}</div>
                    </button>
                </div>
                <div class="col-6 col-lg-3">
                    <button
                        type="button"
                        class="weekly-logistics-metric p-3 text-center h-100 w-100 border-0 d-flex flex-column align-items-center justify-content-center"
                        data-tip="Popyt przyszłego tygodnia minus osoby, które wtedy nadal są na projekcie. Odpadają ci, których rotacja kończy się w oknie {{ $rotationWindow }} — także gdy przypisanie jeszcze ich obejmuje. Liczba ujemna to nadmiar."
                        @click.prevent="openPanel('to-send', $event)"
                    >
                        <i class="bi bi-box-arrow-up-right weekly-logistics-metric__icon" aria-hidden="true"></i>
                        <div class="weekly-logistics-metric__label">Do dosłania</div>
                        <div class="weekly-logistics-metric__value">{{ $toSendCount }}</div>
                        @if($toSendCount < 0)
                            <div class="weekly-logistics-metric__hint">nadmiar</div>
                        @endif
                    </button>
                </div>
            </div>
        </div>
    </x-ui.card>

    <template x-if="active">
        <div
            class="weekly-summary-popover-backdrop"
            @click.self="close()"
        >
            <div
                class="weekly-logistics-popover-panel"
                :class="active === 'departures' || active === 'returns' ? 'wo-dep-panel' : ''"
                role="dialog"
                aria-modal="true"
                :aria-label="panelTitle()"
                @click.stop
            >
                <div class="weekly-summary-popover-head">
                    <div class="weekly-summary-popover-head__title">
                        <span class="weekly-summary-popover-head__icon" aria-hidden="true">
                            <i class="bi" :class="panelIconClass()"></i>
                        </span>
                        <h6 x-text="panelTitle()"></h6>
                    </div>
                    <button type="button" class="btn-close btn-close-white flex-shrink-0 mt-1" aria-label="Zamknij" @click="close()"></button>
                </div>

                <div class="weekly-summary-popover-body">
                <div x-show="active === 'rotations-ending'" x-cloak>
                    @if(collect($rotationsEnding ?? [])->isNotEmpty())
                        <p class="weekly-summary-popover-lead">Koniec rotacji w oknie {{ $rotationHorizonLabel }} (ten tydzień i przyszła środa włącznie).</p>
                        <ul class="mb-0 list-unstyled">
                            @foreach($rotationsEnding as $rotation)
                                <li class="weekly-summary-popover-line d-flex justify-content-between align-items-center">
                                    @if($rotation->employee)
                                        <a href="{{ route('employees.rotations.show', [$rotation->employee, $rotation]) }}" class="text-decoration-none min-w-0 flex-grow-1">
                                            <span class="fw-semibold">{{ $rotation->employee->full_name }}</span>
                                        </a>
                                    @else
                                        <span class="fw-semibold">—</span>
                                    @endif
                                    <span class="weekly-summary-popover-value">{{ $rotation->end_date->format('d.m.Y') }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="weekly-summary-popover-lead mb-0">Nikt nie kończy rotacji w tym tygodniu ani do najbliższej środy po nim.</p>
                    @endif
                </div>

                <div x-show="active === 'transfers'" x-cloak>
                    @if(isset($allTransfers) && collect($allTransfers)->isNotEmpty())
                        <ul class="mb-0 small list-unstyled">
                            @foreach($allTransfers as $transfer)
                                @php
                                    $uniqueParticipants = $transfer->participants
                                        ->filter(fn ($p) => $p->employee !== null)
                                        ->unique('employee_id')
                                        ->values();
                                    $participantsCount = $uniqueParticipants->count();
                                    $participantNames = $uniqueParticipants
                                        ->map(fn ($p) => $p->employee->full_name)
                                        ->join(', ');
                                    $visualStatus = $transfer->getVisualStatus();
                                    $dayOfWeek = $transfer->event_date->locale('pl')->isoFormat('dd');
                                @endphp
                                <li class="mb-2">
                                    <a href="{{ route('transfers.show', $transfer) }}" class="text-decoration-none d-flex align-items-center gap-1 flex-wrap">
                                        <i class="bi bi-calendar-week"></i>
                                        <span class="text-uppercase">{{ $dayOfWeek }}</span>
                                        @if($transfer->fromLocation || $transfer->toLocation)
                                            <i class="bi bi-arrow-left-right"></i>
                                            <span>{{ $transfer->fromLocation?->name ?? '?' }} → {{ $transfer->toLocation?->name ?? '?' }}</span>
                                        @endif
                                        <span class="text-muted">|</span>
                                        @if($participantsCount > 0)
                                            <span data-tip="{{ $participantNames }}">
                                                {{ $participantsCount }} os.
                                            </span>
                                        @endif
                                        @if($transfer->vehicle)
                                            <span class="text-muted">|</span>
                                            <i class="bi bi-car-front"></i>
                                            <span>{{ $transfer->vehicle->registration_number }}</span>
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
                        <p class="weekly-summary-popover-lead mb-0">Brak transferów w tym tygodniu.</p>
                    @endif
                </div>

                <div x-show="active === 'housing'" x-cloak>
                    <p class="weekly-summary-popover-lead">Osoby w domu / miejsca. Suma to {{ $housingOccupied }}/{{ $housingCapacity }}. Pusty dom też jest na liście. Kto zmienia dom w tygodniu, liczy się w każdym.</p>
                    @if($housingHouses->isNotEmpty())
                        <ul class="mb-0 list-unstyled">
                            @foreach($housingHouses->sortByDesc('occupied') as $house)
                                <li class="weekly-summary-popover-line d-flex justify-content-between align-items-center">
                                    <a href="{{ route('accommodations.show', $house->id) }}" class="text-decoration-none min-w-0 flex-grow-1">
                                        <span class="fw-semibold">{{ $house->name }}</span>
                                    </a>
                                    <span class="weekly-summary-popover-value">{{ $house->occupied }}/{{ $house->capacity }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="weekly-summary-popover-lead mb-0">W tym tygodniu nie ma domów własnych ani wynajętych.</p>
                    @endif
                </div>

                <div x-show="active === 'field-vehicles'" x-cloak>
                    <p class="weekly-summary-popover-lead">Osoby z przypisań / fotele. Suma to {{ $vehicleOccupied }}/{{ $vehicleCapacity }}. Tylko auta poza bazą, bez serwisu i bez wyjazdu, zjazdu albo transferu w tym tygodniu.</p>
                    @if($fieldVehicles->isNotEmpty())
                        <ul class="mb-0 list-unstyled">
                            @foreach($fieldVehicles->sortByDesc('occupied') as $vehicle)
                                <li class="weekly-summary-popover-line d-flex justify-content-between align-items-center">
                                    <a href="{{ route('vehicles.show', $vehicle->id) }}" class="text-decoration-none min-w-0 flex-grow-1">
                                        <span class="fw-semibold">{{ $vehicle->name }}</span>
                                    </a>
                                    <span class="weekly-summary-popover-value">{{ $vehicle->occupied }}/{{ $vehicle->capacity }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="weekly-summary-popover-lead mb-0">Żadne auto nie jest w tym tygodniu w terenie.</p>
                    @endif
                </div>

                <div x-show="active === 'bench'" x-cloak>
                    <p class="weekly-summary-popover-lead">Stan na {{ $benchDayLabel }}. W bazie z rotacją: {{ $benchWithRotation }}. W bazie bez rotacji: {{ $benchWithoutRotation }}. Rotacja liczy się, gdy obejmuje ten dzień albo zaczyna się do końca okna {{ $rotationWindow }}.</p>
                    <p class="weekly-summary-popover-section">W bazie z rotacją</p>
                    @if($benchWithPeople->isNotEmpty())
                        <ul class="mb-3 list-unstyled">
                            @foreach($benchWithPeople as $person)
                                <li class="weekly-summary-popover-line">
                                    <a href="{{ route('employees.show', $person->id) }}" class="text-decoration-none">
                                        <span class="fw-semibold">{{ $person->name }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="weekly-summary-popover-lead mb-3">Nikt.</p>
                    @endif
                    <p class="weekly-summary-popover-section">W bazie bez rotacji</p>
                    @if($benchWithoutPeople->isNotEmpty())
                        <ul class="mb-0 list-unstyled">
                            @foreach($benchWithoutPeople as $person)
                                <li class="weekly-summary-popover-line">
                                    <a href="{{ route('employees.show', $person->id) }}" class="text-decoration-none">
                                        <span class="fw-semibold">{{ $person->name }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="weekly-summary-popover-lead mb-0">Nikt.</p>
                    @endif
                </div>

                <div x-show="active === 'to-send'" x-cloak>
                    <p class="weekly-summary-popover-lead">
                        Popyt przyszłego tygodnia {{ $toSendNextDemand }}
                        − zostają {{ $toSendStaying }}
                        = <span class="fw-semibold">{{ $toSendCount }}</span>@if($toSendCount < 0) (nadmiar)@endif.
                        Odpadają osoby, których rotacja kończy się w oknie {{ $rotationWindow }}.
                    </p>
                    <p class="weekly-summary-popover-section">Popyt przyszłego tygodnia</p>
                    @if($toSendDemandByProject->isNotEmpty())
                        <ul class="mb-3 list-unstyled">
                            @foreach($toSendDemandByProject as $row)
                                <li class="weekly-summary-popover-line d-flex justify-content-between align-items-center">
                                    <a href="{{ route('projects.show', $row->project_id) }}" class="text-decoration-none min-w-0 flex-grow-1">
                                        <span class="fw-semibold">{{ $row->project_name }}</span>
                                    </a>
                                    <span class="weekly-summary-popover-value">{{ $row->needed }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="weekly-summary-popover-lead mb-3">Brak popytu.</p>
                    @endif
                    <p class="weekly-summary-popover-section">Zostają na przyszły tydzień</p>
                    @if($toSendStayingPeople->isNotEmpty())
                        <ul class="mb-3 list-unstyled">
                            @foreach($toSendStayingPeople as $person)
                                <li class="weekly-summary-popover-line">
                                    <a href="{{ route('employees.show', $person->id) }}" class="text-decoration-none">
                                        <span class="fw-semibold">{{ $person->name }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="weekly-summary-popover-lead mb-3">Nikt z przypisaniem nie zostaje.</p>
                    @endif
                    <p class="weekly-summary-popover-section">Odchodzą — rotacja kończy się w oknie</p>
                    @if($toSendLeavingPeople->isNotEmpty())
                        <ul class="mb-0 list-unstyled">
                            @foreach($toSendLeavingPeople as $person)
                                <li class="weekly-summary-popover-line d-flex justify-content-between align-items-center">
                                    <a href="{{ route('employees.show', $person->id) }}" class="text-decoration-none min-w-0 flex-grow-1">
                                        <span class="fw-semibold">{{ $person->name }}</span>
                                    </a>
                                    @if($person->rotation_end)
                                        <span class="weekly-summary-popover-value">{{ \Illuminate\Support\Carbon::parse($person->rotation_end)->format('d.m.Y') }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="weekly-summary-popover-lead mb-0">Nikt z przypisanych na przyszły tydzień nie kończy rotacji w tym oknie.</p>
                    @endif
                </div>

                <div x-show="active === 'returns'" x-cloak>
                    @if($returnTrips->isNotEmpty())
                        @php
                            $returnSeatCount = 0;
                            $returnPeopleByStatus = [
                                'oczekuje' => 0,
                                'w trakcie' => 0,
                                'zakończone' => 0,
                            ];
                            foreach ($returnTrips as $returnRow) {
                                $seats = $returnRow->participants
                                    ->filter(fn ($participant) => $participant->employee !== null)
                                    ->unique('employee_id')
                                    ->count();
                                $returnSeatCount += $seats;
                                $rowStatus = $returnRow->getVisualStatus();
                                if (array_key_exists($rowStatus, $returnPeopleByStatus)) {
                                    $returnPeopleByStatus[$rowStatus] += $seats;
                                }
                            }
                            $returnVerb = match (true) {
                                $returnSeatCount === 1 => 'osoba zjeżdża',
                                $returnSeatCount % 10 >= 2 && $returnSeatCount % 10 <= 4 && ($returnSeatCount % 100 < 12 || $returnSeatCount % 100 > 14) => 'osoby zjeżdżają',
                                default => 'osób zjeżdża',
                            };
                        @endphp
                        <div class="d-flex justify-content-between align-items-end gap-3 flex-wrap mb-3">
                            <div>
                                <div class="wo-dep-hero">
                                    <span class="wo-dep-hero__n">{{ $returnSeatCount }}</span>
                                    <span class="wo-dep-hero__verb">{{ $returnVerb }}</span>
                                </div>
                                <div class="text-muted small mt-1">
                                    w {{ $returnEventsCount }} {{ $pl($returnEventsCount, 'zjeździe', 'zjazdach', 'zjazdach') }}
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-2 justify-content-end">
                                @if($returnPeopleByStatus['w trakcie'] > 0)
                                    <x-ui.badge variant="warning">{{ $returnPeopleByStatus['w trakcie'] }} w trakcie</x-ui.badge>
                                @endif
                                @if($returnPeopleByStatus['oczekuje'] > 0)
                                    <x-ui.badge variant="info">{{ $returnPeopleByStatus['oczekuje'] }} {{ $pl($returnPeopleByStatus['oczekuje'], 'zaplanowany', 'zaplanowane', 'zaplanowanych') }}</x-ui.badge>
                                @endif
                                @if($returnPeopleByStatus['zakończone'] > 0)
                                    <x-ui.badge variant="success">{{ $returnPeopleByStatus['zakończone'] }} {{ $pl($returnPeopleByStatus['zakończone'], 'zakończony', 'zakończone', 'zakończonych') }}</x-ui.badge>
                                @endif
                            </div>
                        </div>
                        <div class="wo-dep-table-wrap">
                            <table class="wo-dep-table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Dzień</th>
                                        <th>Osoby</th>
                                        <th>Czym</th>
                                        <th>Status</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($returnTrips as $returnTrip)
                                        @php
                                            $uniqueParticipants = $returnTrip->participants
                                                ->filter(fn ($participant) => $participant->employee !== null)
                                                ->unique('employee_id')
                                                ->values();
                                            $participantsCount = $uniqueParticipants->count();
                                            $visualStatus = $returnTrip->getVisualStatus();
                                            $when = $returnTrip->event_date;
                                            $dayLabel = (int) $when->dayOfWeekIso === 4
                                                ? 'czw'
                                                : $when->locale('pl')->isoFormat('dd');
                                            $statusLabel = match ($visualStatus) {
                                                'oczekuje' => 'Zaplanowany',
                                                'w trakcie' => 'W trakcie',
                                                'zakończone' => 'Zakończone',
                                                'anulowany' => 'Anulowany',
                                                default => ucfirst((string) $visualStatus),
                                            };
                                            $badgeVariant = match ($visualStatus) {
                                                'oczekuje' => 'info',
                                                'w trakcie' => 'warning',
                                                'zakończone' => 'success',
                                                'anulowany' => 'danger',
                                                default => 'secondary',
                                            };
                                        @endphp
                                        <tr>
                                            <td style="white-space:nowrap;">
                                                <div class="fw-semibold text-uppercase" style="letter-spacing:0.04em;">{{ $dayLabel }}</div>
                                                <div class="text-muted small" style="font-variant-numeric:tabular-nums;">{{ $when->format('d.m') }}</div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <x-ui.avatar-stack :employees="$uniqueParticipants->pluck('employee')" size="42px" :max="4" />
                                                    <span class="wo-dep-people">{{ $participantsCount }} os.</span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="wo-dep-mode" aria-hidden="true">
                                                        <i class="bi {{ $returnTrip->vehicle ? 'bi-car-front' : 'bi-bus-front' }}"></i>
                                                    </span>
                                                    <span>
                                                        <span class="fw-semibold">{{ $returnTrip->vehicle ? 'auto' : 'publiczny' }}</span>
                                                        @if($returnTrip->vehicle?->registration_number)
                                                            <span class="text-muted small d-block">{{ $returnTrip->vehicle->registration_number }}</span>
                                                        @endif
                                                    </span>
                                                </div>
                                            </td>
                                            <td>
                                                <x-ui.badge variant="{{ $badgeVariant }}">{{ $statusLabel }}</x-ui.badge>
                                            </td>
                                            <td class="text-end">
                                                <x-ui.button variant="ghost" href="{{ route('return-trips.show', $returnTrip) }}" class="btn-sm" title="Zobacz">
                                                    <i class="bi bi-eye"></i>
                                                </x-ui.button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="weekly-summary-popover-lead mb-0">Brak zjazdów w tym tygodniu.</p>
                    @endif
                </div>

                <div x-show="active === 'employees-by-project'" x-cloak>
                    @if($employeesInFieldByProject->isNotEmpty())
                        <ul class="mb-0 list-unstyled">
                            @foreach($employeesInFieldByProject as $row)
                                <li class="weekly-summary-popover-line d-flex justify-content-between align-items-center">
                                    <a href="{{ route('projects.show', $row->project_id) }}" class="text-decoration-none min-w-0 flex-grow-1">
                                        <span class="fw-semibold">{{ $row->project_name }}</span>
                                    </a>
                                    <span class="weekly-summary-popover-value">{{ (int) $row->employee_count }}/{{ (int) ($row->needed_count ?? 0) }}</span>
                                </li>
                            @endforeach
                        </ul>
                        <p class="weekly-summary-popover-tip">
                            Przy każdym kierunku: przypisani / zapotrzebowanie. Na kafelku {{ $employeesInFieldCount }}/{{ $employeesNeededCount }} to unikalne osoby wobec sumy popytu ze wszystkich kierunków. Suma osób po projektach może być wyższa, jeśli ta sama osoba jest w kilku projektach.
                        </p>
                    @else
                        <p class="weekly-summary-popover-lead mb-0">Brak przypisań do projektów przecinających ten tydzień.</p>
                    @endif
                </div>

                <div x-show="active === 'departures'" x-cloak>
                    @if(isset($allDepartures) && $allDepartures->isNotEmpty())
                        @php
                            $departureSeatCount = 0;
                            $departurePeopleByStatus = [
                                'oczekuje' => 0,
                                'w trakcie' => 0,
                                'zakończone' => 0,
                            ];
                            foreach ($allDepartures as $departureRow) {
                                $seats = $departureRow->participants
                                    ->filter(fn ($participant) => $participant->employee !== null)
                                    ->unique('employee_id')
                                    ->count();
                                $departureSeatCount += $seats;
                                $rowStatus = $departureRow->getVisualStatus();
                                if (array_key_exists($rowStatus, $departurePeopleByStatus)) {
                                    $departurePeopleByStatus[$rowStatus] += $seats;
                                }
                            }
                            $departureVerb = match (true) {
                                $departureSeatCount === 1 => 'osoba wyjeżdża',
                                $departureSeatCount % 10 >= 2 && $departureSeatCount % 10 <= 4 && ($departureSeatCount % 100 < 12 || $departureSeatCount % 100 > 14) => 'osoby wyjeżdżają',
                                default => 'osób wyjeżdża',
                            };
                        @endphp
                        <div class="d-flex justify-content-between align-items-end gap-3 flex-wrap mb-3">
                            <div>
                                <div class="wo-dep-hero">
                                    <span class="wo-dep-hero__n">{{ $departureSeatCount }}</span>
                                    <span class="wo-dep-hero__verb">{{ $departureVerb }}</span>
                                </div>
                                <div class="text-muted small mt-1">
                                    w {{ $departureEventsCount }} {{ $pl($departureEventsCount, 'wyjeździe', 'wyjazdach', 'wyjazdach') }}
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-2 justify-content-end">
                                @if($departurePeopleByStatus['w trakcie'] > 0)
                                    <x-ui.badge variant="warning">{{ $departurePeopleByStatus['w trakcie'] }} w trakcie</x-ui.badge>
                                @endif
                                @if($departurePeopleByStatus['oczekuje'] > 0)
                                    <x-ui.badge variant="info">{{ $departurePeopleByStatus['oczekuje'] }} {{ $pl($departurePeopleByStatus['oczekuje'], 'zaplanowany', 'zaplanowane', 'zaplanowanych') }}</x-ui.badge>
                                @endif
                                @if($departurePeopleByStatus['zakończone'] > 0)
                                    <x-ui.badge variant="success">{{ $departurePeopleByStatus['zakończone'] }} {{ $pl($departurePeopleByStatus['zakończone'], 'zakończony', 'zakończone', 'zakończonych') }}</x-ui.badge>
                                @endif
                            </div>
                        </div>
                        <div class="wo-dep-table-wrap">
                            <table class="wo-dep-table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Dzień</th>
                                        <th>Osoby</th>
                                        <th>Czym</th>
                                        <th>Status</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($allDepartures as $departure)
                                        @php
                                            $uniqueParticipants = $departure->participants
                                                ->filter(fn ($participant) => $participant->employee !== null)
                                                ->unique('employee_id')
                                                ->values();
                                            $participantsCount = $uniqueParticipants->count();
                                            $visualStatus = $departure->getVisualStatus();
                                            $when = $departure->end_date ?? $departure->event_date;
                                            $dayLabel = (int) $when->dayOfWeekIso === 4
                                                ? 'czw'
                                                : $when->locale('pl')->isoFormat('dd');
                                            $statusLabel = match ($visualStatus) {
                                                'oczekuje' => 'Zaplanowany',
                                                'w trakcie' => 'W trakcie',
                                                'zakończone' => 'Zakończone',
                                                'anulowany' => 'Anulowany',
                                                default => ucfirst((string) $visualStatus),
                                            };
                                            $badgeVariant = match ($visualStatus) {
                                                'oczekuje' => 'info',
                                                'w trakcie' => 'warning',
                                                'zakończone' => 'success',
                                                'anulowany' => 'danger',
                                                default => 'secondary',
                                            };
                                        @endphp
                                        <tr>
                                            <td style="white-space:nowrap;">
                                                <div class="fw-semibold text-uppercase" style="letter-spacing:0.04em;">{{ $dayLabel }}</div>
                                                <div class="text-muted small" style="font-variant-numeric:tabular-nums;">{{ $when->format('d.m') }}</div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <x-ui.avatar-stack :employees="$uniqueParticipants->pluck('employee')" size="42px" :max="4" />
                                                    <span class="wo-dep-people">{{ $participantsCount }} os.</span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="wo-dep-mode" aria-hidden="true">
                                                        <i class="bi {{ $departure->vehicle ? 'bi-car-front' : 'bi-bus-front' }}"></i>
                                                    </span>
                                                    <span>
                                                        <span class="fw-semibold">{{ $departure->vehicle ? 'auto' : 'publiczny' }}</span>
                                                        @if($departure->vehicle?->registration_number)
                                                            <span class="text-muted small d-block">{{ $departure->vehicle->registration_number }}</span>
                                                        @endif
                                                    </span>
                                                </div>
                                            </td>
                                            <td>
                                                <x-ui.badge variant="{{ $badgeVariant }}">{{ $statusLabel }}</x-ui.badge>
                                            </td>
                                            <td class="text-end">
                                                <x-ui.button variant="ghost" href="{{ route('departures.show', $departure) }}" class="btn-sm" title="Zobacz">
                                                    <i class="bi bi-eye"></i>
                                                </x-ui.button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="weekly-summary-popover-lead mb-0">Brak wyjazdów w tym tygodniu.</p>
                    @endif
                </div>
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
        openPanel(type, event) {
            event.preventDefault();
            event.stopPropagation();
            this.active = type;
            document.documentElement.style.overflow = 'hidden';
        },
        close() {
            this.active = null;
            document.documentElement.style.overflow = '';
        },
        panelTitle() {
            const map = {
                'rotations-ending': 'Kończą rotację',
                transfers: 'Transfery (tydzień)',
                returns: 'Zjazdy (tydzień)',
                departures: 'Wyjazdy (tydzień)',
                'employees-by-project': 'Pracownicy wg projektu',
                housing: 'Domy (tydzień)',
                'field-vehicles': 'Auta w terenie',
                bench: 'Ławka',
                'to-send': 'Do dosłania',
            };

            return map[this.active] || '';
        },
        panelIconClass() {
            const map = {
                'rotations-ending': 'bi-hourglass-bottom',
                transfers: 'bi-arrow-left-right',
                returns: 'bi-arrow-return-left',
                departures: 'bi-arrow-right-circle',
                'employees-by-project': 'bi-people',
                housing: 'bi-house-door',
                'field-vehicles': 'bi-car-front',
                bench: 'bi-person-workspace',
                'to-send': 'bi-box-arrow-up-right',
            };

            return map[this.active] || 'bi-info-circle';
        },
    }));
});
</script>
@endpush
@endonce
