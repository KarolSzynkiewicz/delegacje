@php
    // Helper function to format numbers without unnecessary .00
    function formatNumber($number, $decimals = 2) {
        $formatted = number_format($number, $decimals, '.', '');
        // Remove trailing zeros and decimal point if not needed
        return rtrim(rtrim($formatted, '0'), '.');
    }
    
    // Helper function to format currency symbol
    function formatCurrency($currency) {
        return match(strtoupper($currency)) {
            'EUR' => '€',
            'USD' => '$',
            'GBP' => '£',
            'PLN' => 'zł',
            default => $currency,
        };
    }
    
    // Helper function to get default currency (EUR if zero, otherwise use provided)
    function getDefaultCurrency($value, $currency = null) {
        // Always return EUR if value is zero, regardless of provided currency
        if ($value == 0 || $value == 0.0 || $value == '0' || empty($value)) {
            return 'EUR';
        }
        return $currency ?? 'EUR';
    }
@endphp

<x-app-layout>
    <div class="py-4">
        <div class="container-xxl">
            <!-- Nawigacja między miesiącami -->
            <div class="mb-4 d-flex justify-content-between align-items-center gap-3 flex-wrap">
                <!-- Przycisk poprzedni miesiąc -->
                <x-ui.button variant="ghost" href="{{ $navigation['prevUrl'] }}">
                    <i class="bi bi-chevron-left"></i>
                    <span>Poprzedni miesiąc</span>
                </x-ui.button>

                <!-- Aktualny miesiąc -->
                <div class="text-center">
                    <h3 class="fs-5 fw-bold mb-0">
                        Dashboard zysków i strat
                    </h3>
                    <p class="small text-muted mb-0">
                        {{ $navigation['current']['label'] }}
                    </p>
                </div>

                <!-- Przycisk następny miesiąc -->
                <x-ui.button variant="primary" href="{{ $navigation['nextUrl'] }}">
                    <span>Następny miesiąc</span>
                    <i class="bi bi-chevron-right"></i>
                </x-ui.button>
            </div>

    <!-- Summary Card -->
    <div class="row mb-4">
        <div class="col-12">
            <x-ui.card label="Podsumowanie">
                <div class="row g-3">
                    <div class="col-md-2">
                        <div class="text-center p-3 bg-light rounded">
                            <div class="text-muted small mb-1">Przychody</div>
                            @if(isset($summary['revenue_by_currency']) && count($summary['revenue_by_currency']) > 0)
                                @foreach($summary['revenue_by_currency'] as $currency => $amount)
                                    <div class="h4 mb-0 text-success">{{ formatNumber($amount) }} {{ formatCurrency($currency) }}</div>
                                @endforeach
                            @else
                                <div class="h4 mb-0 text-success">0 {{ formatCurrency('EUR') }}</div>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-center p-3 bg-light rounded">
                            <div class="text-muted small mb-1">Koszty pracy</div>
                            @if(isset($summary['labor_costs_by_currency']) && count($summary['labor_costs_by_currency']) > 0)
                                @foreach($summary['labor_costs_by_currency'] as $currency => $amount)
                                    <div class="h4 mb-0 text-danger">{{ formatNumber($amount) }} {{ formatCurrency($currency) }}</div>
                                @endforeach
                            @else
                                <div class="h4 mb-0 text-danger">0 {{ formatCurrency('EUR') }}</div>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-center p-3 bg-light rounded">
                            <div class="text-muted small mb-1">Koszty zmienne</div>
                            @if(isset($summary['variable_costs_by_currency']) && count($summary['variable_costs_by_currency']) > 0)
                                @foreach($summary['variable_costs_by_currency'] as $currency => $amount)
                                    <div class="h4 mb-0 text-warning">{{ formatNumber($amount) }} {{ formatCurrency($currency) }}</div>
                                @endforeach
                            @else
                                <div class="h4 mb-0 text-warning">0 {{ formatCurrency('EUR') }}</div>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-center p-3 bg-light rounded">
                            <div class="text-muted small mb-1">Koszty stałe</div>
                            @if(isset($summary['fixed_costs_by_currency']) && count($summary['fixed_costs_by_currency']) > 0)
                                @foreach($summary['fixed_costs_by_currency'] as $currency => $amount)
                                    <div class="h4 mb-0 text-info">{{ formatNumber($amount) }} {{ formatCurrency($currency) }}</div>
                                @endforeach
                            @else
                                <div class="h4 mb-0 text-info">0 {{ formatCurrency('EUR') }}</div>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-center p-3 bg-light rounded">
                            <div class="text-muted small mb-1">Łączne koszty</div>
                            @php
                                $totalCostsByCurrency = [];
                                foreach ($summary['labor_costs_by_currency'] ?? [] as $currency => $amount) {
                                    $totalCostsByCurrency[$currency] = ($totalCostsByCurrency[$currency] ?? 0) + $amount;
                                }
                                foreach ($summary['variable_costs_by_currency'] ?? [] as $currency => $amount) {
                                    $totalCostsByCurrency[$currency] = ($totalCostsByCurrency[$currency] ?? 0) + $amount;
                                }
                                foreach ($summary['fixed_costs_by_currency'] ?? [] as $currency => $amount) {
                                    $totalCostsByCurrency[$currency] = ($totalCostsByCurrency[$currency] ?? 0) + $amount;
                                }
                            @endphp
                            @if(count($totalCostsByCurrency) > 0)
                                @foreach($totalCostsByCurrency as $currency => $amount)
                                    <div class="h4 mb-0 text-danger">{{ formatNumber($amount) }} {{ formatCurrency($currency) }}</div>
                                @endforeach
                            @else
                                <div class="h4 mb-0 text-danger">0 {{ formatCurrency('EUR') }}</div>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-center p-3 bg-light rounded">
                            <div class="text-muted small mb-1">Marża</div>
                            @php
                                $marginByCurrency = [];
                                foreach ($summary['revenue_by_currency'] ?? [] as $currency => $revenue) {
                                    $costs = $totalCostsByCurrency[$currency] ?? 0;
                                    $margin = $revenue - $costs;
                                    $marginByCurrency[$currency] = [
                                        'amount' => $margin,
                                        'percentage' => $revenue > 0 ? ($margin / $revenue) * 100 : 0
                                    ];
                                }
                            @endphp
                            @if(count($marginByCurrency) > 0)
                                @foreach($marginByCurrency as $currency => $marginData)
                                    <div class="h4 mb-0 {{ $marginData['amount'] >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ formatNumber($marginData['amount']) }} {{ formatCurrency($currency) }}
                                    </div>
                                    <div class="small text-muted">({{ formatNumber($marginData['percentage']) }}%)</div>
                                @endforeach
                            @else
                                <div class="h4 mb-0 text-success">0 {{ formatCurrency('EUR') }}</div>
                            @endif
                        </div>
                    </div>
                </div>
            </x-ui.card>
        </div>
    </div>

    <!-- Projects Cards -->
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="h5 mb-3">Aktywne Projekty</h2>
        </div>
        @forelse($projectsProfitability as $projectData)
            @php
                $project = $projectData['project'];
            @endphp
            <div class="col-md-6 col-lg-4 mb-4">
                <x-ui.card>
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h3 class="h6 mb-1">{{ $project->name }}</h3>
                            <p class="text-muted small mb-0">{{ $project->client_name ?? 'Brak klienta' }}</p>
                        </div>
                        @if($project->type)
                            <x-ui.badge variant="info">{{ $project->type->label() }}</x-ui.badge>
                        @endif
                    </div>

                    <!-- Przychody -->
                    <div class="mb-3 pb-3 border-bottom">
                        <h6 class="text-muted small mb-2 fw-bold">PRZYCHODY</h6>
                        <div class="p-2 bg-light rounded">
                            @php
                                $revenueCurrency = getDefaultCurrency($projectData['revenue'], $projectData['revenue_currency'] ?? null);
                            @endphp
                            <div class="fw-bold text-success fs-5">
                                {{ formatNumber($projectData['revenue']) }} {{ formatCurrency($revenueCurrency) }}
                            </div>
                        </div>
                    </div>

                    <!-- Koszty -->
                    <div class="mb-3 pb-3 border-bottom">
                        <h6 class="text-muted small mb-2 fw-bold">KOSZTY</h6>
                        
                        <!-- Koszty pracy -->
                        <div class="mb-2">
                            <div class="text-muted small mb-1">Praca (łącznie)</div>
                            @if(isset($projectData['labor_costs_by_currency']) && count($projectData['labor_costs_by_currency']) > 0)
                                @foreach($projectData['labor_costs_by_currency'] as $currency => $cost)
                                    <div class="fw-bold text-danger">
                                        {{ formatNumber($cost) }} {{ formatCurrency($currency) }}
                                    </div>
                                @endforeach
                            @else
                                <div class="fw-bold text-danger">0 {{ formatCurrency('EUR') }}</div>
                            @endif
                            
                            @if(isset($projectData['paid_labor_costs_by_currency']) && count($projectData['paid_labor_costs_by_currency']) > 0)
                                <div class="small text-muted mt-1">
                                    <div class="text-success">✓ Wypłacone:</div>
                                    @foreach($projectData['paid_labor_costs_by_currency'] as $currency => $cost)
                                        <div class="text-success ms-2">{{ formatNumber($cost) }} {{ formatCurrency($currency) }}</div>
                                    @endforeach
                                </div>
                                @php
                                    $unpaidByCurrency = [];
                                    foreach ($projectData['labor_costs_by_currency'] ?? [] as $currency => $total) {
                                        $paid = $projectData['paid_labor_costs_by_currency'][$currency] ?? 0;
                                        $unpaid = $total - $paid;
                                        if ($unpaid > 0) {
                                            $unpaidByCurrency[$currency] = $unpaid;
                                        }
                                    }
                                @endphp
                                @if(count($unpaidByCurrency) > 0)
                                    <div class="small text-muted mt-1">
                                        <div class="text-warning">○ Niewypłacone:</div>
                                        @foreach($unpaidByCurrency as $currency => $cost)
                                            <div class="text-warning ms-2">{{ formatNumber($cost) }} {{ formatCurrency($currency) }}</div>
                                        @endforeach
                                    </div>
                                @endif
                            @endif
                        </div>
                        
                        <!-- Koszty zmienne -->
                        <div>
                            <div class="text-muted small mb-1">Koszty zmienne</div>
                            @if(isset($projectData['variable_costs_by_currency']) && count($projectData['variable_costs_by_currency']) > 0)
                                @foreach($projectData['variable_costs_by_currency'] as $currency => $cost)
                                    <div class="fw-bold text-warning">
                                        {{ formatNumber($cost) }} {{ formatCurrency($currency) }}
                                    </div>
                                @endforeach
                            @else
                                <div class="fw-bold text-warning">0 {{ formatCurrency('EUR') }}</div>
                            @endif
                        </div>
                    </div>

                    <x-ui.detail-list>
                        <x-ui.detail-item label="Liczba pracowników:">
                            {{ $projectData['employee_count'] }}
                        </x-ui.detail-item>
                        <x-ui.detail-item label="Godziny szacowane:">
                            {{ formatNumber($projectData['estimated_hours']) }}h
                        </x-ui.detail-item>
                        <x-ui.detail-item label="Godziny rzeczywiste:">
                            {{ formatNumber($projectData['actual_hours']) }}h
                        </x-ui.detail-item>
                        <x-ui.detail-item label="Wykonanie planu:">
                            <x-ui.badge variant="{{ $projectData['plan_execution'] >= 100 ? 'success' : ($projectData['plan_execution'] >= 80 ? 'warning' : 'danger') }}">
                                {{ formatNumber($projectData['plan_execution']) }}%
                            </x-ui.badge>
                        </x-ui.detail-item>
                    </x-ui.detail-list>

                    <div class="mt-3 pt-3 border-top">
                        <x-ui.button variant="ghost" href="{{ route('projects.show', $project) }}" class="btn-sm">
                            <i class="bi bi-eye me-1"></i> Szczegóły
                        </x-ui.button>
                    </div>
                </x-ui.card>
            </div>
        @empty
            <div class="col-12">
                <x-ui.card>
                    <x-ui.empty-state 
                        icon="folder-x"
                        message="Brak aktywnych projektów"
                    />
                </x-ui.card>
            </div>
        @endforelse
    </div>

    <!-- Statistics Row -->
    <div class="row">
        <!-- Top Employees by Revenue -->
        <div class="col-lg-6 mb-4">
            <x-ui.card label="Najlepsi pracownicy (przychody)">
                @if(count($topEmployees) > 0)
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Pracownik</th>
                                    <th class="text-end">Godziny</th>
                                    <th class="text-end">Przychody</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topEmployees as $employeeData)
                                    <tr>
                                        <td>
                                            <a href="{{ route('employees.show', $employeeData['employee']) }}" class="text-decoration-none">
                                                {{ $employeeData['employee']->full_name }}
                                            </a>
                                        </td>
                                        <td class="text-end">{{ formatNumber($employeeData['total_hours']) }}h</td>
                                        <td class="text-end">
                                            @if(isset($employeeData['total_revenue_by_currency']) && count($employeeData['total_revenue_by_currency']) > 0)
                                                @foreach($employeeData['total_revenue_by_currency'] as $currency => $revenue)
                                                    <strong>{{ formatNumber($revenue) }} {{ formatCurrency($currency) }}</strong>
                                                    @if(!$loop->last)<br>@endif
                                                @endforeach
                                            @else
                                                <strong>0 {{ formatCurrency('EUR') }}</strong>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <x-ui.empty-state 
                        icon="people"
                        message="Brak danych o pracownikach"
                    />
                @endif
            </x-ui.card>
        </div>

        <!-- Longest Rotations -->
        <div class="col-lg-6 mb-4">
            <x-ui.card label="Najdłuższe rotacje">
                @if(count($longestRotations) > 0)
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Pracownik</th>
                                    <th class="text-end">Dni</th>
                                    <th class="text-end">Rotacji</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($longestRotations as $rotationData)
                                    <tr>
                                        <td>
                                            <a href="{{ route('employees.show', $rotationData['employee']) }}" class="text-decoration-none">
                                                {{ $rotationData['employee']->full_name }}
                                            </a>
                                        </td>
                                        <td class="text-end">
                                            <strong>{{ formatNumber($rotationData['total_days'], 0) }}</strong>
                                        </td>
                                        <td class="text-end">{{ $rotationData['rotation_count'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <x-ui.empty-state 
                        icon="arrow-repeat"
                        message="Brak danych o rotacjach"
                    />
                @endif
            </x-ui.card>
        </div>
    </div>
        </div>
    </div>
</x-app-layout>
