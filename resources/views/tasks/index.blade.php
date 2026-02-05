<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 text-dark mb-0">Zadania</h2>
        </div>
    </x-slot>

    <!-- Formularz inline do dodawania zadań -->
    <x-ui.card class="mb-4">
        <h5 class="card-title mb-3">Dodaj nowe zadanie</h5>
        <form action="{{ route('tasks.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-md-4">
                    <x-ui.input 
                        type="text" 
                        name="name" 
                        label="Nazwa zadania"
                        required
                    />
                </div>
                <div class="col-md-3">
                    <x-ui.input 
                        type="select" 
                        name="project_id" 
                        label="Projekt (opcjonalnie)"
                    >
                        <option value="">Brak projektu</option>
                        @foreach(\App\Models\Project::orderBy('name')->get() as $project)
                            <option value="{{ $project->id }}" {{ old('project_id') == $project->id ? 'selected' : '' }}>
                                {{ $project->name }}
                            </option>
                        @endforeach
                    </x-ui.input>
                </div>
                <div class="col-md-3">
                    <x-ui.input 
                        type="select" 
                        name="assigned_to" 
                        label="Przypisz do"
                    >
                        <option value="">Brak przypisania</option>
                        @foreach(\App\Models\User::orderBy('name')->get() as $user)
                            <option value="{{ $user->id }}" {{ old('assigned_to') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </x-ui.input>
                </div>
                <div class="col-md-2">
                    <x-ui.input 
                        type="date" 
                        name="due_date" 
                        label="Termin"
                        value="{{ old('due_date') }}"
                    />
                </div>
                <div class="col-md-12">
                    <x-ui.input 
                        type="textarea" 
                        name="description" 
                        label="Opis"
                        rows="2"
                        value="{{ old('description') }}"
                    />
                </div>
                <div class="col-md-12">
                    <x-ui.button type="submit" variant="primary">
                        <i class="bi bi-plus-circle me-1"></i> Dodaj Zadanie
                    </x-ui.button>
                </div>
            </div>
        </form>
        <x-ui.errors />
    </x-ui.card>

    <livewire:tasks-table :assignedToUserId="auth()->id()" />
</x-app-layout>
