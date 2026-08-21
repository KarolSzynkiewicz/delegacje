<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Edytuj Zadanie: {{ $task->name }}">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('tasks.show', $task) }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Edytuj Zadanie">
                <x-ui.errors />

                <form action="{{ route('tasks.update', $task) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <x-ui.input 
                            type="text" 
                            name="name" 
                            label="Nazwa zadania"
                            value="{{ old('name', $task->name) }}"
                            required
                        />
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="textarea" 
                            name="description" 
                            label="Opis"
                            value="{{ old('description', $task->plainDescription()) }}"
                            rows="4"
                        />
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <x-ui.input 
                                type="select" 
                                name="assigned_to" 
                                label="Przypisz do"
                            >
                                <option value="">Brak przypisania</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" {{ old('assigned_to', $task->assigned_to) == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </x-ui.input>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4 mb-3 mb-md-0">
                            <x-ui.input 
                                type="date" 
                                name="due_date" 
                                label="Termin wykonania"
                                value="{{ old('due_date', $task->due_date ? $task->due_date->format('Y-m-d') : '') }}"
                            />
                        </div>
                        <div class="col-md-4 mb-3 mb-md-0">
                            <x-ui.input 
                                type="select" 
                                name="priority" 
                                label="Priorytet"
                            >
                                <option value="">Brak priorytetu</option>
                                <option value="1" {{ old('priority', $task->priority) == '1' ? 'selected' : '' }}>1 - Najniższy</option>
                                <option value="2" {{ old('priority', $task->priority) == '2' ? 'selected' : '' }}>2 - Niski</option>
                                <option value="3" {{ old('priority', $task->priority) == '3' ? 'selected' : '' }}>3 - Średni</option>
                                <option value="4" {{ old('priority', $task->priority) == '4' ? 'selected' : '' }}>4 - Wysoki</option>
                                <option value="5" {{ old('priority', $task->priority) == '5' ? 'selected' : '' }}>5 - Najwyższy</option>
                            </x-ui.input>
                        </div>
                        <div class="col-md-4">
                            <x-ui.input 
                                type="select" 
                                name="status" 
                                label="Status"
                            >
                                <option value="pending" {{ old('status', $task->status->value) === 'pending' ? 'selected' : '' }}>Oczekujące</option>
                                <option value="in_progress" {{ old('status', $task->status->value) === 'in_progress' ? 'selected' : '' }}>W trakcie</option>
                                <option value="completed" {{ old('status', $task->status->value) === 'completed' ? 'selected' : '' }}>Zakończone</option>
                                <option value="cancelled" {{ old('status', $task->status->value) === 'cancelled' ? 'selected' : '' }}>Anulowane</option>
                            </x-ui.input>
                        </div>
                    </div>

                    <div class="mb-3">
                        <x-ui.input
                            type="select"
                            name="sprint_id"
                            label="Sprint"
                        >
                            <option value="">Poza sprintem</option>
                            @foreach(\App\Models\Sprint::query()->orderByDesc('start_date')->get() as $sprintOption)
                                <option value="{{ $sprintOption->id }}" {{ old('sprint_id', $task->sprint_id) == $sprintOption->id ? 'selected' : '' }}>
                                    {{ $sprintOption->label() }}
                                </option>
                            @endforeach
                        </x-ui.input>
                    </div>
                    
                    <div class="mb-3">
                        <x-ui.input 
                            type="text" 
                            name="category" 
                            label="Kategoria (opcjonalnie)"
                            value="{{ old('category', $task->category) }}"
                            placeholder="np. Bug, Feature, Dokumentacja..."
                        />
                    </div>

                    @if($task->attachments->count() > 0)
                        <div class="mb-3">
                            <label class="form-label">Obecne załączniki</label>
                            <x-attachment-list :attachments="$task->attachments" />
                        </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label">Dodaj załączniki</label>
                        <input type="file" name="attachments[]" class="form-control" multiple accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.doc,.docx,.xls,.xlsx,.txt,.zip,application/pdf,image/*">
                        <small class="text-muted d-block mt-1">Do 15 plików, każdy max. 15 MB.</small>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <x-ui.button 
                            variant="ghost" 
                            href="{{ route('tasks.show', $task) }}"
                            action="cancel"
                        >
                            Anuluj
                        </x-ui.button>
                        <x-ui.button 
                            variant="primary" 
                            type="submit"
                            action="save"
                        >
                            Zaktualizuj zadanie
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
