@php
    $state = $locationStatus['state'] ?? null;
    $away = ! in_array($state, [
        \App\Enums\EmployeeLocationState::IN_BASE,
        \App\Enums\EmployeeLocationState::IN_TRANSIT,
    ], true);
    $warn = (bool) ($locationStatus['has_assignment_overlap'] ?? false);
    $statusLabel = match ($state) {
        \App\Enums\EmployeeLocationState::IN_TRANSIT => 'W podróży',
        \App\Enums\EmployeeLocationState::IN_BASE => 'Baza',
        default => 'Poza bazą',
    };
    $statusVariant = match ($state) {
        \App\Enums\EmployeeLocationState::IN_TRANSIT => 'warning',
        \App\Enums\EmployeeLocationState::IN_BASE => 'success',
        default => 'info',
    };
    $whereRows = [
        ['label' => 'Dom', 'items' => $locationStatus['accommodation_names'] ?? []],
        ['label' => 'Auto', 'items' => $locationStatus['vehicle_labels'] ?? []],
        ['label' => 'Projekt', 'items' => $locationStatus['project_names'] ?? []],
    ];
@endphp

<div class="emp-kv">
    <div class="emp-kv__row">
        <x-ui.badge :variant="$statusVariant">{{ $statusLabel }}</x-ui.badge>
        @unless($away)
            <span class="emp-kv__mark" aria-hidden="true">×</span>
        @endunless
    </div>
    @if($away)
        @foreach($whereRows as $whereRow)
            <div class="emp-kv__row">
                <span class="emp-kv__label">{{ $whereRow['label'] }}</span>
                <span class="emp-kv__value">
                    @if(! empty($whereRow['items']))
                        @foreach($whereRow['items'] as $whereItem)
                            <x-ui.badge :variant="$warn ? 'warning' : 'info'" :title="$whereItem">
                                {{ \Illuminate\Support\Str::limit($whereItem, 12, '…') }}
                            </x-ui.badge>
                        @endforeach
                    @else
                        <span class="emp-kv__mark" aria-hidden="true">×</span>
                    @endif
                </span>
            </div>
        @endforeach
    @endif
</div>
