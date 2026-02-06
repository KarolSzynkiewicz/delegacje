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
                        <th style="cursor: pointer;" wire:click="sortBy('name')">
                            Zadanie
                            @if($sortField === 'name')
                                <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                            @endif
                        </th>
                        <th style="cursor: pointer;" wire:click="sortBy('project')">
                            Projekt
                            @if($sortField === 'project')
                                <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                            @endif
                        </th>
                        <th>Status</th>
                        <th style="cursor: pointer;" wire:click="sortBy('due_date')">
                            Termin
                            @if($sortField === 'due_date')
                                <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                            @endif
                        </th>
                        <th style="cursor: pointer;" wire:click="sortBy('assigned_to')">
                            Przypisany
                            @if($sortField === 'assigned_to')
                                <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                            @endif
                        </th>
                        <th style="cursor: pointer;" wire:click="sortBy('created_at')">
                            Data utworzenia
                            @if($sortField === 'created_at')
                                <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                            @endif
                        </th>
                        <th style="cursor: pointer;" wire:click="sortBy('updated_at')">
                            Ostatnia modyfikacja
                            @if($sortField === 'updated_at')
                                <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                            @endif
                        </th>
                        <th style="cursor: pointer;" wire:click="sortBy('created_by')">
                            Stworzył
                            @if($sortField === 'created_by')
                                <i class="bi bi-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}"></i>
                            @endif
                        </th>
                        <th>Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tasks as $task)
                        <tr wire:key="task-{{ $task->id }}">
                            <td>
                                <strong>{{ $task->name }}</strong>
                                @if($task->description)
                                    <br><small class="text-muted">{{ Str::limit($task->description, 50) }}</small>
                                @endif
                            </td>
                            <td>
                                @if($task->project)
                                    <a href="{{ $isMineView ? route('mine.projects.show', $task->project) : route('projects.show', $task->project) }}" class="text-decoration-none">
                                        {{ $task->project->name }}
                                    </a>
                                @else
                                    <span class="text-muted fst-italic">Brak projektu</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $badgeVariant = match($task->status) {
                                        \App\Enums\TaskStatus::PENDING => 'warning',
                                        \App\Enums\TaskStatus::IN_PROGRESS => 'info',
                                        \App\Enums\TaskStatus::COMPLETED => 'success',
                                        \App\Enums\TaskStatus::CANCELLED => 'danger',
                                    };
                                @endphp
                                <x-ui.badge variant="{{ $badgeVariant }}">{{ $task->status->label() }}</x-ui.badge>
                            </td>
                            <td>
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
                            </td>
                            <td>
                                @if($task->assignedTo)
                                    {{ $task->assignedTo->name }}
                                @else
                                    <span class="text-muted">Nie przypisane</span>
                                @endif
                            </td>
                            <td>
                                @php
                                    $createdAt = \Carbon\Carbon::parse($task->created_at);
                                @endphp
                                <div>
                                    <span class="fw-semibold">{{ $createdAt->format('d.m.Y') }}</span>
                                    <br>
                                    <small class="text-muted">{{ $createdAt->format('H:i') }}</small>
                                </div>
                            </td>
                            <td>
                                @php
                                    $updatedAt = \Carbon\Carbon::parse($task->updated_at);
                                @endphp
                                <div>
                                    <span class="fw-semibold">{{ $updatedAt->format('d.m.Y') }}</span>
                                    <br>
                                    <small class="text-muted">{{ $updatedAt->format('H:i') }}</small>
                                </div>
                            </td>
                            <td>
                                @if($task->createdBy)
                                    {{ $task->createdBy->name }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
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
