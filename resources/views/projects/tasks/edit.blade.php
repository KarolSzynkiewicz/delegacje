<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Edytuj Zadanie: {{ $task->name }}">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('projects.tasks.show', [$project, $task]) }}"
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

                <form action="{{ route('projects.tasks.update', [$project, $task]) }}" method="POST">
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
                            value="{{ old('description', $task->description) }}"
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
                        <div class="col-md-6">
                            <x-ui.input 
                                type="date" 
                                name="due_date" 
                                label="Termin wykonania"
                                value="{{ old('due_date', $task->due_date ? $task->due_date->format('Y-m-d') : '') }}"
                            />
                        </div>
                    </div>

                    <div class="mb-3">
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

                    <div class="d-flex justify-content-between align-items-center">
                        <x-ui.button 
                            variant="ghost" 
                            href="{{ route('projects.tasks.show', [$project, $task]) }}"
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
