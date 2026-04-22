<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Szczegóły transferu">
            <x-slot name="left">
                <x-ui.button
                    variant="ghost"
                    href="{{ route('transfers.index') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
            <x-slot name="right">
                @if(in_array($transfer->status, [\App\Enums\LogisticsEventStatus::PLANNED, \App\Enums\LogisticsEventStatus::COMPLETED]))
                    <form method="POST" action="{{ route('transfers.cancel', $transfer) }}" class="d-inline">
                        @csrf
                        <x-ui.button variant="danger" type="submit" onclick="return confirm('{{ $transfer->has_reassignment ? 'Anulować ten transfer? Przypisania zostaną przywrócone do stanu sprzed transferu.' : 'Anulować ten transfer?' }}')">
                            <i class="bi bi-x-circle me-1"></i> Anuluj transfer
                        </x-ui.button>
                    </form>
                @endif
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    @if(session('success'))
        <x-alert type="success" dismissible icon="check-circle">{{ session('success') }}</x-alert>
    @endif
    @if(session('error'))
        <x-alert type="danger" dismissible icon="exclamation-triangle">{{ session('error') }}</x-alert>
    @endif

    @once('transfer-show-route-note-styles')
        <style>
            .transfer-route-stop-note {
                background: linear-gradient(90deg, rgba(99, 102, 241, 0.22) 0%, rgba(30, 41, 59, 0.85) 100%);
                border: 1px solid rgba(165, 180, 252, 0.45);
                border-left: 4px solid #a5b4fc;
                color: #f1f5f9;
                line-height: 1.45;
                box-shadow: 0 1px 0 rgba(255, 255, 255, 0.06) inset;
            }
            .transfer-route-stop-note__label {
                display: block;
                font-size: 0.65rem;
                font-weight: 700;
                letter-spacing: 0.06em;
                text-transform: uppercase;
                color: #c7d2fe;
                margin-bottom: 0.25rem;
            }
            .transfer-route-stop-note__text {
                display: block;
                color: #f8fafc;
                font-weight: 500;
            }
        </style>
    @endonce

    <!-- Podstawowe informacje -->
    <x-ui.card label="Informacje podstawowe" class="mb-4">
        <div class="row g-4">
            <div class="col-md-4">
                <h6 class="text-muted small mb-1">Data i godzina</h6>
                <p class="fw-semibold mb-0">{{ $transfer->event_date->format('d.m.Y H:i') }}</p>
            </div>
            <div class="col-md-4">
                <h6 class="text-muted small mb-1">Status</h6>
                @php
                    $visualStatus = $transfer->getVisualStatus();
                    $badgeVariant = match($visualStatus) {
                        'oczekuje' => 'primary',
                        'w trakcie' => 'warning',
                        'zakończone' => 'success',
                        'anulowany' => 'danger',
                        default => 'accent'
                    };
                @endphp
                <x-ui.badge variant="{{ $badgeVariant }}">{{ ucfirst($visualStatus) }}</x-ui.badge>
            </div>
            <div class="col-md-4">
                <h6 class="text-muted small mb-1">Środek transportu</h6>
                @if($transfer->vehicle)
                    <p class="fw-semibold mb-0">
                        Pojazd służbowy:
                        {{ $transfer->vehicle->registration_number }}
                        @if($transfer->vehicle->brand || $transfer->vehicle->model)
                            <small class="text-muted">— {{ trim($transfer->vehicle->brand . ' ' . $transfer->vehicle->model) }}</small>
                        @endif
                    </p>
                @elseif($transfer->has_transport)
                    <p class="fw-semibold mb-0">Transport publiczny</p>
                    @if(($publicHubKind ?? null) === 'airport')
                        <small class="text-muted d-block">Lotnisko — trasa lotnicza (samolot)</small>
                    @elseif(($publicHubKind ?? null) === 'station')
                        <small class="text-muted d-block">Dworzec — transport naziemny (autobus / pociąg)</small>
                    @else
                        <small class="text-muted d-block">Bez pojazdu służbowego</small>
                    @endif
                @else
                    <span class="text-muted">Bez pojazdu służbowego</span>
                @endif
            </div>
            <div class="col-md-6">
                <h6 class="text-muted small mb-1"><i class="bi bi-geo-alt text-danger me-1"></i>Skąd</h6>
                <p class="fw-semibold mb-0">{{ $transfer->fromLocation?->name ?? '—' }}</p>
                @if($transfer->fromLocation?->city)
                    <small class="text-muted">{{ $transfer->fromLocation->city }}</small>
                @endif
            </div>
            <div class="col-md-6">
                <h6 class="text-muted small mb-1"><i class="bi bi-geo-alt-fill text-success me-1"></i>Dokąd</h6>
                <p class="fw-semibold mb-0">{{ $transfer->toLocation?->name ?? '—' }}</p>
                @if($transfer->toLocation?->city)
                    <small class="text-muted">{{ $transfer->toLocation->city }}</small>
                @endif
            </div>
            @if($transfer->notes)
                <div class="col-12">
                    <h6 class="text-muted small mb-1">Notatki</h6>
                    <p class="mb-0">{{ $transfer->notes }}</p>
                </div>
            @endif
        </div>
    </x-ui.card>

    <!-- Trasa -->
    @php $isPublicTransport = ! $transfer->vehicle_id && $transfer->has_transport; @endphp
    <x-ui.card label="Trasa" class="mb-4">
        <div class="row g-4">
            @if(! $isPublicTransport)
                <div class="col-md-4">
                    <h6 class="text-muted small mb-1">Dystans</h6>
                    <p class="fw-semibold mb-0">{{ $transfer->getFormattedDistance() ?? '—' }}</p>
                </div>
                <div class="col-md-4">
                    <h6 class="text-muted small mb-1">Czas</h6>
                    <p class="fw-semibold mb-0">{{ $transfer->getFormattedDuration() ?? '—' }}</p>
                </div>
                <div class="col-md-4">
                    <h6 class="text-muted small mb-1">Przystanki</h6>
                    <p class="fw-semibold mb-0">{{ $routeStopCount ?? ($routeStopRows->count() ?? 0) }}</p>
                </div>
            @else
                <div class="col-12">
                    <p class="small mb-0" style="color: #94a3b8;">
                        <i class="bi bi-info-circle me-1"></i>
                        Transport publiczny — dystans i czas nie są obliczane automatycznie.
                    </p>
                </div>
            @endif
        </div>

        <div class="mt-3">
            @if(isset($routeStopRows) && $routeStopRows->count() > 0)
                <div class="d-flex flex-column gap-2">
                    @foreach($routeStopRows as $i => $row)
                        @php
                            $isStart = $i === 0;
                            $isEnd = $i === ($routeStopRows->count() - 1);
                            $badge = $isStart ? 'Start' : ($isEnd ? 'Cel' : 'Przystanek');
                            $badgeVariant = $isStart ? 'primary' : ($isEnd ? 'success' : 'accent');
                            $locModel = ($row['kind'] ?? '') === 'extra_location'
                                ? ($routeStopLocationsById[$row['model_id']] ?? null)
                                : null;
                        @endphp
                        <div class="p-3 border rounded-3 d-flex align-items-start gap-2 w-100">
                            <x-ui.badge variant="{{ $badgeVariant }}">{{ $badge }}</x-ui.badge>
                            <div class="min-w-0 flex-grow-1">
                                <div class="fw-semibold">{{ $row['name'] ?? '—' }}</div>
                                @if(!empty($row['address_line']))
                                    <div class="text-muted small">{{ $row['address_line'] }}</div>
                                @endif
                                @if(!empty($row['employees_label']))
                                    <div class="text-muted small mt-1">{{ $row['employees_label'] }}</div>
                                @endif
                                @if(!empty($row['purpose']))
                                    <div class="small mt-2 px-3 py-2 rounded-3 transfer-route-stop-note">
                                        <span class="transfer-route-stop-note__label">Notatka</span>
                                        <span class="transfer-route-stop-note__text">{{ $row['purpose'] }}</span>
                                    </div>
                                @endif
                                @if($locModel && !$locModel->hasCoordinates())
                                    <div class="text-warning small mt-1">
                                        <i class="bi bi-exclamation-triangle"></i> brak współrzędnych
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <x-ui.empty-state icon="map" message="Brak zapisanej trasy (brak przystanków)" />
            @endif
        </div>
    </x-ui.card>

    @if(! $transfer->vehicle_id)
        <x-logistics.ground-transfer-tickets :rows="$groundLegTicketRows ?? []" />

        @php $savedTickets = $transfer->transportCosts->where('cost_type', 'ticket')->values(); @endphp
        <x-ui.card label="Bilety (zapisane przy transferze)" class="mb-4">
            @if($savedTickets->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0 align-middle">
                        <thead>
                            <tr class="text-muted small">
                                <th>Opis</th>
                                <th>Kwota</th>
                                <th class="text-end">Załącznik</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($savedTickets as $tc)
                                @php $u = \App\Support\PublicDiskFileUrl::url($tc->file_path); @endphp
                                <tr>
                                    <td>
                                        <div>{{ $tc->description ?: 'Bilet' }}</div>
                                        @if($tc->notes)
                                            <div class="text-muted small">{{ $tc->notes }}</div>
                                        @endif
                                    </td>
                                    <td>{{ number_format((float) $tc->amount, 2) }} {{ $tc->currency }}</td>
                                    <td class="text-end">
                                        @if($u)
                                            <a href="{{ $u }}" target="_blank" rel="noopener" class="text-decoration-none">
                                                <i class="bi bi-paperclip"></i> Podgląd
                                            </a>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted small mb-0">Brak zapisanych kosztów biletu przy tym transferze.</p>
            @endif
        </x-ui.card>
    @endif

    <!-- Uczestnicy -->
    <x-ui.card label="Uczestnicy" class="mb-4">
        @if($transfer->participants->count() > 0)
            @php
                $uniqueEmployees = $transfer->participants
                    ->filter(fn($p) => $p->employee)
                    ->unique('employee_id');
            @endphp
            <div class="row g-2">
                @foreach($uniqueEmployees as $participant)
                    <div class="col-md-4 col-lg-3">
                        <div class="d-flex align-items-center gap-2 p-2 border rounded-3">
                            <i class="bi bi-person text-primary"></i>
                            <div>
                                <div class="fw-semibold small">{{ $participant->employee->full_name }}</div>
                                @if($participant->employee->phone)
                                    <div class="text-muted" style="font-size: 0.75rem;">{{ $participant->employee->phone }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-muted mb-0">Brak uczestników.</p>
        @endif
    </x-ui.card>

    @if($transfer->has_reassignment)
    <!-- Zmiany przypisań -->
    <x-ui.card label="Zmiany przypisań (przeniesienie)" class="mb-4">
        <x-ui.badge variant="info" class="mb-2">
            <i class="bi bi-arrow-left-right me-1"></i> Transfer z przeniesieniem
        </x-ui.badge>
        <p class="text-muted small mb-4">
            Dla każdej kategorii: <strong>stare przypisanie</strong> → <strong>nowe przypisanie</strong>.
        </p>

        @php
            $reassignmentTypeLabels = [
                'project_assignment' => 'Projekt',
                'accommodation_assignment' => 'Mieszkanie',
                'vehicle_assignment' => 'Pojazd',
            ];
            $participantsByEmployee = $transfer->participants->groupBy('employee_id');
        @endphp

        @foreach($participantsByEmployee as $employeeId => $parts)
            @php
                $employee = $parts->first()->employee;
                $hasAssignmentRows = $parts->contains(fn ($p) => filled($p->assignment_type));
            @endphp

            @if($employee)
            <div class="border rounded-3 p-3 mb-3">
                <h6 class="fw-semibold mb-3">
                    <i class="bi bi-person-fill text-primary me-1"></i>
                    {{ $employee->full_name }}
                </h6>

                @if(! $hasAssignmentRows)
                    <p class="text-muted small mb-0">Bez zmiany przypisań (zachowano poprzednie).</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm table-borderless mb-0 align-middle" style="--bs-table-bg: transparent;">
                            <thead>
                                <tr class="text-muted small">
                                    <th scope="col" class="pb-2">Kategoria</th>
                                    <th scope="col" class="pb-2">Stare przypisanie</th>
                                    <th scope="col" class="pb-2 text-center" style="width: 2rem;"></th>
                                    <th scope="col" class="pb-2">Nowe przypisanie</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($reassignmentTypeLabels as $typeKey => $typeLabel)
                                    @php
                                        $oldP = $parts->first(fn ($p) => $p->assignment_type === $typeKey && $p->original_end_date !== null);
                                        $newP = $parts->first(fn ($p) => $p->assignment_type === $typeKey && $p->original_end_date === null);
                                        $oldA = $oldP?->assignment;
                                        $newA = $newP?->assignment;
                                        $oldText = '—';
                                        $newText = '—';
                                        if ($oldP && $oldA) {
                                            if ($typeKey === 'project_assignment') {
                                                $oldText = $oldA->project?->name ?? ('#'.$oldP->assignment_id);
                                                $oldText .= $oldP->original_end_date ? ' (do '.$oldP->original_end_date->format('d.m.Y').')' : '';
                                            } elseif ($typeKey === 'accommodation_assignment') {
                                                $oldText = $oldA->accommodation?->name ?? ('#'.$oldP->assignment_id);
                                                $oldText .= $oldP->original_end_date ? ' (do '.$oldP->original_end_date->format('d.m.Y').')' : '';
                                            } elseif ($typeKey === 'vehicle_assignment') {
                                                $oldText = $oldA->vehicle?->registration_number ?? ('#'.$oldP->assignment_id);
                                                $pos = $oldA->position ?? null;
                                                $oldText .= $pos ? ' ('.$pos->label().')' : '';
                                                $oldText .= $oldP->original_end_date ? ' (do '.$oldP->original_end_date->format('d.m.Y').')' : '';
                                            }
                                        }
                                        if ($newP && $newA) {
                                            if ($typeKey === 'project_assignment') {
                                                $newText = $newA->project?->name ?? ('#'.$newP->assignment_id);
                                            } elseif ($typeKey === 'accommodation_assignment') {
                                                $newText = $newA->accommodation?->name ?? ('#'.$newP->assignment_id);
                                            } elseif ($typeKey === 'vehicle_assignment') {
                                                $newText = $newA->vehicle?->registration_number ?? ('#'.$newP->assignment_id);
                                                $pos = $newA->position ?? null;
                                                $newText .= $pos ? ' ('.$pos->label().')' : '';
                                            }
                                            if ($newA->start_date) {
                                                $newText .= ' ('.$newA->start_date->format('d.m.Y').' – '.($newA->end_date?->format('d.m.Y') ?? '∞').')';
                                            }
                                        }
                                        $showRow = $oldP || $newP;
                                    @endphp
                                    @if($showRow)
                                        <tr>
                                            <td class="text-muted small fw-semibold text-nowrap pe-2">{{ $typeLabel }}</td>
                                            <td class="small">{{ $oldText }}</td>
                                            <td class="text-center text-muted small px-1">→</td>
                                            <td class="small">{{ $newText }}</td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
            @endif
        @endforeach
    </x-ui.card>
    @endif

    <!-- Wynagrodzenie kierowcy -->
    @if($transfer->driverAdjustments->count() > 0)
        <x-ui.card label="Wynagrodzenie kierowcy" class="mb-4">
            @foreach($transfer->driverAdjustments as $adj)
                <div class="d-flex align-items-start justify-content-between flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <i class="bi bi-person-badge fs-4 text-primary"></i>
                        <div>
                            <div class="fw-semibold">{{ $adj->employee?->full_name ?? '—' }}</div>
                            <div class="text-success fw-semibold fs-5">
                                {{ number_format($adj->amount, 2) }} {{ $adj->currency }}
                            </div>
                        </div>
                    </div>
                    <div class="text-end">
                        @if($adj->payroll_id && $adj->payroll)
                            <div class="small text-muted">Payroll:</div>
                            <a href="{{ route('payrolls.show', $adj->payroll) }}" class="small">
                                {{ $adj->payroll->display_name }}
                            </a>
                        @else
                            <x-ui.badge variant="warning">Bez payrollu</x-ui.badge>
                            <div class="small text-muted mt-1">
                                Przypisz payroll w
                                <a href="{{ route('adjustments.edit', $adj) }}">edycji uznania</a>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </x-ui.card>
    @endif

    <!-- Meta -->
    <x-ui.card label="Informacje systemowe">
        <div class="row g-3">
            <div class="col-md-4">
                <h6 class="text-muted small mb-1">ID</h6>
                <p class="mb-0 font-monospace">{{ $transfer->id }}</p>
            </div>
            <div class="col-md-4">
                <h6 class="text-muted small mb-1">Utworzony przez</h6>
                <p class="mb-0">{{ $transfer->creator?->name ?? '—' }}</p>
            </div>
            <div class="col-md-4">
                <h6 class="text-muted small mb-1">Data utworzenia</h6>
                <p class="mb-0">{{ $transfer->created_at->format('d.m.Y H:i') }}</p>
            </div>
        </div>
    </x-ui.card>

    <x-comments
        :commentable="$transfer"
        commentable-type="logistics_event"
    />
</x-app-layout>
