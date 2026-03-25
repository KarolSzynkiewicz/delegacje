<div>
    <x-ui.card class="mb-4">     
        <!-- Search bar -->
        <div class="row">
            <div class="col-md-8">
                <div class="row g-2">
                    <div class="col-md-3">
                <input 
                    type="text" 
                    wire:model.live.debounce.300ms="searchTask" 
                    placeholder="Szukaj zadania..."
                    class="form-control form-control-sm">
            </div>
          
                    <div class="col-md-3">
                <input 
                    type="text" 
                    wire:model.live.debounce.300ms="searchProject" 
                    placeholder="Szukaj projektu..."
                    class="form-control form-control-sm">
                    </div>

                    <div class="col-md-3">
                        <input 
                            type="text" 
                            wire:model.live.debounce.300ms="searchCategory" 
                            placeholder="Szukaj kategorii..."
                            class="form-control form-control-sm">
                    </div>

                    <div class="col-md-3">
                        <input 
                            type="text" 
                            wire:model.live.debounce.300ms="searchAssignedTo" 
                            placeholder="Szukaj przypisanej osoby..."
                            class="form-control form-control-sm">
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="d-flex gap-2 flex-wrap h-100 align-items-center">
                    <div class="btn-group" role="group">
                        <button 
                            type="button"
                            wire:click="$set('status', '')"
                            class="btn btn-sm {{ $status === '' ? 'btn-primary' : 'btn-outline-primary' }}"
                        >
                            Aktywne
                        </button>
                        <button 
                            type="button"
                            wire:click="$set('status', 'closed')"
                            class="btn btn-sm {{ $status === 'closed' ? 'btn-primary' : 'btn-outline-primary' }}"
                        >
                            Zamknięte
                        </button>
                    </div>
                    <button 
                        type="button"
                        wire:click="toggleMyTasks"
                        class="btn btn-sm {{ $myTasksOnly ? 'btn-primary' : 'btn-outline-primary' }}"
                    >
                        <i class="bi bi-person-check me-1"></i>Moje zadania
                    </button>
                </div>
            </div>
        </div>
    </x-ui.card>

    @if($tasks->count() > 0)
        <!-- Sortowanie -->
        <div class="mb-3 d-flex gap-2 flex-wrap align-items-center">
            <small class="text-muted">Sortuj po:</small>
            <button 
                type="button" 
                wire:click="sortBy('priority')" 
                class="btn btn-sm btn-outline-secondary"
            >
                <i class="bi bi-exclamation-triangle me-1"></i> priorytecie
                @if($sortField === 'priority')
                    <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                @endif
            </button>
            <button 
                type="button" 
                wire:click="sortBy('due_date')" 
                class="btn btn-sm btn-outline-secondary"
            >
                <i class="bi bi-calendar-event me-1"></i> dacie wykonania
                @if($sortField === 'due_date')
                    <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                @endif
            </button>
            <button 
                type="button" 
                wire:click="sortBy('created_at')" 
                class="btn btn-sm btn-outline-secondary"
            >
                <i class="bi bi-calendar-plus me-1"></i> dacie utworzenia
                @if($sortField === 'created_at')
                    <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                @endif
            </button>
            <button 
                type="button" 
                wire:click="sortBy('updated_at')" 
                class="btn btn-sm btn-outline-secondary"
            >
                <i class="bi bi-pencil-square me-1"></i> dacie edycji
                @if($sortField === 'updated_at')
                    <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                @endif
            </button>
        </div>

        <!-- Karty zadań -->
        <div class="row g-3">
            @foreach($tasks as $task)
                @php
                    $hasProject = $task->project !== null;
                    $badgeVariant = match($task->status) {
                        \App\Enums\TaskStatus::PENDING => 'warning',
                        \App\Enums\TaskStatus::IN_PROGRESS => 'info',
                        \App\Enums\TaskStatus::COMPLETED => 'success',
                        \App\Enums\TaskStatus::CANCELLED => 'danger',
                    };
                    $createdAt = \Carbon\Carbon::parse($task->created_at);
                    $updatedAt = \Carbon\Carbon::parse($task->updated_at);
                    // Support both stored newlines and stored <br> tags (historical data)
                    $taskDescriptionForDisplay = $task->description
                        ? preg_replace('/<br\\s*\\/?\\s*>/i', "\n", (string) $task->description)
                        : null;
                @endphp
                <div class="col-12" wire:key="task-{{ $task->id }}" id="task-{{ $task->id }}">
                    <div class="card">
                        <div class="card-body">
                            <!-- GŁÓWNY WIERSZ 1: Tytuł + Opis (lewa) + Badge (Status, Projekt, Due Date) (prawa) -->
                            <div class="row g-3 mb-3">
                                <!-- Lewa strona: Tytuł + Opis -->
                                <div class="col-md-6">
                                    <a href="{{ route('tasks.show', $task) }}" class="text-decoration-none" style="display: block; cursor: pointer;">
                                        <x-ui.hero-card 
                                        title="{{ $task->name }}" 
                                        subtitle="{{ Str::limit($taskDescriptionForDisplay, 2000) }}"
                                        variant="gradient">
                                        
                                        </x-ui.hero-card>
                                    </a>
                                </div>
                                
                                <!-- Prawa strona: Badge (Status, Projekt, Kategoria, Termin, Komentarze) -->
                                <div class="col-md-6">
                                    <div class="d-flex flex-column gap-2">
                                        <!-- Linia 1: Status / Projekt / Kategoria -->
                                    <div class="d-flex gap-3 flex-wrap align-items-end">
                                        <!-- Status -->
                                        <div>
                                            <small class="text-muted d-block mb-1">
                                                <i class="bi bi-flag me-1"></i>Status
                                            </small>
                                            <x-ui.badge variant="{{ $badgeVariant }}">{{ $task->status->label() }}</x-ui.badge>
                                        </div>
                                        
                                        <!-- Projekt -->
                                        <div>
                                            <small class="text-muted d-block mb-1">
                                                <i class="bi bi-folder me-1"></i>Projekt
                                            </small>
                                            @if($task->project)
                                                <span class="badge bg-secondary">
                                                    <i class="bi bi-folder me-1"></i>{{ $task->project->name }}
                                                </span>
                                            @else
                                                <span class="badge bg-light text-dark">
                                                    <i class="bi bi-x-circle me-1"></i>Brak projektu
                                                </span>
                                            @endif
                                        </div>
                                        
                                            <!-- Kategoria -->
                                            <div>
                                                <small class="text-muted d-block mb-1">
                                                    <i class="bi bi-tag me-1"></i>Kategoria
                                                </small>
                                                @if($task->category)
                                                    <x-ui.badge variant="info">
                                                        <i class="bi bi-tag me-1"></i>{{ Str::limit($task->category, 15) }}
                                                    </x-ui.badge>
                                                @else
                                                    <span class="badge bg-light text-dark">
                                                        <i class="bi bi-x-circle me-1"></i>Brak
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                        
                                        <!-- Linia 2: Termin wykonania -->
                                        <div class="d-flex gap-3 flex-wrap align-items-end">
                                            <div>
                                                <small class="text-muted d-block mb-1">
                                                    <i class="bi bi-calendar-event me-1"></i>Termin wykonania
                                                </small>
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
                                                    <x-ui.badge variant="{{ $dueDateBadgeVariant }}">
                                                        <i class="bi bi-calendar-event me-1"></i>{{ $dueDate->format('d.m.Y') }}
                                                    </x-ui.badge>
                                                @else
                                                    <span class="badge bg-light text-dark">
                                                        <i class="bi bi-x-circle me-1"></i>Nie ustawiono
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                        
                                        <!-- Linia 3: Ilość komentarzy -->
                                        @php
                                            $commentsCount = $task->comments->count();
                                        @endphp
                                        <div class="d-flex gap-3 flex-wrap align-items-end">
                                            <div>
                                                <small class="text-muted d-block mb-1">
                                                    <i class="bi bi-chat-dots me-1"></i>Komentarze
                                                </small>
                                                <span class="fw-semibold">{{ $commentsCount }}</span>
                                            </div>
                                        </div>
                                        
                                        <!-- Linia 4: Ostatni komentarz -->
                                        @php
                                            $lastComment = $task->comments->sortByDesc('created_at')->first();
                                        @endphp
                                        @if($lastComment)
                                            <div>
                                                <small class="text-muted d-block mb-1">
                                                    <i class="bi bi-chat-quote me-1"></i>Ostatni komentarz
                                                </small>
                                                <div>
                                                    <small class="text-break">{{ Str::limit($lastComment->body, 100) }}</small>
                                                    <br>
                                                    <small class="text-muted">
                                                        {{ $lastComment->user->name }} - {{ $lastComment->created_at->format('d.m.Y H:i') }}
                                                    </small>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Podzadania -->
                            @php
                                $subtasksCount = $task->subtasks->count();
                                $completedSubtasksCount = $task->subtasks->where('is_completed', true)->count();
                            @endphp
                            @if($subtasksCount > 0)
                                <div class="mt-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <small class="text-muted">
                                            <i class="bi bi-list-check me-1"></i>Podzadania: {{ $subtasksCount }}
                                        </small>
                                        <div style="flex: 1; max-width: 200px;">
                                            <x-ui.progress 
                                                value="{{ $completedSubtasksCount }}" 
                                                max="{{ $subtasksCount }}" 
                                                variant="{{ $completedSubtasksCount == $subtasksCount ? 'success' : ($completedSubtasksCount > 0 ? 'warning' : 'default') }}"
                                            />
                                        </div>
                                        <small class="text-muted">
                                            {{ $completedSubtasksCount }}/{{ $subtasksCount }} ukończone
                                        </small>
                                    </div>
                                </div>
                            @endif
                            
                            <!-- GŁÓWNY WIERSZ 2: Detale w Bootstrap row -->
                            <hr>
                            <div class="row g-3">
                                <!-- Data utworzenia -->
                                <div class="col-md-3">
                                    <small class="text-muted d-block mb-1">
                                        <i class="bi bi-calendar-plus me-1"></i>Utworzono
                                    </small>
                                    <div>
                                        <small class="fw-semibold">{{ $createdAt->format('d.m.Y H:i') }}</small>
                                        <br>
                                        <small class="text-muted">{{ $createdAt->diffForHumans() }}</small>
                                    </div>
                                </div>
                                
                                <!-- Data edycji -->
                                <div class="col-md-3">
                                    <small class="text-muted d-block mb-1">
                                        <i class="bi bi-pencil-square me-1"></i>Zmodyfikowano
                                    </small>
                                    <div>
                                        <small class="fw-semibold">{{ $updatedAt->format('d.m.Y H:i') }}</small>
                                            <br>
                                            <small class="text-muted">{{ $updatedAt->diffForHumans() }}</small>
                                    </div>
                                </div>
                                
                                <!-- Przypisany -->
                                <div class="col-md-3">
                                    <small class="text-muted d-block mb-1">
                                        <i class="bi bi-person-check me-1"></i>Przypisany
                                    </small>
                                    <div>
                                        @if($task->assignedTo)
                                            <x-ui.person :user="$task->assignedTo" avatar-size="32px" :show-email="false" />
                                        @else
                                            <span class="text-muted">Nie przypisane</span>
                                        @endif
                                    </div>
                                </div>
                                
                                <!-- Akcje -->
                                <div class="col-md-3">
                                    <small class="text-muted d-block mb-1">
                                        <i class="bi bi-gear me-1"></i>Akcje
                                    </small>
                                    <div class="d-flex gap-1 flex-wrap">
                                        @if($task->status === \App\Enums\TaskStatus::PENDING)
                                            <form action="{{ route('tasks.mark-in-progress', $task) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-info" title="Rozpocznij">
                                                    <i class="bi bi-play-circle"></i>
                                                </button>
                                            </form>
                                        @elseif($task->status === \App\Enums\TaskStatus::IN_PROGRESS)
                                            <form action="{{ route('tasks.mark-completed', $task) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success" title="Zakończ">
                                                    <i class="bi bi-check-circle"></i>
                                                </button>
                                            </form>
                                        @endif
                                        
                                        @if($task->status !== \App\Enums\TaskStatus::CANCELLED && $task->status !== \App\Enums\TaskStatus::COMPLETED)
                                            <form action="{{ route('tasks.cancel', $task) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-danger" title="Anuluj">
                                                    <i class="bi bi-x-circle"></i>
                                                </button>
                                            </form>
                                        @endif
                                        
                                        <a href="{{ route('tasks.show', $task) }}" 
                                           class="btn btn-sm btn-outline-secondary" 
                                           title="Podgląd">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @if(!$isMineView)
                                            <a href="{{ route('tasks.edit', $task) }}" 
                                               class="btn btn-sm btn-outline-primary" 
                                               title="Edytuj">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if($tasks->hasPages())
            <div class="mt-3">
                {{ $tasks->links() }}
            </div>
        @endif
    @else
        <x-ui.empty-state 
            icon="list-check"
            message="Brak zadań"
        />
    @endif
</div>
