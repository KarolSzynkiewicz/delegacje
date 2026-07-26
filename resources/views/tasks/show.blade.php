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
                                <div class="text-break">{{ $task->plainDescription() }}</div>
                            </div>
                        @endif

                        @if($task->attachments->count() > 0)
                            <div class="mt-3">
                                <small class="text-muted d-block mb-1">
                                    <i class="bi bi-paperclip me-1"></i>Załączniki
                                </small>
                                <x-attachment-list :attachments="$task->attachments" />
                            </div>
                        @endif
                    </div>
                    
                    <!-- Prawa strona: Status, projekt, priorytet, kategoria, termin (szybka edycja jak na liście) -->
                    <div class="col-md-6">
                        <livewire:task-show-quick-edit :task="$task" wire:key="task-show-qe-{{ $task->id }}" />
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

            @if($task->procedureRun)
                <div class="mt-4">
                    <livewire:procedure-run-stepper :run="$task->procedureRun" wire:key="stepper-{{ $task->procedureRun->id }}" />
                </div>
            @endif

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
