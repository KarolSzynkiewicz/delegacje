<div>
    @include('livewire.partials.project-show-styles')

    <x-ui.tabs 
        :tabs="$tabsForComponent" 
        :activeTab="$activeTab" 
        id="projectTabs"
        :compact-mobile="true"
        mobile-label="Sekcja projektu"
    />

<div id="projectTabsContent" class="mt-3">
    @if($activeTab === 'info')
        @php
            $projectTypeValue = $project->type instanceof \App\Enums\ProjectType
                ? $project->type->value
                : ($project->type ?? null);
            $openRate = $project->hourlyRates->first(fn ($rate) => $rate->end_date === null);
        @endphp

        <div id="info" role="tabpanel" class="pj-shell">
            <div class="pj-grid pj-grid--main">
                <div class="pj-panel cl-reveal">
                    <div class="pj-panel__head">
                        <h4 class="pj-panel__title">Szczegóły</h4>
                    </div>

                    <div class="pj-kv">
                        <div class="pj-kv__item">
                            <div class="pj-kv__label">Klient</div>
                            <div class="pj-kv__value">
                                @if($project->client)
                                    <a href="{{ route('clients.show', $project->client) }}">{{ $project->client->name }}</a>
                                @else
                                    {{ $project->client_name ?? '—' }}
                                @endif
                            </div>
                        </div>

                        <div class="pj-kv__item">
                            <div class="pj-kv__label">Osoba kontaktowa</div>
                            <div class="pj-kv__value">
                                @if($project->clientContact)
                                    {{ $project->clientContact->label() }}
                                    @if($project->clientContact->phone)
                                        <div class="pj-kv__sub font-mono">{{ $project->clientContact->phone }}</div>
                                    @elseif($project->clientContact->email)
                                        <div class="pj-kv__sub">{{ $project->clientContact->email }}</div>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </div>
                        </div>

                        <div class="pj-kv__item">
                            <div class="pj-kv__label">Lokalizacja</div>
                            <div class="pj-kv__value">{{ $project->location?->name ?? '—' }}</div>
                        </div>

                        @if(! $isMineView)
                            @if($projectTypeValue === \App\Enums\ProjectType::HOURLY->value)
                                <div class="pj-kv__item">
                                    <div class="pj-kv__label">Stawka</div>
                                    <div class="pj-kv__value font-mono">
                                        @if($openRate)
                                            {{ number_format($openRate->amount, 2, ',', ' ') }} {{ $openRate->currency }}/h
                                            <div class="pj-kv__sub">od {{ $openRate->start_date->format('d.m.Y') }}</div>
                                        @elseif($project->hourly_rate)
                                            {{ number_format($project->hourly_rate, 2, ',', ' ') }} {{ $project->currency ?? 'PLN' }}/h
                                        @else
                                            —
                                        @endif
                                    </div>
                                </div>
                            @elseif($projectTypeValue === \App\Enums\ProjectType::CONTRACT->value)
                                <div class="pj-kv__item">
                                    <div class="pj-kv__label">Kontrakt</div>
                                    <div class="pj-kv__value font-mono">
                                        {{ $project->contract_amount ? number_format($project->contract_amount, 2, ',', ' ').' '.($project->currency ?? 'PLN') : '—' }}
                                    </div>
                                </div>
                            @endif
                            <div class="pj-kv__item">
                                <div class="pj-kv__label">Budżet</div>
                                <div class="pj-kv__value font-mono">
                                    {{ $project->budget ? number_format($project->budget, 2, ',', ' ').' '.($project->currency ?? 'PLN') : '—' }}
                                </div>
                            </div>
                        @endif

                        @if($project->description)
                            <div class="pj-kv__item pj-kv__item--wide">
                                <div class="pj-kv__label">Opis</div>
                                <div class="pj-kv__value pj-prose">{{ $project->description }}</div>
                            </div>
                        @endif
                    </div>

                    <div class="pj-ustalenia">
                        <div class="pj-ustalenia__label">Ustalenia</div>
                        @if($project->arrangements)
                            <div class="pj-prose">{{ $project->arrangements }}</div>
                        @else
                            <div class="pj-prose pj-prose--muted">
                                Brak — wymagania techniczne / co zapewniamy pracownikom (edycja projektu).
                            </div>
                        @endif
                    </div>
                </div>

                <div class="pj-side-stack">
                    @include('livewire.partials.project-milestones-panel', [
                        'milestones' => $project->milestones,
                        'canManage' => $canManage,
                    ])

                    <div class="pj-panel cl-reveal">
                        <div class="pj-panel__head">
                            <h4 class="pj-panel__title">Kierownik</h4>
                            @if($canManage)
                                <a href="{{ route('projects.site-leads.create', $project) }}" class="btn btn-sm btn-outline-secondary">
                                    Ustaw
                                </a>
                            @endif
                        </div>
                        @php
                            $currentLead = $project->siteLeads->first(fn ($lead) => $lead->isCurrentlyActive());
                        @endphp
                        @if($currentLead)
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <x-project-site-lead-badge />
                                <x-employee-cell :employee="$currentLead->employee" />
                                <span class="small text-muted font-mono">
                                    od {{ $currentLead->start_date->format('d.m.Y') }}
                                    @if($currentLead->end_date)
                                        · do {{ $currentLead->end_date->format('d.m.Y') }}
                                    @endif
                                </span>
                            </div>
                        @else
                            <div class="pm-empty py-2 mb-0">
                                <i class="bi bi-person-badge"></i>
                                Brak kierownika na dziś.
                            </div>
                        @endif
                        @if($project->siteLeads->count() > 1)
                            <p class="small text-muted mb-0 mt-2">
                                Pełna historia poniżej.
                            </p>
                        @endif
                    </div>
                </div>
            </div>

            @if(! $isMineView)
                <div class="pj-grid pj-grid--history">
                    <div class="pj-panel cl-reveal">
                        <div class="pj-panel__head">
                            <h4 class="pj-panel__title">Historia kierownictwa</h4>
                            <span class="pj-panel__hint">Okresy prowadzenia ekipy</span>
                        </div>
                        @if($project->siteLeads->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0 pj-history-table">
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
                                        @foreach($project->siteLeads as $lead)
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
                                                        <x-employee-cell :employee="$lead->employee" :show-phone="false" avatar-size="28px" />
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                                <td class="font-mono small text-nowrap">
                                                    {{ $lead->start_date->format('d.m.Y') }}
                                                    –
                                                    {{ $lead->end_date ? $lead->end_date->format('d.m.Y') : 'bez terminu' }}
                                                </td>
                                                <td>
                                                    <x-ui.badge variant="{{ $statusVariant }}">{{ $statusLabel }}</x-ui.badge>
                                                </td>
                                                @if($canManage)
                                                    <td class="text-end text-nowrap">
                                                        <a
                                                            href="{{ route('projects.site-leads.edit', [$project, $lead]) }}"
                                                            class="btn btn-sm btn-outline-secondary"
                                                            title="Edytuj"
                                                        >
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <form
                                                            action="{{ route('projects.site-leads.destroy', [$project, $lead]) }}"
                                                            method="POST"
                                                            class="d-inline"
                                                            onsubmit="return confirm('Usunąć ten wpis kierownika?')"
                                                        >
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-outline-secondary" title="Usuń">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="pm-empty py-2 mb-0">
                                <i class="bi bi-clock-history"></i>
                                Brak wpisów kierownictwa.
                            </div>
                        @endif
                    </div>

                    @if($projectTypeValue === \App\Enums\ProjectType::HOURLY->value)
                        <div class="pj-panel cl-reveal">
                            <div class="pj-panel__head">
                                <h4 class="pj-panel__title">Historia stawek</h4>
                                <span class="pj-panel__hint">Nowa stawka zamyka poprzednią dzień wcześniej</span>
                            </div>
                            @if($project->hourlyRates->isNotEmpty())
                                <div class="pj-rates">
                                    @foreach($project->hourlyRates as $rate)
                                        <div class="pj-rate {{ $rate->end_date === null ? 'pj-rate--open' : '' }}">
                                            <span class="pj-rate__amt font-mono">
                                                {{ number_format($rate->amount, 2, ',', ' ') }} {{ $rate->currency }}/h
                                            </span>
                                            <span class="pj-rate__when">
                                                {{ $rate->start_date->format('d.m.Y') }}
                                                –
                                                {{ $rate->end_date ? $rate->end_date->format('d.m.Y') : 'nadal' }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="small text-muted mb-3">Brak okresów — pierwsza kwota od wskazanego dnia.</p>
                            @endif
                            @if(auth()->user()->hasPermission('projects.update'))
                                <form method="POST" action="{{ route('projects.hourly-rates.update', $project) }}" class="row g-2 align-items-end">
                                    @csrf
                                    <div class="col-sm-4">
                                        <label class="form-label small mb-1" for="hourly_rate_amount">Nowa stawka</label>
                                        <input id="hourly_rate_amount" name="amount" type="number" step="0.01" min="0" required class="form-control form-control-sm @error('amount') is-invalid @enderror" value="{{ old('amount') }}">
                                        @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-sm-4">
                                        <label class="form-label small mb-1" for="hourly_rate_start">Od dnia</label>
                                        <input id="hourly_rate_start" name="start_date" type="date" required class="form-control form-control-sm @error('start_date') is-invalid @enderror" value="{{ old('start_date') }}">
                                        @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-sm-4">
                                        <button type="submit" class="btn btn-primary btn-sm">Ustaw od tego dnia</button>
                                    </div>
                                </form>
                            @endif
                        </div>
                    @endif
                </div>
            @endif

            @if(! $isMineView)
                <div class="pj-panel cl-reveal">
                    <livewire:project-demand-timeline :project="$project" :wire:key="'pdt-'.$project->id" />
                </div>

                <div class="cl-reveal">
                    <x-equipment.destination-consumptions
                        :consumable="$project"
                        empty="Brak rozchodów z magazynu na ten projekt."
                    />
                </div>
            @endif
        </div>
    @elseif($activeTab === 'files')
        <!-- Zakładka Pliki -->
        <div id="files" role="tabpanel">
            <x-project-files :project="$project" />
        </div>
    @elseif($activeTab === 'timeline')
        <div id="timeline" role="tabpanel">
            <livewire:resource-assignment-timeline resource-type="project" :resource-id="$project->id" :wire:key="'project-timeline-'.$project->id" />
        </div>
    @elseif($activeTab === 'assignments')
        <!-- Zakładka Przypisani pracownicy -->
        <div id="assignments" role="tabpanel">
            <x-ui.card label="Przypisani Pracownicy">
                @php
                    // Load assignments if not already loaded
                    if (!$project->relationLoaded('assignments')) {
                        $assignments = $project->assignments()->with(['employee', 'role'])->get();
                    } else {
                        $assignments = $project->assignments;
                    }
                @endphp
                @if($assignments->count() > 0)
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Pracownik</th>
                                    <th>Rola</th>
                                    <th>Okres</th>
                                    <th>Status</th>
                                    <th class="text-end">Akcje</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($assignments as $assignment)
                                    <tr>
                                        <td>
                                            <a href="{{ route('employees.show', $assignment->employee) }}" class="text-primary text-decoration-none">
                                                <x-employee-cell :employee="$assignment->employee" />
                                            </a>
                                            @if($project->siteLeads->contains(fn ($lead) => $lead->employee_id === $assignment->employee_id && $lead->isCurrentlyActive()))
                                                <x-project-site-lead-badge class="mt-1" />
                                            @endif
                                        </td>
                                        <td>
                                            <x-ui.badge variant="info">{{ $assignment->role->name }}</x-ui.badge>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ $assignment->start_date->format('Y-m-d') }} - 
                                                {{ $assignment->end_date ? $assignment->end_date->format('Y-m-d') : 'Bieżące' }}
                                            </small>
                                        </td>
                                        <td>
                                            @php
                                                $status = $assignment->status ?? \App\Enums\AssignmentStatus::ACTIVE;
                                                $statusValue = $status instanceof \App\Enums\AssignmentStatus ? $status->value : $status;
                                                $badgeVariant = match($statusValue) {
                                                    'active' => 'success',
                                                    'completed' => 'info',
                                                    'cancelled' => 'danger',
                                                    'in_transit' => 'warning',
                                                    'at_base' => 'info',
                                                    default => 'info'
                                                };
                                            @endphp
                                            <x-ui.badge variant="{{ $badgeVariant }}">{{ ucfirst($statusValue) }}</x-ui.badge>
                                        </td>
                                        <td>
                                            <x-ui.button 
                                                variant="ghost" 
                                                href="{{ route('project-assignments.show', $assignment) }}"
                                                routeName="project-assignments.show"
                                                action="view"
                                            />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <x-ui.empty-state 
                        icon="people" 
                        message="Brak przypisanych pracowników"
                    />
                @endif
            </x-ui.card>
        </div>
    @elseif($activeTab === 'comments')
        <div id="comments" role="tabpanel">
            <x-comments
                :commentable="$project"
                label="Raporty z działania"
                input-label="Dodaj raport z działania"
                button-text="Dodaj raport"
            />
        </div>
    @elseif($activeTab === 'evaluations')
        <!-- Zakładka Oceny pracowników -->
        <div id="evaluations" role="tabpanel">
            <x-project-evaluations :project="$project" />
        </div>
    @endif
</div>
</div>
