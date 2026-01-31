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
                <select 
                    wire:model.live="status" 
                    class="form-control form-select-sm">
                    <option value="">Wszystkie statusy</option>
                    @foreach($statuses as $statusValue => $statusLabel)
                        <option value="{{ $statusValue }}">{{ $statusLabel }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </x-ui.card>

    @if($tasks->count() > 0)
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Zadanie</th>
                        <th>Projekt</th>
                        <th>Status</th>
                        <th>Termin</th>
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
                                <a href="{{ $isMineView ? route('mine.projects.show', $task->project) : route('projects.show', $task->project) }}" class="text-decoration-none">
                                    {{ $task->project->name }}
                                </a>
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
                                    {{ $task->due_date->format('d.m.Y') }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <x-tasks-actions 
                                    :task="$task" 
                                    :project="$task->project" 
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
