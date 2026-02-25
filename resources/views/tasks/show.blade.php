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
                            
                            <!-- Priorytet -->
                            @if($task->priority)
                                @php
                                    $priorityVariant = match((int)$task->priority) {
                                        1, 2 => 'secondary',
                                        3 => 'info',
                                        4 => 'warning',
                                        5 => 'danger',
                                        default => 'secondary',
                                    };
                                    $priorityLabel = match((int)$task->priority) {
                                        1 => 'Najniższy',
                                        2 => 'Niski',
                                        3 => 'Średni',
                                        4 => 'Wysoki',
                                        5 => 'Najwyższy',
                                        default => '',
                                    };
                                @endphp
                                <div>
                                    <small class="text-muted d-block mb-1">
                                        <i class="bi bi-exclamation-triangle me-1"></i>Priorytet
                                    </small>
                                    <x-ui.badge variant="{{ $priorityVariant }}">
                                        <i class="bi bi-{{ $task->priority >= 4 ? 'exclamation-triangle-fill' : 'exclamation-triangle' }} me-1"></i>
                                        {{ $task->priority }} - {{ $priorityLabel }}
                                    </x-ui.badge>
                                </div>
                            @endif
                            
                            <!-- Kategoria -->
                            @if($task->category)
                                <div>
                                    <small class="text-muted d-block mb-1">
                                        <i class="bi bi-tag me-1"></i>Kategoria
                                    </small>
                                    <x-ui.badge variant="info">
                                        <i class="bi bi-tag me-1"></i>{{ $task->category }}
                                    </x-ui.badge>
                                </div>
                            @endif
                            
                            <!-- Due Date Badge -->
                            @if($task->due_date)
                                @php
                                    $dueDate = $task->due_date; // Already a Carbon instance due to model cast
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
                                    <x-ui.badge variant="{{ $dueDateBadgeVariant }}">
                                        <i class="bi bi-calendar-event me-1"></i>{{ $dueDate->format('d.m.Y') }}
                                    </x-ui.badge>
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
                    <x-ui.detail-item label="Priorytet">
                        @if($task->priority)
                            @php
                                $priorityVariant = match((int)$task->priority) {
                                    1, 2 => 'secondary',
                                    3 => 'info',
                                    4 => 'warning',
                                    5 => 'danger',
                                    default => 'secondary',
                                };
                                $priorityLabel = match((int)$task->priority) {
                                    1 => 'Najniższy',
                                    2 => 'Niski',
                                    3 => 'Średni',
                                    4 => 'Wysoki',
                                    5 => 'Najwyższy',
                                    default => '',
                                };
                            @endphp
                            <x-ui.badge variant="{{ $priorityVariant }}">
                                <i class="bi bi-{{ $task->priority >= 4 ? 'exclamation-triangle-fill' : 'exclamation-triangle' }} me-1"></i>
                                {{ $task->priority }} - {{ $priorityLabel }}
                            </x-ui.badge>
                        @else
                            <span class="text-muted">Nie ustawiono</span>
                        @endif
                    </x-ui.detail-item>
                    <x-ui.detail-item label="Kategoria">
                        @if($task->category)
                            <x-ui.badge variant="info">
                                <i class="bi bi-tag me-1"></i>{{ $task->category }}
                            </x-ui.badge>
                        @else
                            <span class="text-muted">Nie ustawiono</span>
                        @endif
                    </x-ui.detail-item>
                    <x-ui.detail-item label="Termin wykonania">
                        @if($task->due_date)
                            @php
                                $dueDate = $task->due_date; // Already a Carbon instance due to model cast
                                $now = \Carbon\Carbon::now();
                                $isPast = $dueDate->isPast();
                                $isToday = $dueDate->isToday();
                            @endphp
                            <div>
                                <span class="fw-semibold">{{ $dueDate->format('d.m.Y') }}</span>
                                <span class="text-muted ms-2">({{ $dueDate->diffForHumans() }})</span>
                                @if($isPast && !$isToday)
                                    <x-ui.badge variant="danger" class="ms-2">Przeterminowane</x-ui.badge>
                                @elseif($isToday)
                                    <x-ui.badge variant="warning" class="ms-2">Dzisiaj</x-ui.badge>
                                @endif
                            </div>
                        @else
                            <span class="text-muted">Nie ustawiono</span>
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

            <x-comments :commentable="$task" class="mt-4" />
        </div>
    </div>
</x-app-layout>
