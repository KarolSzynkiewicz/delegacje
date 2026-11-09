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
    <td class="projects-table__cell projects-table__cell--name">
        <a href="{{ route($showRoute, $project) }}" class="table-link fw-semibold projects-table__truncate" title="{{ $project->name }}">{{ $project->name }}</a>
        @if($typeInfo)
            <div class="small text-muted mt-1 font-mono projects-table__truncate">{{ $typeInfo }}</div>
        @endif
    </td>
    <td class="d-none d-md-table-cell projects-table__cell">
        @if($project->client)
            <a href="{{ route('clients.show', $project->client) }}" class="table-link table-link--muted projects-table__truncate" title="{{ $project->client->name }}">
                {{ $project->client->name }}
            </a>
        @else
            <span class="text-muted">{{ $project->client_name ?? '—' }}</span>
        @endif
    </td>
    <td class="projects-table__cell">
        @if($project->location)
            <div class="projects-table__truncate">
                <i class="bi bi-geo-alt text-muted me-1"></i>{{ $project->location->name }}
            </div>
            @if($project->location->city)
                <div class="small text-muted projects-table__truncate">{{ $project->location->city }}</div>
            @endif
        @else
            <span class="text-muted">—</span>
        @endif
    </td>
    <td class="d-none d-xl-table-cell projects-table__cell">
        @if($project->currentSiteLead?->employee)
            <a href="{{ route('employees.show', $project->currentSiteLead->employee) }}" class="table-link table-link--muted">
                {{ $project->currentSiteLead->employee->full_name }}
            </a>
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
