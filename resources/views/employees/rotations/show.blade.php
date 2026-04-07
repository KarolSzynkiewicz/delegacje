<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Rotacja: {{ $employee->full_name }}">
            <x-slot name="left">
                <x-ui.button
                    variant="ghost"
                    href="{{ route('employees.rotations.index', $employee) }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
            <x-slot name="right">
                <x-ui.button
                    variant="ghost"
                    href="{{ route('employees.rotations.edit', [$employee, $rotation]) }}"
                    action="edit"
                >
                    Edytuj
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    @php
        $fmtMoney = static function (float $amount, string $currency): string {
            return number_format($amount, 2, ',', ' ') . ' ' . $currency;
        };
    @endphp

    <div class="row g-4">
        <div class="col-12 col-xl-5">
            <x-ui.card label="Szczegóły rotacji">
                <div class="row mb-3">
                    <div class="col-sm-4">
                        <strong class="text-muted">Pracownik:</strong>
                    </div>
                    <div class="col-sm-8">
                        <a href="{{ route('employees.show', $employee) }}" class="text-decoration-none">
                            {{ $employee->full_name }}
                        </a>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-sm-4">
                        <strong class="text-muted">Data rozpoczęcia:</strong>
                    </div>
                    <div class="col-sm-8">
                        {{ $rotation->start_date->format('d.m.Y') }}
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-sm-4">
                        <strong class="text-muted">Data zakończenia:</strong>
                    </div>
                    <div class="col-sm-8">
                        {{ $rotation->end_date->format('d.m.Y') }}
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-sm-4">
                        <strong class="text-muted">Status:</strong>
                    </div>
                    <div class="col-sm-8">
                        @php
                            $today = now()->toDateString();
                            $isActive = $rotation->start_date->toDateString() <= $today &&
                                $rotation->end_date->toDateString() >= $today;
                            $isCompleted = $rotation->end_date->toDateString() < $today;
                            $isScheduled = $rotation->start_date->toDateString() > $today;
                            $isCancelled = $rotation->status === 'cancelled';
                            $badgeVariant = match (true) {
                                $isCancelled => 'danger',
                                $isActive => 'success',
                                $isCompleted => 'accent',
                                $isScheduled => 'info',
                                default => 'accent',
                            };
                            $badgeLabel = match (true) {
                                $isCancelled => 'Anulowana',
                                $isActive => 'Aktywna',
                                $isCompleted => 'Zakończona',
                                $isScheduled => 'Zaplanowana',
                                default => 'Nieznany',
                            };
                        @endphp
                        <x-ui.badge variant="{{ $badgeVariant }}">{{ $badgeLabel }}</x-ui.badge>
                    </div>
                </div>

                @if($rotation->notes)
                    <div class="row mb-3">
                        <div class="col-sm-4">
                            <strong class="text-muted">Uwagi:</strong>
                        </div>
                        <div class="col-sm-8">
                            {{ $rotation->notes }}
                        </div>
                    </div>
                @endif

                <div class="row mb-0">
                    <div class="col-sm-4">
                        <strong class="text-muted">Utworzono:</strong>
                    </div>
                    <div class="col-sm-8">
                        {{ $rotation->created_at->format('d.m.Y H:i') }}
                    </div>
                </div>
            </x-ui.card>
        </div>

        <div class="col-12 col-xl-7">
            <x-ui.card label="Godziny, stawka i rozliczenie (okres rotacji)">
                <p class="text-muted small mb-3">
                    Godziny z ewidencji czasu pracy w datach rotacji. Kwota z godzin = suma
                    (godziny × stawka obowiązująca w dniu wpisu), jak przy listach płac.
                    Kary i nagrody: wpisy z datą w tym okresie.
                </p>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <div class="border rounded-3 p-3 h-100 bg-body-secondary bg-opacity-25">
                            <div class="text-muted small">Wypracowane godziny</div>
                            <div class="fs-4 fw-semibold">
                                {{ number_format($rotationSummary['total_hours'], 2, ',', ' ') }} h
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="border rounded-3 p-3 h-100">
                            <div class="text-muted small mb-2">Zarobek z godzin (× stawka)</div>
                            @forelse($rotationSummary['earnings_by_currency'] as $cur => $amt)
                                <div class="d-flex justify-content-between align-items-center py-1">
                                    <span class="text-muted">{{ $cur }}</span>
                                    <span class="fw-semibold">{{ $fmtMoney($amt, $cur) }}</span>
                                </div>
                            @empty
                                <span class="text-muted">Brak wpisów czasu lub brak stawki w tych dniach.</span>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <div class="small text-muted mb-1">Nagrody (bonusy)</div>
                        @forelse($rotationSummary['bonus_by_currency'] as $cur => $amt)
                            <div class="d-flex justify-content-between">
                                <span>{{ $cur }}</span>
                                <span class="text-success fw-medium">+ {{ $fmtMoney($amt, $cur) }}</span>
                            </div>
                        @empty
                            <span class="text-muted small">—</span>
                        @endforelse
                    </div>
                    <div class="col-md-6">
                        <div class="small text-muted mb-1">Kary</div>
                        @forelse($rotationSummary['penalty_by_currency'] as $cur => $amt)
                            <div class="d-flex justify-content-between">
                                <span>{{ $cur }}</span>
                                <span class="text-danger fw-medium">− {{ $fmtMoney($amt, $cur) }}</span>
                            </div>
                        @empty
                            <span class="text-muted small">—</span>
                        @endforelse
                    </div>
                </div>

                @if(!empty($rotationSummary['net_by_currency']))
                    <div class="border-top pt-3">
                        <div class="small text-muted mb-2">Łącznie (godziny + nagrody − kary), per waluta</div>
                        @foreach($rotationSummary['net_by_currency'] as $cur => $amt)
                            <div class="d-flex justify-content-between align-items-center fs-5">
                                <span>{{ $cur }}</span>
                                <span class="fw-bold">{{ $fmtMoney($amt, $cur) }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-ui.card>
        </div>

        <div class="col-12 col-lg-6">
            <x-ui.card label="Zakwaterowanie w okresie rotacji">
                @if($rotationSummary['accommodation_assignments']->isEmpty())
                    <p class="text-muted mb-0">Brak przypisań do mieszkań nachodzących na ten okres.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Okres</th>
                                    <th>Miejsce</th>
                                    <th class="text-end">Akcja</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rotationSummary['accommodation_assignments'] as $aa)
                                    <tr>
                                        <td class="text-nowrap small">
                                            {{ $aa->start_date->format('d.m.Y') }}
                                            —
                                            {{ $aa->end_date ? $aa->end_date->format('d.m.Y') : '…' }}
                                        </td>
                                        <td>
                                            {{ $aa->accommodation?->name ?? '—' }}
                                            @if($aa->accommodation?->location)
                                                <span class="text-muted small">({{ $aa->accommodation->location->name }})</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('accommodation-assignments.show', $aa) }}" class="text-decoration-none small">Szczegóły</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-ui.card>
        </div>

        <div class="col-12 col-lg-6">
            <x-ui.card label="Pojazdy w okresie rotacji">
                @if($rotationSummary['vehicle_assignments']->isEmpty())
                    <p class="text-muted mb-0">Brak przypisań do pojazdów nachodzących na ten okres.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Okres</th>
                                    <th>Pojazd</th>
                                    <th class="text-end">Akcja</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rotationSummary['vehicle_assignments'] as $va)
                                    <tr>
                                        <td class="text-nowrap small">
                                            {{ $va->start_date->format('d.m.Y') }}
                                            —
                                            {{ $va->end_date ? $va->end_date->format('d.m.Y') : '…' }}
                                        </td>
                                        <td>
                                            @if($va->vehicle)
                                                <span class="fw-medium">{{ $va->vehicle->registration_number }}</span>
                                                @if($va->vehicle->brand || $va->vehicle->model)
                                                    <span class="text-muted small">
                                                        {{ trim(($va->vehicle->brand ?? '').' '.($va->vehicle->model ?? '')) }}
                                                    </span>
                                                @endif
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('vehicle-assignments.show', $va) }}" class="text-decoration-none small">Szczegóły</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-ui.card>
        </div>

        @if($rotationSummary['adjustments']->isNotEmpty())
            <div class="col-12">
                <x-ui.card label="Kary i nagrody (lista)">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Data</th>
                                    <th>Typ</th>
                                    <th>Kwota</th>
                                    <th>Uwagi</th>
                                    <th class="text-end">Powiązanie</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rotationSummary['adjustments'] as $adj)
                                    <tr>
                                        <td class="text-nowrap">{{ $adj->date->format('d.m.Y') }}</td>
                                        <td>
                                            @if($adj->type === 'bonus')
                                                <x-ui.badge variant="success">Nagroda</x-ui.badge>
                                            @else
                                                <x-ui.badge variant="danger">Kara</x-ui.badge>
                                            @endif
                                        </td>
                                        <td class="text-nowrap">{{ $fmtMoney((float) $adj->amount, $adj->currency ?? 'PLN') }}</td>
                                        <td class="small text-muted">{{ $adj->notes ?: '—' }}</td>
                                        <td class="text-end small">
                                            @if($adj->payroll_id)
                                                <a href="{{ route('payrolls.show', $adj->payroll_id) }}" class="text-decoration-none">Lista płac</a>
                                            @else
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-ui.card>
            </div>
        @endif

        <div class="col-12">
            <x-ui.card label="Delegacja w tym okresie (odczyt)">
                <p class="text-muted small mb-3">
                    Chronologicznie: wyjazdy, zjazdy, transfery (także bez zmiany przypisań), przypisania do projektów
                    oraz mieszkania/pojazdy nachodzące na daty rotacji. Linki prowadzą do szczegółów w systemie.
                </p>
                @if($fieldHistoryTimeline->isEmpty())
                    <p class="mb-0 text-muted">Brak wpisów w tym zakresie dat.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">Data</th>
                                    <th scope="col">Rodzaj</th>
                                    <th scope="col">Opis</th>
                                    <th scope="col" class="text-end">Szczegóły</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($fieldHistoryTimeline as $row)
                                    <tr>
                                        <td class="text-nowrap">{{ $row['at']->format('d.m.Y') }}</td>
                                        <td>{{ $row['label'] }}</td>
                                        <td class="text-muted small">{{ $row['description'] ?? '—' }}</td>
                                        <td class="text-end text-nowrap">
                                            @if(!empty($row['url']))
                                                <a href="{{ $row['url'] }}" class="text-decoration-none">Otwórz</a>
                                            @else
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
