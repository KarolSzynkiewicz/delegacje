@php
    $weekStart = $weeks[0]['start'];
    $weekSiteLeads = $weekData['site_leads'] ?? collect();
    $leadRows = $assignedList->filter(fn ($row) => $row['is_site_lead'] ?? false)->values();
    $absentLeads = $weekSiteLeads
        ->filter(function ($lead) use ($assignedList) {
            $leadId = (int) $lead->employee_id;

            return ! $assignedList->contains(fn ($row) => (int) $row['employee']->id === $leadId);
        })
        ->values();
    $pickEmployees = $assignedList
        ->map(fn ($row) => $row['employee'] ?? null)
        ->filter()
        ->unique('id')
        ->values();
    $canManageSiteLead = auth()->user()?->hasPermission('projects.update') ?? false;
@endphp

@if($leadRows->isEmpty())
    <div class="wo-lead-banner mb-3">
        @if($weekSiteLeads->isNotEmpty())
            <p class="wo-lead-note wo-lead-note--warn mb-3">
                <i class="bi bi-exclamation-triangle-fill"></i>
                @if($absentLeads->count() === 1 && $absentLeads->first()->employee)
                    {{ $absentLeads->first()->employee->full_name }} jest kierownikiem, ale nie ma go w projekcie w tym tygodniu.
                @else
                    Kierownik nie jest w projekcie w tym tygodniu.
                @endif
                Wyznacz kogoś innego z ekipy.
            </p>
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                @foreach($absentLeads as $absentLead)
                    @if($absentLead->employee)
                        <span class="d-inline-flex align-items-center gap-2">
                            <span class="wo-lead-tag">Kierownik</span>
                            <x-employee-cell :employee="$absentLead->employee" :show-phone="false" avatar-size="32px" />
                        </span>
                    @endif
                @endforeach
            </div>
            @include('weekly-overview.partials.site-lead-quick-picks', [
                'emptyMessage' => 'Przypisz kogoś do projektu w tym tygodniu, żeby podmienić kierownika.',
            ])
        @else
            <p class="wo-lead-note mb-3">
                Brak kierownika. Wyznacz kogoś z ekipy w tym tygodniu.
            </p>
            @include('weekly-overview.partials.site-lead-quick-picks', [
                'emptyMessage' => 'Najpierw przypisz osoby do projektu w tym tygodniu.',
            ])
        @endif
    </div>
@endif
