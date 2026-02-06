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
                        <th style="cursor: pointer; width: 40%;" wire:click="sortBy('name')" class="ps-3">
                            Zadanie
                            @if($sortField === 'name')
                                <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                            @endif
                        </th>
                        <th style="cursor: pointer; width: 50%;" wire:click="sortBy('due_date')">
                            Szczegóły
                            @if($sortField === 'due_date')
                                <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                            @endif
                        </th>
                        <th style="width: 10%;" class="text-end pe-3">Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tasks as $task)
                        <tr wire:key="task-{{ $task->id }}" class="border-bottom">
                            <td class="ps-3 py-3">
                                <!-- Główna linia: Nazwa zadania -->
                                <div class="mb-1">
                                    <strong class="fs-6">{{ $task->name }}</strong>
                                </div>
                                <!-- Druga linia: Opis -->
                                @if($task->description)
                                    <div class="text-muted small">
                                        {{ Str::limit($task->description, 100) }}
                                    </div>
                                @endif
                            </td>
                            <td class="py-3">
                                <!-- Inline metadata -->
                                <div class="d-flex flex-wrap gap-3 align-items-center">
                                    <!-- Projekt -->
                                    <div>
                                        <small class="text-muted d-block mb-1">Projekt</small>
                                        @if($task->project)
                                            <a href="{{ $isMineView ? route('mine.projects.show', $task->project) : route('projects.show', $task->project) }}" class="text-decoration-none">
                                                {{ $task->project->name }}
                                            </a>
                                        @else
                                            <span class="text-muted fst-italic">Brak projektu</span>
                                        @endif
                                    </div>
                                    
                                    <!-- Status -->
                                    <div>
                                        <small class="text-muted d-block mb-1">Status</small>
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
                                    
                                    <!-- Termin -->
                                    <div>
                                        <small class="text-muted d-block mb-1">Termin</small>
                                        @if($task->due_date)
                                            @php
                                                $isOverdue = $task->due_date->isPast() && !in_array($task->status, [\App\Enums\TaskStatus::COMPLETED, \App\Enums\TaskStatus::CANCELLED]);
                                            @endphp
                                            <span class="{{ $isOverdue ? 'text-danger fw-bold' : '' }}">
                                                {{ $task->due_date->format('d.m.Y') }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </div>
                                    
                                    <!-- Przypisany -->
                                    <div>
                                        <small class="text-muted d-block mb-1">Przypisany</small>
                                        @if($task->assignedTo)
                                            <span>{{ $task->assignedTo->name }}</span>
                                        @else
                                            <span class="text-muted">Nie przypisane</span>
                                        @endif
                                    </div>
                                    
                                    <!-- Data utworzenia -->
                                    <div>
                                        <small class="text-muted d-block mb-1">Utworzono</small>
                                        @php
                                            $createdAt = \Carbon\Carbon::parse($task->created_at);
                                        @endphp
                                        <span>{{ $createdAt->format('d.m.Y H:i') }}</span>
                                    </div>
                                    
                                    <!-- Ostatnia modyfikacja -->
                                    <div>
                                        <small class="text-muted d-block mb-1">Zmodyfikowano</small>
                                        @php
                                            $updatedAt = \Carbon\Carbon::parse($task->updated_at);
                                        @endphp
                                        <span>{{ $updatedAt->format('d.m.Y H:i') }}</span>
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
                            </td>
                            <td class="text-end pe-3 py-3">
                                <x-tasks-actions 
                                    :task="$task" 
                                    :project="$task->project ?? null" 
                                    size="sm" 
                                    gap="1" 
                                    :isMineView="$isMineView ?? false" 
                                />
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
