<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Zadanie: {{ $task->name }}">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('tasks.index') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
            <x-slot name="right">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('tasks.edit', $task) }}"
                    routeName="tasks.edit"
                    action="edit"
                >
                    Edytuj
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <x-ui.card>
                <!-- Główny wiersz: Tytuł + Badge (Status, Projekt, Due Date) -->
                <div class="row mb-3">
                    <!-- Lewa strona: Tytuł -->
                    <div class="col-md-6">
                        <div class="mb-2">
                            <small class="text-muted d-block mb-1">
                                <i class="bi bi-list-task me-1"></i>Nazwa zadania
                            </small>
                            <h4 class="fw-bold mb-0">{{ $task->name }}</h4>
                        </div>
                        
                        <!-- Opis pod tytułem -->
                        @if($task->description)
                            <div class="mt-3">
                                <small class="text-muted d-block mb-1">
                                    <i class="bi bi-card-text me-1"></i>Opis
                                </small>
                                <div class="text-break">{{ $task->description }}</div>
                            </div>
                        @endif
                    </div>
                    
                    <!-- Prawa strona: Badge (Status, Projekt, Due Date) -->
                    <div class="col-md-6">
                        <div class="d-flex flex-column gap-2">
                            <!-- Status -->
                            <div>
                                <small class="text-muted d-block mb-1">
                                    <i class="bi bi-flag me-1"></i>Status
                                </small>
                                @php
                                    $badgeVariant = match($task->status) {
                                        \App\Enums\TaskStatus::PENDING => 'warning',
                                        \App\Enums\TaskStatus::IN_PROGRESS => 'info',
                                        \App\Enums\TaskStatus::COMPLETED => 'success',
                                        \App\Enums\TaskStatus::CANCELLED => 'danger',
                                    };
                                @endphp
                                <x-ui.badge variant="{{ $badgeVariant }}">{{ $task->status->label() }}</x-ui.badge>
                            </div>
                            
                            <!-- Projekt -->
                            @if($task->project)
                                <div>
                                    <small class="text-muted d-block mb-1">
                                        <i class="bi bi-folder me-1"></i>Projekt
                                    </small>
                                    <a href="{{ route('projects.show', $task->project) }}" class="text-decoration-none">
                                        <span class="badge bg-secondary">{{ $task->project->name }}</span>
                                    </a>
                                </div>
                            @endif
                            
                            <!-- Due Date Badge -->
                            @if($task->due_date)
                                @php
                                    $dueDate = \Carbon\Carbon::parse($task->due_date);
                                    $now = \Carbon\Carbon::now();
                                    $isPast = $dueDate->isPast();
                                    $isToday = $dueDate->isToday();
                                    $daysDiff = $now->diffInDays($dueDate, false); // false = signed difference
                                    
                                    // Określ kolor badge
                                    $dueDateBadgeVariant = 'info'; // Niebieski - domyślnie
                                    if ($isPast || $isToday) {
                                        $dueDateBadgeVariant = 'danger'; // Czerwony - dzisiaj lub w przeszłości
                                    } elseif ($daysDiff <= 3) {
                                        $dueDateBadgeVariant = 'warning'; // Żółty - w ciągu najbliższych 3 dni
                                    }
                                @endphp
                                <div>
                                    <small class="text-muted d-block mb-1">
                                        <i class="bi bi-calendar-event me-1"></i>Termin wykonania
                                    </small>
                                    <span class="badge bg-{{ $dueDateBadgeVariant }}">
                                        <i class="bi bi-calendar-event me-1"></i>{{ $dueDate->format('d.m.Y') }}
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                
                <!-- Szczegóły -->
                <hr class="my-4">
                <x-ui.detail-list>
                    <x-ui.detail-item label="Przypisany do">
                        @if($task->assignedTo)
                            {{ $task->assignedTo->name }}
                        @else
                            <span class="text-muted">Nie przypisane</span>
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

            <div class="mt-4">
                <livewire:task-subtasks :task="$task" />
            </div>

            <x-ui.card label="Akcje" class="mt-4">
                <x-tasks-actions :task="$task" :project="$task->project" size="sm" gap="2" class="flex-wrap" />
            </x-ui.card>

            <x-ui.card label="Komentarze" class="mt-4">
                <x-comments :commentable="$task" />
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
