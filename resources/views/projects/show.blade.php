<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Projekt: {{ $project->name }}">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('projects.index') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
            <x-slot name="right">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('projects.edit', $project) }}"
                    routeName="projects.edit"
                    action="edit"
                >
                    Edytuj
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="container-xxl">
        <div class="row">
            <div class="col-md-12">
                <!-- Zakładki -->
                @php
                    $activeTab = $activeTab ?? 'info';
                @endphp
                <ul class="nav nav-tabs mb-4" id="projectTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link {{ $activeTab === 'info' ? 'active' : '' }}" href="{{ route('projects.show', $project) }}">
                            Informacje
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link {{ $activeTab === 'files' ? 'active' : '' }}" href="{{ route('projects.show.files', $project) }}">
                            Pliki ({{ $project->files_count ?? 0 }})
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link {{ $activeTab === 'tasks' ? 'active' : '' }}" href="{{ route('projects.show.tasks', $project) }}">
                            Zadania ({{ $project->tasks_count ?? 0 }})
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link {{ $activeTab === 'assignments' ? 'active' : '' }}" href="{{ route('projects.show.assignments', $project) }}">
                            Przypisani pracownicy ({{ $project->assignments_count ?? 0 }})
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link {{ $activeTab === 'comments' ? 'active' : '' }}" href="{{ route('projects.show.comments', $project) }}">
                            Komentarze ({{ $project->comments_count ?? 0 }})
                        </a>
                    </li>
                </ul>

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
                    @if($projectTypeValue === \App\Enums\ProjectType::HOURLY->value)
                        <x-ui.detail-item label="Stawka za godzinę:">
                            {{ $project->hourly_rate ? number_format($project->hourly_rate, 2) . ' ' . ($project->currency ?? 'PLN') : '-' }}
                        </x-ui.detail-item>
                    @elseif($projectTypeValue === \App\Enums\ProjectType::CONTRACT->value)
                        <x-ui.detail-item label="Kwota kontraktu:">
                            {{ $project->contract_amount ? number_format($project->contract_amount, 2) . ' ' . ($project->currency ?? 'PLN') : '-' }}
                        </x-ui.detail-item>
                    @endif
                    <x-ui.detail-item label="Budżet:">{{ $project->budget ? number_format($project->budget, 2) . ' PLN' : '-' }}</x-ui.detail-item>
                    @if($project->location)
                    <x-ui.detail-item label="Lokalizacja:">{{ $project->location->name }}</x-ui.detail-item>
                    @endif
                    @if($project->description)
                    <x-ui.detail-item label="Opis:" :full-width="true">{{ $project->description }}</x-ui.detail-item>
                    @endif
                </x-ui.detail-list>

            </x-ui.card>

            @if($project->demand)
            <x-ui.card label="Zapotrzebowanie" class="mt-4">
                <dl class="row mb-0">
                    <div class="col-md-6 mb-2">
                        <dt class="fw-semibold">Liczba pracowników:</dt>
                        <dd>{{ $project->demand->required_workers_count }}</dd>
                    </div>
                    <div class="col-md-6 mb-2">
                        <dt class="fw-semibold">Od:</dt>
                        <dd>{{ $project->demand->start_date->format('Y-m-d') }}</dd>
                    </div>
                    <div class="col-md-6 mb-2">
                        <dt class="fw-semibold">Do:</dt>
                        <dd>{{ $project->demand->end_date ? $project->demand->end_date->format('Y-m-d') : 'Nieokreślone' }}</dd>
                    </div>
                </dl>
            </x-ui.card>
                    @endif
                        </x-ui.card>
                    </div>
                    @elseif($activeTab === 'files')
                    <!-- Zakładka Pliki -->
                    <div id="files" role="tabpanel">
                        <x-project-files :project="$project" />
                    </div>
                    @elseif($activeTab === 'tasks')
                    <!-- Zakładka Zadania -->
                    <div id="tasks" role="tabpanel">
                        <x-project-tasks :project="$project" :users="$users ?? []" />
                    </div>
                    @elseif($activeTab === 'assignments')
                    <!-- Zakładka Przypisani pracownicy -->
                    <div id="assignments" role="tabpanel">
                        <x-ui.card label="Przypisani Pracownicy">
                            @if($project->assignments->count() > 0)
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
                                            @foreach($project->assignments as $assignment)
                                                <tr>
                                                    <td>
                                                        <a href="{{ route('employees.show', $assignment->employee) }}" class="text-primary text-decoration-none">
                                                            {{ $assignment->employee->full_name }}
                                                        </a>
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
                                                            href="{{ route('assignments.show', $assignment) }}"
                                                            routeName="assignments.show"
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
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
