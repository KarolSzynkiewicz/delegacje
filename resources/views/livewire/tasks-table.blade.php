<div>
    <x-ui.card class="mb-4">     
        <!-- Search bar -->
        <div class="row">
            <div class="col-md-4">
                <input 
                    type="text" 
                    wire:model.live.debounce.300ms="searchTask" 
                    placeholder="Szukaj zadania..."
                    class="form-control form-control-sm">
            </div>
          
            <div class="col-md-4">
                <input 
                    type="text" 
                    wire:model.live.debounce.300ms="searchProject" 
                    placeholder="Szukaj projektu..."
                    class="form-control form-control-sm">
            </div>

            <div class="col-md-4">
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
            </div>
        </div>
    </x-ui.card>

    @if($tasks->count() > 0)
        <!-- Sortowanie -->
        <div class="mb-3 d-flex gap-2 flex-wrap align-items-center">
            <small class="text-muted">Sortuj:</small>
            <button 
                type="button" 
                wire:click="sortBy('name')" 
                class="btn btn-sm btn-outline-secondary"
            >
                <i class="bi bi-list-task me-1"></i> Zadanie
                @if($sortField === 'name')
                    <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                @endif
            </button>
            <button 
                type="button" 
                wire:click="sortBy('created_by')" 
                class="btn btn-sm btn-outline-secondary"
            >
                <i class="bi bi-person-plus me-1"></i> Utworzył
                @if($sortField === 'created_by')
                    <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                @endif
            </button>
            <button 
                type="button" 
                wire:click="sortBy('updated_at')" 
                class="btn btn-sm btn-outline-secondary"
            >
                <i class="bi bi-pencil-square me-1"></i> Zmodyfikował
                @if($sortField === 'updated_at')
                    <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                @endif
            </button>
            <button 
                type="button" 
                wire:click="sortBy('assigned_to')" 
                class="btn btn-sm btn-outline-secondary"
            >
                <i class="bi bi-person-check me-1"></i> Przypisany
                @if($sortField === 'assigned_to')
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
                    $updatedAt = \Carbon\Carbon::parse($task->updated_at);
                @endphp
                <div class="col-12" wire:key="task-{{ $task->id }}">
                    <div class="card">
                        <div class="card-body">
                            <!-- GŁÓWNY WIERSZ 1: Tytuł + Mini karta (badge + opis) -->
                            <div class="row g-3 mb-3">
                                <!-- Tytuł zadania -->
                                <div class="col-md-6">
                                    <h5 class="card-title mb-0 fw-bold">{{ $task->name }}</h5>
                                </div>
                                
                                <!-- Mini karta: Badge + Opis -->
                                <div class="col-md-6">
                                    <div class="card bg-light">
                                        <div class="card-body p-3">
                                            <!-- Badge: Status + Projekt -->
                                            <div class="d-flex gap-2 mb-2 flex-wrap">
                                                <x-ui.badge variant="{{ $badgeVariant }}">{{ $task->status->label() }}</x-ui.badge>
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
                                            
                                            <!-- Opis -->
                                            @if($task->description)
                                                <div class="text-muted small">
                                                    {{ Str::limit($task->description, 200) }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- GŁÓWNY WIERSZ 2: Detale w Bootstrap row -->
                            <div class="row g-3">
                                <!-- Utworzył -->
                                <div class="col-md-3">
                                    <small class="text-muted d-block mb-1">
                                        <i class="bi bi-person-plus me-1"></i>Utworzył
                                    </small>
                                    <div>
                                        @if($task->createdBy)
                                            <span class="fw-semibold">{{ $task->createdBy->name }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </div>
                                </div>
                                
                                <!-- Zmodyfikował -->
                                <div class="col-md-3">
                                    <small class="text-muted d-block mb-1">
                                        <i class="bi bi-pencil-square me-1"></i>Zmodyfikował
                                    </small>
                                    <div>
                                        @if($task->createdBy)
                                            <span class="fw-semibold">{{ $task->createdBy->name }}</span>
                                            <br>
                                            <small class="text-muted">{{ $updatedAt->diffForHumans() }}</small>
                                        @else
                                            <span class="text-muted">-</span>
                                            <br>
                                            <small class="text-muted">{{ $updatedAt->diffForHumans() }}</small>
                                        @endif
                                    </div>
                                </div>
                                
                                <!-- Przypisany -->
                                <div class="col-md-3">
                                    <small class="text-muted d-block mb-1">
                                        <i class="bi bi-person-check me-1"></i>Przypisany
                                    </small>
                                    <div>
                                        @if($task->assignedTo)
                                            <span class="fw-semibold">{{ $task->assignedTo->name }}</span>
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
                                            @if($hasProject)
                                                <form action="{{ route('projects.tasks.mark-in-progress', [$task->project, $task]) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-info" title="Rozpocznij">
                                                        <i class="bi bi-play-circle"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <form action="{{ route('tasks.mark-in-progress', $task) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-info" title="Rozpocznij">
                                                        <i class="bi bi-play-circle"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        @elseif($task->status === \App\Enums\TaskStatus::IN_PROGRESS)
                                            @if($hasProject)
                                                <form action="{{ route('projects.tasks.mark-completed', [$task->project, $task]) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success" title="Zakończ">
                                                        <i class="bi bi-check-circle"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <form action="{{ route('tasks.mark-completed', $task) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success" title="Zakończ">
                                                        <i class="bi bi-check-circle"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        @endif
                                        
                                        @if($task->status !== \App\Enums\TaskStatus::CANCELLED && $task->status !== \App\Enums\TaskStatus::COMPLETED)
                                            @if($hasProject)
                                                <form action="{{ route('projects.tasks.cancel', [$task->project, $task]) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Anuluj">
                                                        <i class="bi bi-x-circle"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <form action="{{ route('tasks.cancel', $task) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Anuluj">
                                                        <i class="bi bi-x-circle"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        @endif
                                        
                                        @if($hasProject)
                                            <a href="{{ route('projects.tasks.show', [$task->project, $task]) }}" 
                                               class="btn btn-sm btn-outline-secondary" 
                                               title="Podgląd">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            @if(!$isMineView)
                                                <a href="{{ route('projects.tasks.edit', [$task->project, $task]) }}" 
                                                   class="btn btn-sm btn-outline-primary" 
                                                   title="Edytuj">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                            @endif
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
