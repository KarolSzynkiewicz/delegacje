<x-app-layout>
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Edytuj Projekt">
                <form method="POST" action="{{ route('projects.update', $project) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <x-ui.input 
                            type="text" 
                            name="name" 
                            label="Nazwa Projektu"
                            value="{{ old('name', $project->name) }}"
                            required="true"
                        />
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="text" 
                            name="client_name" 
                            label="Klient"
                            value="{{ old('client_name', $project->client_name) }}"
                        />
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="textarea" 
                            name="description" 
                            label="Opis"
                            value="{{ old('description', $project->description) }}"
                            rows="4"
                        />
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="select" 
                            name="status" 
                            label="Status"
                            required="true"
                        >
                            <option value="active" {{ $project->status == 'active' ? 'selected' : '' }}>Aktywny</option>
                            <option value="on_hold" {{ $project->status == 'on_hold' ? 'selected' : '' }}>Wstrzymany</option>
                            <option value="completed" {{ $project->status == 'completed' ? 'selected' : '' }}>Zakończony</option>
                            <option value="cancelled" {{ $project->status == 'cancelled' ? 'selected' : '' }}>Anulowany</option>
                        </x-ui.input>
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="number" 
                            name="budget" 
                            label="Budżet (PLN)"
                            value="{{ old('budget', $project->budget) }}"
                            step="0.01"
                        />
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <x-ui.button variant="primary" type="submit">
                            <i class="bi bi-save me-1"></i> Aktualizuj
                        </x-ui.button>
                        <x-ui.button variant="ghost" href="{{ route('projects.index') }}">Anuluj</x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
