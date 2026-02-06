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
                        <th style="cursor: pointer;" wire:click="sortBy('name')" class="ps-3" style="width: 40%;">
                            <i class="bi bi-list-task me-1"></i> Zadanie
                            @if($sortField === 'name')
                                <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                            @endif
                        </th>
                        <th style="cursor: pointer;" wire:click="sortBy('created_by')" style="width: 20%;">
                            <i class="bi bi-person-plus me-1"></i> Utworzył
                            @if($sortField === 'created_by')
                                <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                            @endif
                        </th>
                        <th style="cursor: pointer;" wire:click="sortBy('updated_at')" style="width: 20%;">
                            <i class="bi bi-pencil-square me-1"></i> Zmodyfikował
                            @if($sortField === 'updated_at')
                                <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                            @endif
                        </th>
                        <th style="cursor: pointer;" wire:click="sortBy('assigned_to')" style="width: 15%;">
                            <i class="bi bi-person-check me-1"></i> Przypisany
                            @if($sortField === 'assigned_to')
                                <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                            @endif
                        </th>
                        <th class="pe-3" style="width: 5%;">
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
                            $updatedBy = $task->updatedBy ?? null; // Jeśli masz relację updatedBy w modelu
                        @endphp
                        <!-- Główny wiersz 1: Nazwa zadania + Status/Projekt + Opis -->
                        <tr wire:key="task-{{ $task->id }}-row1" class="border-bottom">
                            <td class="ps-3 py-3" colspan="5">
                                <div class="row g-3">
                                    <!-- Lewa kolumna: Nazwa zadania -->
                                    <div class="col-md-6">
                                        <div class="fw-bold fs-5">{{ $task->name }}</div>
                                    </div>
                                    
                                    <!-- Prawa kolumna: Karta z 2 wierszami -->
                                    <div class="col-md-6">
                                        <div class="card border-0 bg-light">
                                            <div class="card-body p-2">
                                                <!-- Wiersz 1 karty: 2 badge (Status + Projekt) -->
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
                                                
                                                <!-- Wiersz 2 karty: Opis -->
                                                @if($task->description)
                                                    <div class="text-muted small">
                                                        {{ Str::limit($task->description, 200) }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        
                        <!-- Główny wiersz 2: Utworzył + Zmodyfikował + Przypisany + Akcje -->
                        <tr wire:key="task-{{ $task->id }}-row2" class="border-bottom">
                            <!-- Utworzył -->
                            <td class="ps-3 py-2">
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
                            </td>
                            
                            <!-- Zmodyfikował -->
                            <td class="py-2">
                                <small class="text-muted d-block mb-1">
                                    <i class="bi bi-pencil-square me-1"></i>Zmodyfikował
                                </small>
                                <div>
                                    @php
                                        $updatedAt = \Carbon\Carbon::parse($task->updated_at);
                                    @endphp
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
                            </td>
                            
                            <!-- Przypisany -->
                            <td class="py-2">
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
                            </td>
                            
                            <!-- Akcje -->
                            <td class="pe-3 py-2">
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
