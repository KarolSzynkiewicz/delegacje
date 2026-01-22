<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Zadanie: {{ $task->name }}">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('projects.show.tasks', $project) }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
            <x-slot name="right">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('projects.tasks.edit', [$project, $task]) }}"
                    routeName="projects.tasks.edit"
                    action="edit"
                >
                    Edytuj
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-xl-8">
                <x-ui.card label="Szczegóły Zadania">
                    <x-ui.detail-list>
                        <x-ui.detail-item label="Projekt">
                            <a href="{{ route('projects.show', $project) }}" class="text-primary text-decoration-none">
                                {{ $project->name }}
                            </a>
                        </x-ui.detail-item>
                        <x-ui.detail-item label="Nazwa">
                            <strong>{{ $task->name }}</strong>
                        </x-ui.detail-item>
                        <x-ui.detail-item label="Status">
                            @php
                                $badgeVariant = match($task->status) {
                                    \App\Enums\TaskStatus::PENDING => 'warning',
                                    \App\Enums\TaskStatus::IN_PROGRESS => 'info',
                                    \App\Enums\TaskStatus::COMPLETED => 'success',
                                    \App\Enums\TaskStatus::CANCELLED => 'danger',
                                };
                            @endphp
                            <x-ui.badge variant="{{ $badgeVariant }}">{{ $task->status->label() }}</x-ui.badge>
                        </x-ui.detail-item>
                        <x-ui.detail-item label="Przypisany do">
                            @if($task->assignedTo)
                                {{ $task->assignedTo->name }}
                            @else
                                <span class="text-muted">Nie przypisane</span>
                            @endif
                        </x-ui.detail-item>
                        <x-ui.detail-item label="Termin wykonania">
                            @if($task->due_date)
                                @php
                                    $dueDate = \Carbon\Carbon::parse($task->due_date);
                                    $now = \Carbon\Carbon::now();
                                    $isPast = $dueDate->isPast();
                                    $isToday = $dueDate->isToday();
                                    $isFuture = $dueDate->isFuture();
                                @endphp
                                <div>
                                    <span class="fw-semibold">{{ $dueDate->format('d.m.Y') }}</span>
                                    <span class="text-muted ms-2">
                                        @if($isPast && !$isToday)
                                            ({{ $dueDate->diffForHumans() }})
                                        @elseif($isToday)
                                            (dzisiaj)
                                        @elseif($isFuture)
                                            ({{ $dueDate->diffForHumans() }})
                                        @endif
                                    </span>
                                </div>
                            @else
                                <span class="text-muted">Nie określono</span>
                            @endif
                        </x-ui.detail-item>
                        @if($task->completed_at)
                        <x-ui.detail-item label="Data zakończenia">
                            @php
                                $completedAt = \Carbon\Carbon::parse($task->completed_at);
                            @endphp
                            <div>
                                <span class="fw-semibold">{{ $completedAt->format('d.m.Y H:i') }}</span>
                                <span class="text-muted ms-2">({{ $completedAt->diffForHumans() }})</span>
                            </div>
                        </x-ui.detail-item>
                        @endif
                        @if($task->description)
                        <x-ui.detail-item label="Opis" :full-width="true">
                            <div class="text-break">{{ $task->description }}</div>
                        </x-ui.detail-item>
                        @endif
                        <x-ui.detail-item label="Utworzone przez">
                            {{ $task->createdBy->name }}
                        </x-ui.detail-item>
                        <x-ui.detail-item label="Utworzono">
                            @php
                                $createdAt = \Carbon\Carbon::parse($task->created_at);
                            @endphp
                            <div>
                                <span class="fw-semibold">{{ $createdAt->format('d.m.Y H:i') }}</span>
                                <span class="text-muted ms-2">({{ $createdAt->diffForHumans() }})</span>
                            </div>
                        </x-ui.detail-item>
                        <x-ui.detail-item label="Zaktualizowano">
                            @php
                                $updatedAt = \Carbon\Carbon::parse($task->updated_at);
                            @endphp
                            <div>
                                <span class="fw-semibold">{{ $updatedAt->format('d.m.Y H:i') }}</span>
                                <span class="text-muted ms-2">({{ $updatedAt->diffForHumans() }})</span>
                            </div>
                        </x-ui.detail-item>
                    </x-ui.detail-list>
                </x-ui.card>

                <x-ui.card label="Akcje" class="mt-4">
                    <x-tasks-actions :task="$task" :project="$project" size="sm" gap="2" class="flex-wrap" />
                </x-ui.card>

                <x-ui.card label="Komentarze" class="mt-4">
                    <x-comments :commentable="$task" />
                </x-ui.card>
            </div>
        </div>
    </div>
</x-app-layout>
