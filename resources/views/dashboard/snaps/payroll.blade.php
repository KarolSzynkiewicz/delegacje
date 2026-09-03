@php
    $batch = $snaps['payrollBatch'];
    $payrolls = $snaps['payrolls'];
    $doc = $snaps['payrollDocument'];
    $payroll = $doc['payroll'];
    $hoursBreakdown = $doc['hoursBreakdown'];
    $periodStart = $snaps['today']->copy()->subMonth()->startOfMonth()->format('Y-m-d');
    $periodEnd = $snaps['today']->copy()->subMonth()->endOfMonth()->format('Y-m-d');
@endphp

<x-dashboard.snap
    kicker="Payroll"
    title="Generuj dla wszystkich → lista płac"
    caption="Kliknij przycisk — system zbiera ludzi z godzinami w okresie, liczy stawkę × godziny i układa listy. Poniżej przykładowa lista i dokument jednej osoby."
    :href="Route::has('payrolls.index') ? route('payrolls.index') : null"
    :interactive="true"
    tall
>
    <div
        class="dash-payroll"
        data-people="{{ e(json_encode($batch, JSON_UNESCAPED_UNICODE)) }}"
        x-data="{
            people: JSON.parse($el.dataset.people),
            visible: [],
            running: false,
            done: false,
            generate() {
                if (this.running) return;
                this.visible = [];
                this.done = false;
                this.running = true;
                const rows = this.people;
                let i = 0;
                const tick = () => {
                    if (i >= rows.length) {
                        this.running = false;
                        this.done = true;
                        return;
                    }
                    this.visible.push(rows[i]);
                    i += 1;
                    setTimeout(tick, 280);
                };
                setTimeout(tick, 420);
            }
        }"
    >
        <x-ui.card label="Generuj Payroll dla wszystkich pracowników" class="mb-4">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Data od</label>
                    <input type="date" class="form-control" value="{{ $periodStart }}" readonly>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Data do</label>
                    <input type="date" class="form-control" value="{{ $periodEnd }}" readonly>
                </div>
            </div>
            <div class="d-flex justify-content-end">
                <x-ui.button
                    variant="primary"
                    type="button"
                    action="filter"
                    class="xuiv2-magnetic"
                    x-bind:disabled="running"
                    x-on:click="generate()"
                >
                    <span x-show="!running && !done">Wygeneruj dla wszystkich</span>
                    <span x-show="running" x-cloak>
                        <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                        Pobieram listę ludzi…
                    </span>
                    <span x-show="done && !running" x-cloak>Wygenerowano · odtwórz</span>
                </x-ui.button>
            </div>
        </x-ui.card>

        <div class="mb-4" x-show="visible.length" x-cloak>
            <x-ui.card label="Wynik generowania">
                <p class="small text-muted mb-3">
                    Znaleziono <span class="font-mono" x-text="visible.length"></span> pracowników z godzinami w okresie.
                </p>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Pracownik</th>
                                <th class="text-end">Godziny</th>
                                <th class="text-end">Stawka</th>
                                <th class="text-end">Zarobki</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="row in visible" :key="row.employee.id">
                                <tr>
                                    <td>
                                        <span class="fw-semibold" x-text="row.employee.full_name"></span>
                                        <div class="small text-muted" x-text="row.employee.roles?.[0]?.name"></div>
                                    </td>
                                    <td class="text-end font-mono" x-text="row.hours"></td>
                                    <td class="text-end font-mono" x-text="Number(row.rate).toFixed(2) + ' ' + row.currency"></td>
                                    <td class="text-end font-mono fw-semibold text-success" x-text="Number(row.amount).toLocaleString('pl-PL', {minimumFractionDigits: 2}) + ' ' + row.currency"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
        </div>
    </div>

    <x-ui.card class="mb-4" label="Lista payrolli">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Pracownik</th>
                        <th>Daty</th>
                        <th>Kwota z godzin</th>
                        <th>Razem</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payrolls as $row)
                        @php
                            $badgeVariant = match($row->status->value) {
                                'draft' => 'accent',
                                'issued' => 'warning',
                                'approved' => 'info',
                                'paid' => 'success',
                                default => 'accent',
                            };
                        @endphp
                        <tr>
                            <td><x-employee-cell :employee="$row->employee" :link="false" /></td>
                            <td class="small">
                                {{ $row->period_start->format('d-m-Y') }}<br>
                                {{ $row->period_end->format('d-m-Y') }}
                            </td>
                            <td class="font-mono">{{ number_format($row->hours_amount, 2, ',', ' ') }} {{ $row->currency }}</td>
                            <td class="font-mono fw-semibold">{{ number_format($row->total_amount, 2, ',', ' ') }} {{ $row->currency }}</td>
                            <td><x-ui.badge :variant="$badgeVariant">{{ $row->status->label() }}</x-ui.badge></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <x-ui.card class="payroll-doc-card" label="Przykładowa lista płac">
        <div class="text-center mb-4 pb-3 border-bottom">
            <h1 class="h3 fw-bold mb-2">LISTA PŁAC</h1>
            <p class="text-muted mb-0">Okres rozliczeniowy</p>
        </div>
        <div class="row mb-4">
            <div class="col-md-6">
                <x-ui.detail-list>
                    <x-ui.detail-item label="Pracownik:">
                        <strong>{{ $payroll->employee->full_name }}</strong>
                    </x-ui.detail-item>
                    <x-ui.detail-item label="Okres od:">
                        {{ $payroll->period_start->format('d.m.Y') }}
                    </x-ui.detail-item>
                    <x-ui.detail-item label="Okres do:">
                        {{ $payroll->period_end->format('d.m.Y') }}
                    </x-ui.detail-item>
                </x-ui.detail-list>
            </div>
            <div class="col-md-6 text-md-end">
                <x-ui.detail-list>
                    <x-ui.detail-item label="Status:">
                        <x-ui.badge variant="info">{{ $payroll->status->label() }}</x-ui.badge>
                    </x-ui.detail-item>
                    <x-ui.detail-item label="Numer dokumentu:">
                        #{{ $payroll->id }}
                    </x-ui.detail-item>
                </x-ui.detail-list>
            </div>
        </div>
        <h5 class="fw-semibold mb-3"><i class="bi bi-clock-history"></i> Rozliczenie godzin pracy</h5>
        <div class="table-responsive">
            <table class="table table-bordered payroll-doc-table mb-0">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Projekt</th>
                        <th class="text-end">Godziny</th>
                        <th class="text-end">Stawka</th>
                        <th class="text-end">Kwota</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($hoursBreakdown as $entry)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($entry['date'])->format('d.m.Y') }}</td>
                            <td>{{ $entry['project'] }}</td>
                            <td class="text-end font-mono">{{ number_format($entry['hours'], 2, ',', ' ') }}</td>
                            <td class="text-end font-mono">{{ number_format($entry['rate'], 2, ',', ' ') }}</td>
                            <td class="text-end font-mono fw-semibold">{{ number_format($entry['amount'], 2, ',', ' ') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="2" class="text-end">RAZEM:</th>
                        <th class="text-end">{{ number_format(collect($hoursBreakdown)->sum('hours'), 2, ',', ' ') }}</th>
                        <th></th>
                        <th class="text-end fs-5 text-primary">{{ number_format($payroll->hours_amount, 2, ',', ' ') }} {{ $payroll->currency }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </x-ui.card>
</x-dashboard.snap>
