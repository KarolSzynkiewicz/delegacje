@props([
    'project',
    'siteLeads',
    'canManage' => false,
])

@php
    $current = $siteLeads->first(fn ($lead) => $lead->isCurrentlyActive());
    $thisWeekCrew = collect();

    if (! $current && $canManage) {
        $thisWeekCrew = $project->employeesAssignedInDateRange(
            now()->startOfWeek(),
            now()->endOfWeek()
        );
    }
@endphp

<x-ui.card label="Kierownik" class="mt-4">
    <x-ui.table-header
        subtitle="Pracownik prowadzący ekipę w danym okresie."
        class="mb-3"
    >
        <x-slot:actions>
            @if($canManage)
                <x-ui.button
                    variant="primary"
                    href="{{ route('projects.site-leads.create', $project) }}"
                    class="btn-sm"
                >
                    <i class="bi bi-plus-circle"></i> Ustaw kierownika
                </x-ui.button>
            @endif
        </x-slot:actions>
    </x-ui.table-header>

    @if($current)
        <div class="d-flex align-items-center gap-2 flex-wrap mb-3">
            <x-project-site-lead-badge />
            <x-employee-cell :employee="$current->employee" />
            <span class="small text-muted font-mono">
                od {{ $current->start_date->format('Y-m-d') }}
                @if($current->end_date)
                    do {{ $current->end_date->format('Y-m-d') }}
                @endif
            </span>
        </div>
    @else
        <x-ui.empty-state
            icon="person-badge"
            message="{{ $thisWeekCrew->isNotEmpty() ? 'Brak kierownika. Przypisz kogoś z ekipy w tym tygodniu.' : 'Brak kierownika.' }}"
            class="py-2"
        >
            @if($canManage && $thisWeekCrew->isNotEmpty())
                <ul class="psl-quick list-unstyled mb-0 mt-3">
                    @foreach($thisWeekCrew as $employee)
                        <li class="psl-quick__row">
                            <x-employee-cell :employee="$employee" :show-phone="false" avatar-size="32px" />
                            <form
                                method="POST"
                                action="{{ route('projects.site-leads.store', $project) }}"
                                class="mb-0"
                            >
                                @csrf
                                <input type="hidden" name="employee_id" value="{{ $employee->id }}">
                                <input type="hidden" name="start_date" value="{{ now()->toDateString() }}">
                                <x-ui.button variant="primary" type="submit" class="btn-sm">
                                    Przypisz
                                </x-ui.button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @elseif($canManage)
                <x-ui.button
                    variant="primary"
                    href="{{ route('projects.site-leads.create', $project) }}"
                    class="btn-sm mt-3"
                    action="create"
                >
                    Ustaw kierownika
                </x-ui.button>
            @endif
        </x-ui.empty-state>
    @endif

    @if($siteLeads->isNotEmpty())
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Pracownik</th>
                        <th>Okres</th>
                        <th>Status</th>
                        @if($canManage)
                            <th class="text-end">Akcje</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($siteLeads as $lead)
                        @php
                            if ($lead->isCurrentlyActive()) {
                                $statusLabel = 'Aktualny';
                                $statusVariant = 'success';
                            } elseif ($lead->isScheduled()) {
                                $statusLabel = 'Zaplanowany';
                                $statusVariant = 'info';
                            } else {
                                $statusLabel = 'Zakończony';
                                $statusVariant = 'accent';
                            }
                        @endphp
                        <tr>
                            <td>
                                @if($lead->employee)
                                    <x-employee-cell :employee="$lead->employee" :show-phone="false" avatar-size="32px" />
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="font-mono small">
                                {{ $lead->start_date->format('Y-m-d') }}
                                –
                                {{ $lead->end_date ? $lead->end_date->format('Y-m-d') : 'bez terminu' }}
                            </td>
                            <td>
                                <x-ui.badge variant="{{ $statusVariant }}">{{ $statusLabel }}</x-ui.badge>
                            </td>
                            @if($canManage)
                                <td class="text-end text-nowrap">
                                    <x-ui.button
                                        variant="ghost"
                                        href="{{ route('projects.site-leads.edit', [$project, $lead]) }}"
                                        class="btn-sm"
                                    >
                                        <i class="bi bi-pencil"></i>
                                    </x-ui.button>
                                    <form
                                        action="{{ route('projects.site-leads.destroy', [$project, $lead]) }}"
                                        method="POST"
                                        class="d-inline"
                                        onsubmit="return confirm('Usunąć ten wpis kierownika?')"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <x-ui.button variant="ghost" type="submit" class="btn-sm" title="Usuń">
                                            <i class="bi bi-trash"></i>
                                        </x-ui.button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-ui.card>
