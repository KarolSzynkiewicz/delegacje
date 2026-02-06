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
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th style="cursor: pointer;" wire:click="sortBy('name')" class="ps-3 d-none d-md-table-cell" style="width: 35%;">
                            <i class="bi bi-list-task me-1"></i> Zadanie
                            @if($sortField === 'name')
                                <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                            @endif
                        </th>
                        <th class="d-md-none ps-3" style="width: 100%;">Zadanie</th>
                        <th style="cursor: pointer;" wire:click="sortBy('created_at')" class="d-none d-lg-table-cell" style="width: 20%;">
                            <i class="bi bi-calendar-plus me-1"></i> Utworzono
                            @if($sortField === 'created_at')
                                <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                            @endif
                        </th>
                        <th style="cursor: pointer;" wire:click="sortBy('updated_at')" class="d-none d-lg-table-cell" style="width: 20%;">
                            <i class="bi bi-pencil-square me-1"></i> Zmodyfikowano
                            @if($sortField === 'updated_at')
                                <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                            @endif
                        </th>
                        <th style="cursor: pointer;" wire:click="sortBy('due_date')" class="d-none d-lg-table-cell" style="width: 15%;">
                            <i class="bi bi-calendar-event me-1"></i> Deadline
                            @if($sortField === 'due_date')
                                <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                            @endif
                        </th>
                        <th class="pe-3 d-none d-md-table-cell" style="width: 10%;">
                            <i class="bi bi-gear me-1"></i> Akcje
                        </th>
                    </tr>
                </thead>
                <tbody>
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
                            $dueDate = $task->due_date ? \Carbon\Carbon::parse($task->due_date) : null;
                            $isOverdue = $dueDate && $dueDate->isPast() && !in_array($task->status, [\App\Enums\TaskStatus::COMPLETED, \App\Enums\TaskStatus::CANCELLED]);
                        @endphp
                        <tr wire:key="task-{{ $task->id }}" class="border-bottom">
                            <!-- COL 1: Informacje podstawowe (blok identyfikacyjny) -->
                            <td class="ps-3 py-3">
                                <div class="d-flex flex-column">
                                    <!-- Projekt (label/kategoria) -->
                                    <div class="mb-2">
                                        @if($task->project)
                                            <span class="badge bg-secondary text-uppercase small">
                                                <i class="bi bi-folder me-1"></i>{{ $task->project->name }}
                                            </span>
                                        @else
                                            <span class="badge bg-light text-dark text-uppercase small">
                                                <i class="bi bi-x-circle me-1"></i>Brak projektu
                                            </span>
                                        @endif
                                    </div>
                                    
                                    <!-- Tytuł zadania (główny tekst, największy weight) -->
                                    <div class="mb-2">
                                        <strong class="fs-5 fw-bold">{{ $task->name }}</strong>
                                    </div>
                                    
                                    <!-- Opis (mniejszy tekst pomocniczy) -->
                                    @if($task->description)
                                        <div class="text-muted small">
                                            {{ Str::limit($task->description, 150) }}
                                        </div>
                                    @endif
                                    
                                    <!-- Mobile: Metadane i akcje -->
                                    <div class="d-md-none mt-3">
                                        <div class="row g-2 mb-2">
                                            <div class="col-6">
                                                <small class="text-muted d-block mb-1">
                                                    <i class="bi bi-calendar-plus me-1"></i>Utworzono
                                                </small>
                                                <div>
                                                    <span class="fw-semibold">{{ $createdAt->format('d.m.Y') }}</span>
                                                    <br>
                                                    <small class="text-muted">({{ $createdAt->diffForHumans() }})</small>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <small class="text-muted d-block mb-1">
                                                    <i class="bi bi-pencil-square me-1"></i>Zmodyfikowano
                                                </small>
                                                <div>
                                                    <span class="fw-semibold">{{ $updatedAt->format('d.m.Y') }}</span>
                                                    <br>
                                                    <small class="text-muted">({{ $updatedAt->diffForHumans() }})</small>
                                                </div>
                                            </div>
                                        </div>
                                        @if($dueDate)
                                            <div class="mb-2">
                                                <small class="text-muted d-block mb-1">
                                                    <i class="bi bi-calendar-event me-1"></i>Deadline
                                                </small>
                                                <div>
                                                    <span class="fw-semibold {{ $isOverdue ? 'text-danger' : '' }}">{{ $dueDate->format('d.m.Y') }}</span>
                                                    <br>
                                                    <small class="text-muted">({{ $dueDate->diffForHumans() }})</small>
                                                </div>
                                            </div>
                                        @endif
                                        <div class="d-flex gap-2 mt-2">
                                            <x-ui.badge variant="{{ $badgeVariant }}">{{ $task->status->label() }}</x-ui.badge>
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
                            </td>
                            
                            <!-- COL 2: Utworzono (desktop) -->
                            <td class="py-3 d-none d-lg-table-cell">
                                <div>
                                    <small class="text-muted d-block mb-1">
                                        <i class="bi bi-calendar-plus me-1"></i>Utworzono
                                    </small>
                                    <div>
                                        <span class="fw-semibold">{{ $createdAt->format('d.m.Y') }}</span>
                                        <br>
                                        <small class="text-muted">({{ $createdAt->diffForHumans() }})</small>
                                    </div>
                                </div>
                            </td>
                            
                            <!-- COL 3: Zmodyfikowano (desktop) -->
                            <td class="py-3 d-none d-lg-table-cell">
                                <div>
                                    <small class="text-muted d-block mb-1">
                                        <i class="bi bi-pencil-square me-1"></i>Zmodyfikowano
                                    </small>
                                    <div>
                                        <span class="fw-semibold">{{ $updatedAt->format('d.m.Y') }}</span>
                                        <br>
                                        <small class="text-muted">({{ $updatedAt->diffForHumans() }})</small>
                                    </div>
                                </div>
                            </td>
                            
                            <!-- COL 4: Deadline (desktop) -->
                            <td class="py-3 d-none d-lg-table-cell">
                                @if($dueDate)
                                    <div>
                                        <small class="text-muted d-block mb-1">
                                            <i class="bi bi-calendar-event me-1"></i>Deadline
                                        </small>
                                        <div>
                                            <span class="fw-semibold {{ $isOverdue ? 'text-danger' : '' }}">{{ $dueDate->format('d.m.Y') }}</span>
                                            <br>
                                            <small class="text-muted">({{ $dueDate->diffForHumans() }})</small>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            
                            <!-- COL 5: Status + akcje (desktop) -->
                            <td class="pe-3 py-3 d-none d-md-table-cell">
                                <div class="d-flex flex-column gap-2">
                                    <!-- Status badge -->
                                    <div>
                                        <x-ui.badge variant="{{ $badgeVariant }}">{{ $task->status->label() }}</x-ui.badge>
                                    </div>
                                    
                                    <!-- Action buttons -->
                                    <div class="d-flex gap-1 flex-wrap">
                                        @if($hasProject && $task->status === \App\Enums\TaskStatus::PENDING)
                                            <form action="{{ route('projects.tasks.mark-in-progress', [$task->project, $task]) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-info" title="Rozpocznij">
                                                    <i class="bi bi-play-circle"></i>
                                                </button>
                                            </form>
                                        @elseif($hasProject && $task->status === \App\Enums\TaskStatus::IN_PROGRESS)
                                            <form action="{{ route('projects.tasks.mark-completed', [$task->project, $task]) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success" title="Zakończ">
                                                    <i class="bi bi-check-circle"></i>
                                                </button>
                                            </form>
                                        @endif
                                        
                                        @if($hasProject && $task->status !== \App\Enums\TaskStatus::CANCELLED && $task->status !== \App\Enums\TaskStatus::COMPLETED)
                                            <form action="{{ route('projects.tasks.cancel', [$task->project, $task]) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-danger" title="Anuluj">
                                                    <i class="bi bi-x-circle"></i>
                                                </button>
                                            </form>
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
                                        @else
                                            <!-- Dla zadań bez projektu - podstawowe akcje -->
                                            <span class="text-muted small" title="Zadania bez projektu nie mają dostępnych akcji">
                                                <i class="bi bi-info-circle"></i>
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
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
