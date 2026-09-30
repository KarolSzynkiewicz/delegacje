<div>
    <x-ui.tabs 
        :tabs="$tabsForComponent" 
        :activeTab="$activeTab" 
        id="projectTabs"
        :compact-mobile="true"
    />

<div id="projectTabsContent">
    @if($activeTab === 'info')
        <!-- Zakładka Informacje -->
        <div id="info" role="tabpanel">
            <x-ui.card label="Szczegóły Projektu: {{ $project->name }}">
                <x-ui.detail-list>
                    <x-ui.detail-item label="Nazwa:">{{ $project->name }}</x-ui.detail-item>
                    <x-ui.detail-item label="Klient:">{{ $project->client_name ?? '-' }}</x-ui.detail-item>
                    <x-ui.detail-item label="Status:">
                        @php
                            $statusValue = $project->status instanceof \App\Enums\ProjectStatus ? $project->status->value : $project->status;
                            $badgeVariant = match($statusValue) {
                                'active' => 'success',
                                'on_hold' => 'warning',
                                'completed' => 'info',
                                'cancelled' => 'danger',
                                default => 'info'
                            };
                        @endphp
                        <x-ui.badge variant="{{ $badgeVariant }}">{{ ucfirst($statusValue) }}</x-ui.badge>
                    </x-ui.detail-item>
                    <x-ui.detail-item label="Typ Projektu:">
                        @if($project->type)
                            <x-ui.badge variant="info">{{ $project->type->label() }}</x-ui.badge>
                        @else
                            -
                        @endif
                    </x-ui.detail-item>
                    @php
                        $projectTypeValue = $project->type instanceof \App\Enums\ProjectType ? $project->type->value : ($project->type ?? null);
                    @endphp
                    @if(!$isMineView)
                        @if($projectTypeValue === \App\Enums\ProjectType::HOURLY->value)
                            @php
                                $openRate = $project->hourlyRates->first(fn ($rate) => $rate->end_date === null);
                            @endphp
                            <x-ui.detail-item label="Stawka za godzinę:">
                                @if($openRate)
                                    {{ number_format($openRate->amount, 2, ',', ' ') }} {{ $openRate->currency }}/h
                                    <span class="text-muted">od {{ $openRate->start_date->format('d.m.Y') }}</span>
                                @else
                                    {{ $project->hourly_rate ? number_format($project->hourly_rate, 2, ',', ' ').' '.($project->currency ?? 'PLN').'/h' : '-' }}
                                @endif
                            </x-ui.detail-item>
                        @elseif($projectTypeValue === \App\Enums\ProjectType::CONTRACT->value)
                            <x-ui.detail-item label="Kwota kontraktu:">
                                {{ $project->contract_amount ? number_format($project->contract_amount, 2) . ' ' . ($project->currency ?? 'PLN') : '-' }}
                            </x-ui.detail-item>
                        @endif
                        <x-ui.detail-item label="Budżet:">{{ $project->budget ? number_format($project->budget, 2) . ' ' . ($project->currency ?? 'PLN') : '-' }}</x-ui.detail-item>
                    @endif
                    @if($project->location)
                    <x-ui.detail-item label="Lokalizacja:">{{ $project->location->name }}</x-ui.detail-item>
                    @endif
                    @if($project->description)
                    <x-ui.detail-item label="Opis:" :full-width="true">{{ $project->description }}</x-ui.detail-item>
                    @endif
                </x-ui.detail-list>
            </x-ui.card>

            @if(! $isMineView && $projectTypeValue === \App\Enums\ProjectType::HOURLY->value)
                <x-ui.card label="Stawki godzinowe" class="mt-4">
                    <p class="small text-muted mb-3">Nowa stawka obowiązuje od wybranego dnia. Poprzednia kończy się dzień wcześniej i zostaje przy godzinach z tamtego okresu.</p>
                    @if($project->hourlyRates->isNotEmpty())
                        <ul class="list-unstyled small mb-3">
                            @foreach($project->hourlyRates as $rate)
                                <li class="d-flex justify-content-between gap-2 py-1 border-bottom">
                                    <span>
                                        {{ number_format($rate->amount, 2, ',', ' ') }} {{ $rate->currency }}/h
                                    </span>
                                    <span class="text-muted font-mono">
                                        {{ $rate->start_date->format('d.m.Y') }}
                                        –
                                        {{ $rate->end_date ? $rate->end_date->format('d.m.Y') : 'nadal' }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-muted small mb-3">Brak okresu stawki. Pierwsza kwota obowiązuje od podanego dnia, bez daty końca.</p>
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
                </x-ui.card>
            @endif

            <x-project-site-leads-panel
                :project="$project"
                :site-leads="$project->siteLeads"
                :can-manage="! $isMineView && auth()->user()->hasPermission('projects.update')"
            />

            @if(!$isMineView && $project->demands && $project->demands->isNotEmpty())
            <x-ui.card label="Zapotrzebowanie" class="mt-4">
                @foreach($project->demands as $demand)
                    <dl class="row mb-0">
                        <div class="col-md-6 mb-2">
                            <dt class="fw-semibold">Od:</dt>
                            <dd>{{ $demand->start_date->format('Y-m-d') }}</dd>
                        </div>
                        <div class="col-md-6 mb-2">
                            <dt class="fw-semibold">Do:</dt>
                            <dd>{{ $demand->end_date ? $demand->end_date->format('Y-m-d') : 'Nieokreślone' }}</dd>
                        </div>
                    </dl>
                @endforeach
            </x-ui.card>
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
        <!-- Zakładka Komentarze -->
        <div id="comments" role="tabpanel">
            <x-comments :commentable="$project" />
        </div>
    @elseif($activeTab === 'evaluations')
        <!-- Zakładka Oceny pracowników -->
        <div id="evaluations" role="tabpanel">
            <x-project-evaluations :project="$project" />
        </div>
    @endif
</div>
</div>
