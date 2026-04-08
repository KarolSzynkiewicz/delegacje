<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Lista Płac - {{ $payroll->employee->full_name }}">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('payrolls.index') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
            <x-slot name="right">
                @if($payroll->canBeRecalculated())
                    <form method="POST" action="{{ route('payrolls.recalculate', $payroll) }}" class="d-inline">
                        @csrf
                        <x-ui.button 
                            variant="ghost" 
                            type="submit"
                            action="refresh"
                        >
                            Przelicz
                        </x-ui.button>
                    </form>
                @endif
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('payrolls.edit', $payroll) }}"
                    routeName="payrolls.edit"
                    action="edit"
                >
                    Edytuj
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>
            <!-- Dokument do wypłaty -->
            <div class="row justify-content-center">
                <div class="col-lg-10 col-xl-9">
                    <x-ui.card class="mb-4 payroll-doc-card">
                        <style>
                            .payroll-doc-card .payroll-doc-table thead th {
                                background: rgba(15, 23, 42, 0.55) !important;
                                color: var(--text-main) !important;
                                border-color: var(--glass-border) !important;
                                font-weight: 600;
                            }
                            .payroll-doc-card .payroll-doc-table tfoot th {
                                background: rgba(15, 23, 42, 0.72) !important;
                                color: var(--text-main) !important;
                                border-color: var(--glass-border) !important;
                            }
                            .payroll-doc-card .payroll-doc-table.table-bordered > :not(caption) > * > * {
                                border-color: var(--glass-border) !important;
                            }
                            .payroll-doc-card .payroll-doc-summary {
                                background: rgba(15, 23, 42, 0.4) !important;
                                border: 1px solid var(--glass-border) !important;
                                color: var(--text-main);
                            }
                        </style>
                        <!-- Nagłówek dokumentu -->
                        <div class="text-center mb-4 pb-3 border-bottom">
                            <h1 class="h3 fw-bold mb-2">LISTA PŁAC</h1>
                            <p class="text-muted mb-0">Okres rozliczeniowy</p>
                        </div>

                        <!-- Informacje podstawowe -->
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
                                        @php
                                            $badgeVariant = match($payroll->status->value) {
                                                'draft' => 'accent',
                                                'issued' => 'warning',
                                                'approved' => 'info',
                                                'paid' => 'success',
                                                default => 'accent'
                                            };
                                        @endphp
                                        <x-ui.badge variant="{{ $badgeVariant }}">{{ $payroll->status->label() }}</x-ui.badge>
                                    </x-ui.detail-item>
                                    <x-ui.detail-item label="Data wystawienia:">
                                        {{ $payroll->created_at->format('d.m.Y') }}
                                    </x-ui.detail-item>
                                    <x-ui.detail-item label="Numer dokumentu:">
                                        #{{ $payroll->id }}
                                    </x-ui.detail-item>
                                </x-ui.detail-list>
                            </div>
                        </div>

                        <!-- Rozliczenie godzin -->
                        <div class="mb-4">
                            <h5 class="fw-semibold mb-3">
                                <i class="bi bi-clock-history"></i> Rozliczenie godzin pracy
                            </h5>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover payroll-doc-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 15%">Data</th>
                                            <th style="width: 30%">Projekt</th>
                                            <th class="text-end" style="width: 15%">Godziny</th>
                                            <th class="text-end" style="width: 20%">Stawka ({{ $payroll->currency }})</th>
                                            <th class="text-end" style="width: 20%">Kwota ({{ $payroll->currency }})</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($hoursBreakdown as $entry)
                                            <tr>
                                                <td>{{ \Carbon\Carbon::parse($entry['date'])->format('d.m.Y') }}</td>
                                                <td>{{ $entry['project'] }}</td>
                                                <td class="text-end">{{ number_format($entry['hours'], 2, ',', ' ') }}</td>
                                                <td class="text-end">
                                                    {{ number_format($entry['rate'], 2, ',', ' ') }}
                                                    @if($entry['rate_currency'] !== $payroll->currency)
                                                        <small class="text-muted">({{ $entry['rate_currency'] }})</small>
                                                    @endif
                                                </td>
                                                <td class="text-end fw-semibold">
                                                    {{ number_format($entry['amount'], 2, ',', ' ') }}
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-4">
                                                    Brak godzin pracy w tym okresie
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="2" class="text-end">RAZEM:</th>
                                            <th class="text-end">
                                                {{ number_format(collect($hoursBreakdown)->sum('hours'), 2, ',', ' ') }}
                                            </th>
                                            <th></th>
                                            <th class="text-end fs-5 text-primary">
                                                {{ number_format($payroll->hours_amount, 2, ',', ' ') }} {{ $payroll->currency }}
                                            </th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        @php
                            $correctionTotalsByCurrency = $payroll->correctionTotalsByCurrency();
                            $payoutTotalsByCurrency = $payroll->payoutTotalsByCurrency();
                        @endphp

                        <!-- Korekty (obciążenia, uznania, zaliczki) -->
                        @if($payroll->adjustments->count() > 0 || $payroll->advances->count() > 0)
                            <div class="mb-4">
                                <h5 class="fw-semibold mb-3">
                                    <i class="bi bi-calculator"></i> Korekty i odliczenia
                                </h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm payroll-doc-table">
                                        <thead>
                                            <tr>
                                                <th style="width: 15%">Data</th>
                                                <th style="width: 25%">Typ</th>
                                                <th style="width: 40%">Opis</th>
                                                <th class="text-end" style="width: 20%">Kwota</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($payroll->adjustments as $adjustment)
                                                <tr>
                                                    <td>{{ $adjustment->date->format('d.m.Y') }}</td>
                                                    <td>
                                                        @if($adjustment->type === 'penalty')
                                                            <x-ui.badge variant="danger">Obciążenie</x-ui.badge>
                                                        @else
                                                            <x-ui.badge variant="success">Uznanie</x-ui.badge>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        {{ $adjustment->notes ?: ($adjustment->type === 'penalty' ? 'Obciążenie' : 'Uznanie') }}
                                                    </td>
                                                    <td class="text-end {{ $adjustment->type === 'penalty' ? 'text-danger' : 'text-success' }}">
                                                        <span class="fw-semibold">
                                                            {{ $adjustment->type === 'penalty' ? '-' : '+' }}{{ number_format(abs((float) $adjustment->getEffectiveAmount()), 2, ',', ' ') }}
                                                        </span>
                                                        <div class="small text-muted">{{ strtoupper((string) ($adjustment->currency ?: 'PLN')) }}</div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                            @foreach($payroll->advances as $advance)
                                                <tr>
                                                    <td>{{ $advance->date->format('d.m.Y') }}</td>
                                                    <td>
                                                        <x-ui.badge variant="warning">Zaliczka</x-ui.badge>
                                                    </td>
                                                    <td>
                                                        Zaliczka
                                                        @if($advance->is_interest_bearing && $advance->interest_rate)
                                                            <small class="text-muted">
                                                                (oprocentowanie {{ number_format($advance->interest_rate, 2, ',', ' ') }}%)
                                                            </small>
                                                        @endif
                                                        @if($advance->notes)
                                                            <br><small class="text-muted">{{ $advance->notes }}</small>
                                                        @endif
                                                    </td>
                                                    <td class="text-end text-danger">
                                                        @php
                                                            $advCur = strtoupper((string) ($advance->currency ?: 'PLN'));
                                                            $advTotal = (float) $advance->amount + $advance->getInterestAmount();
                                                        @endphp
                                                        <span class="fw-semibold">-{{ number_format($advTotal, 2, ',', ' ') }}</span>
                                                        <div class="small text-muted">{{ $advCur }}</div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot>
                                            @forelse($correctionTotalsByCurrency as $sumCur => $sumAmt)
                                                <tr>
                                                    <th colspan="3" class="text-end">SUMA KOREKT ({{ $sumCur }}):</th>
                                                    <th class="text-end {{ $sumAmt < 0 ? 'text-danger' : ($sumAmt > 0 ? 'text-success' : '') }}">
                                                        {{ $sumAmt >= 0 ? '+' : '' }}{{ number_format($sumAmt, 2, ',', ' ') }} {{ $sumCur }}
                                                    </th>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <th colspan="3" class="text-end">SUMA KOREKT:</th>
                                                    <th class="text-end {{ $payroll->adjustments_amount < 0 ? 'text-danger' : 'text-success' }}">
                                                        {{ $payroll->adjustments_amount >= 0 ? '+' : '' }}{{ number_format($payroll->adjustments_amount, 2, ',', ' ') }} {{ $payroll->currency }}
                                                    </th>
                                                </tr>
                                            @endforelse
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        @endif

                        <!-- Podsumowanie -->
                        <div class="border-top pt-4">
                            <div class="row">
                                <div class="col-md-8">
                                    @if($payroll->notes)
                                        <div class="mb-3">
                                            <strong>Uwagi:</strong>
                                            <p class="text-muted mb-0">{{ $payroll->notes }}</p>
                                        </div>
                                    @endif
                                </div>
                                <div class="col-md-4">
                                    <div class="payroll-doc-summary p-3 rounded">
                                        <table class="table table-sm mb-0">
                                            <tr>
                                                <td class="border-0"><strong>Kwota z godzin:</strong></td>
                                                <td class="text-end border-0">
                                                    <strong>{{ number_format($payroll->hours_amount, 2, ',', ' ') }} {{ $payroll->currency }}</strong>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="border-0 align-top"><strong>Korekty:</strong></td>
                                                <td class="text-end border-0">
                                                    @forelse($correctionTotalsByCurrency as $sumCur => $sumAmt)
                                                        <div class="{{ $sumAmt < 0 ? 'text-danger' : ($sumAmt > 0 ? 'text-success' : '') }}">
                                                            <strong>{{ $sumAmt >= 0 ? '+' : '' }}{{ number_format($sumAmt, 2, ',', ' ') }} {{ $sumCur }}</strong>
                                                        </div>
                                                    @empty
                                                        <strong class="{{ $payroll->adjustments_amount < 0 ? 'text-danger' : 'text-success' }}">
                                                            {{ $payroll->adjustments_amount >= 0 ? '+' : '' }}{{ number_format($payroll->adjustments_amount, 2, ',', ' ') }} {{ $payroll->currency }}
                                                        </strong>
                                                    @endforelse
                                                </td>
                                            </tr>
                                            <tr class="border-top">
                                                <td class="pt-2 align-top"><strong class="fs-5">DO WYPŁATY:</strong></td>
                                                <td class="text-end pt-2">
                                                    @foreach($payoutTotalsByCurrency as $pCur => $pAmt)
                                                        <div class="mb-2 @if($loop->last) mb-0 @endif">
                                                            <strong @class([
                                                                'fs-4',
                                                                'text-muted' => abs((float) $pAmt) < 0.00001,
                                                                'text-primary' => (float) $pAmt > 0,
                                                                'text-danger' => (float) $pAmt < 0,
                                                            ])>
                                                                @if(abs((float) $pAmt) >= 0.00001)
                                                                    {{ (float) $pAmt >= 0 ? '+' : '' }}
                                                                @endif
                                                                {{ number_format((float) $pAmt, 2, ',', ' ') }} {{ $pCur }}
                                                            </strong>
                                                        </div>
                                                    @endforeach
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Stopka -->
                        <div class="mt-4 pt-3 border-top text-center text-muted small">
                            <p class="mb-0">
                                Dokument wygenerowany dnia {{ now()->format('d.m.Y H:i') }}<br>
                                Ostatnia aktualizacja: {{ $payroll->updated_at->format('d.m.Y H:i') }}
                            </p>
                        </div>
                    </x-ui.card>
                </div>
            </div>
</x-app-layout>
