@props(['project', 'isMineView' => false])

@php
    $status = $project->status ?? \App\Enums\ProjectStatus::ACTIVE;
    $statusLabel = $status instanceof \App\Enums\ProjectStatus ? $status->label() : ucfirst($status);
    $badgeVariant = match(\App\Services\StatusColorService::getProjectStatusColor($status)) {
        'success' => 'success',
        'danger'  => 'danger',
        'warning' => 'warning',
        default   => 'info',
    };

    if ($project->isScheduled()) {
        $stateLabel   = 'Zaplanowany';
        $stateVariant = 'warning';
        $stateIcon    = 'clock';
    } elseif ($project->isCurrentlyActive()) {
        $stateLabel   = 'Aktywny';
        $stateVariant = 'success';
        $stateIcon    = 'play-circle';
    } elseif ($project->isPast()) {
        $stateLabel   = 'Zakończony';
        $stateVariant = 'info';
        $stateIcon    = 'check-circle';
    } else {
        $stateLabel   = 'Brak dat';
        $stateVariant = 'accent';
        $stateIcon    = 'dash-circle';
    }

    $type = $project->type ?? \App\Enums\ProjectType::CONTRACT;
    $typeValue = $type instanceof \App\Enums\ProjectType ? $type->value : $type;
    $typeInfo = $typeValue === 'hourly'
        ? ($project->hourly_rate ? number_format($project->hourly_rate, 2, ',', ' ') . ' ' . ($project->currency ?? 'EUR') . '/h' : '')
        : ($project->contract_amount ? number_format($project->contract_amount, 2, ',', ' ') . ' ' . ($project->currency ?? 'PLN') : '');

    $showRoute = $isMineView ? 'mine.projects.show' : 'projects.show';
@endphp

<tr wire:key="project-{{ $project->id }}">
    <td>
        <div class="fw-medium">{{ $project->name }}</div>
        @if($typeInfo)
            <div class="small text-muted mt-1">{{ $typeInfo }}</div>
        @endif
    </td>
    <td class="d-none d-md-table-cell">{{ $project->client_name ?? '-' }}</td>
    <td>
        @if($project->location)
            <div><i class="bi bi-geo-alt text-muted me-1"></i>{{ $project->location->name }}</div>
            @if($project->location->city)
                <div class="small text-muted">{{ $project->location->city }}</div>
            @endif
        @else
            <span class="text-muted">—</span>
        @endif
    </td>
    <td class="text-nowrap">
        {{ $project->start_date?->format('d.m.Y') ?? '—' }}
    </td>
    <td class="text-nowrap">
        {{ $project->end_date?->format('d.m.Y') ?? '—' }}
    </td>
    <td class="text-nowrap">
        <x-ui.badge variant="{{ $stateVariant }}">
            <i class="bi bi-{{ $stateIcon }} me-1"></i>{{ $stateLabel }}
        </x-ui.badge>
    </td>
    <td>
        <x-ui.badge variant="{{ $badgeVariant }}">{{ $statusLabel }}</x-ui.badge>
    </td>
    <td class="text-end">
        <x-ui.button variant="ghost" href="{{ route($showRoute, $project) }}" class="btn-sm">
            <i class="bi bi-eye"></i>
            <span class="d-none d-sm-inline ms-1">Zobacz</span>
        </x-ui.button>
    </td>
</tr>
