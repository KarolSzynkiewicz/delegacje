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
                        <th style="cursor: pointer; width: 35%;" wire:click="sortBy('name')" class="ps-3">
                            Zadanie
                            @if($sortField === 'name')
                                <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                            @endif
                        </th>
                        <th style="cursor: pointer; width: 35%;" wire:click="sortBy('due_date')">
                            Metadane
                            @if($sortField === 'due_date')
                                <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                            @endif
                        </th>
                        <th style="width: 30%;" class="pe-3">Status & Akcje</th>
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
                            $isOverdue = $task->due_date && $task->due_date->isPast() && !in_array($task->status, [\App\Enums\TaskStatus::COMPLETED, \App\Enums\TaskStatus::CANCELLED]);
                        @endphp
                        <tr wire:key="task-{{ $task->id }}" class="border-bottom">
                            <!-- COL 1: Informacje podstawowe (blok identyfikacyjny) -->
                            <td class="ps-3 py-3">
                                <div class="d-flex flex-column">
                                    <!-- Projekt (label/kategoria) -->
                                    <div class="mb-2">
                                        @if($task->project)
                                            <span class="badge bg-secondary text-uppercase small">
                                                {{ $task->project->name }}
                                            </span>
                                        @else
                                            <span class="badge bg-light text-dark text-uppercase small">
                                                Brak projektu
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
                                </div>
                            </td>
                            
                            <!-- COL 2: Metadane (grid wewnętrzny 2 kolumny) -->
                            <td class="py-3">
                                <div class="row g-3">
                                    <!-- Lewy sub-column (col1/2) -->
                                    <div class="col-6">
                                        <!-- Termin -->
                                        <div class="mb-3">
                                            <small class="text-muted d-block mb-1">Termin</small>
                                            @if($task->due_date)
                                                <span class="{{ $isOverdue ? 'text-danger fw-bold' : '' }}">
                                                    {{ $task->due_date->format('d.m.Y') }}
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </div>
                                        
                                        <!-- Zmodyfikowano -->
                                        <div>
                                            <small class="text-muted d-block mb-1">Zmodyfikowano</small>
                                            <span>{{ $updatedAt->format('d.m.Y H:i') }}</span>
                                        </div>
                                    </div>
                                    
                                    <!-- Prawy sub-column (col2/2) -->
                                    <div class="col-6">
                                        <!-- Przypisany -->
                                        <div class="mb-3">
                                            <small class="text-muted d-block mb-1">Przypisany</small>
                                            @if($task->assignedTo)
                                                <span>{{ $task->assignedTo->name }}</span>
                                            @else
                                                <span class="text-muted">Nie przypisane</span>
                                            @endif
                                        </div>
                                        
                                        <!-- Stworzył -->
                                        <div>
                                            <small class="text-muted d-block mb-1">Stworzył</small>
                                            @if($task->createdBy)
                                                <span>{{ $task->createdBy->name }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            
                            <!-- COL 3: Status + akcje (grid wewnętrzny 3 kolumny, 2 rzędy) -->
                            <td class="pe-3 py-3">
                                <div class="row g-2">
                                    <!-- Row 1: Status, Action button, Cancel button -->
                                    <div class="col-12">
                                        <div class="row g-2">
                                            <!-- col1/3: Status -->
                                            <div class="col-4">
                                                <x-ui.badge variant="{{ $badgeVariant }}">{{ $task->status->label() }}</x-ui.badge>
                                            </div>
                                            
                                            <!-- col2/3: Action button -->
                                            <div class="col-4">
                                                @if($hasProject && $task->status === \App\Enums\TaskStatus::PENDING)
                                                    <form action="{{ route('projects.tasks.mark-in-progress', [$task->project, $task]) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-info w-100" title="Rozpocznij">
                                                            <i class="bi bi-play-circle"></i>
                                                        </button>
                                                    </form>
                                                @elseif($hasProject && $task->status === \App\Enums\TaskStatus::IN_PROGRESS)
                                                    <form action="{{ route('projects.tasks.mark-completed', [$task->project, $task]) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-success w-100" title="Zakończ">
                                                            <i class="bi bi-check-circle"></i>
                                                        </button>
                                                    </form>
                                                @else
                                                    <span class="text-muted small">-</span>
                                                @endif
                                            </div>
                                            
                                            <!-- col3/3: Cancel button -->
                                            <div class="col-4">
                                                @if($hasProject && $task->status !== \App\Enums\TaskStatus::CANCELLED && $task->status !== \App\Enums\TaskStatus::COMPLETED)
                                                    <form action="{{ route('projects.tasks.cancel', [$task->project, $task]) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-danger w-100" title="Anuluj">
                                                            <i class="bi bi-x-circle"></i>
                                                        </button>
                                                    </form>
                                                @else
                                                    <span class="text-muted small">-</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Row 2: Utworzono, Ikona oka, Ikona ołówka -->
                                    <div class="col-12">
                                        <div class="row g-2">
                                            <!-- col1/3: Utworzono -->
                                            <div class="col-4">
                                                <small class="text-muted d-block">{{ $createdAt->format('d.m.Y') }}</small>
                                            </div>
                                            
                                            <!-- col2/3: Ikona oka (podgląd) -->
                                            <div class="col-4">
                                                @if($hasProject)
                                                    <a href="{{ route('projects.tasks.show', [$task->project, $task]) }}" 
                                                       class="btn btn-sm btn-outline-secondary w-100" 
                                                       title="Podgląd">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                @else
                                                    <span class="text-muted small">-</span>
                                                @endif
                                            </div>
                                            
                                            <!-- col3/3: Ikona ołówka (edycja) -->
                                            <div class="col-4">
                                                @if($hasProject && !$isMineView)
                                                    <a href="{{ route('projects.tasks.edit', [$task->project, $task]) }}" 
                                                       class="btn btn-sm btn-outline-primary w-100" 
                                                       title="Edytuj">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                @else
                                                    <span class="text-muted small">-</span>
                                                @endif
                                            </div>
                                        </div>
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
