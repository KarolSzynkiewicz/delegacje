<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Zadania">
            <x-slot name="right">
                <x-tasks-default-view-button view="cards" />
                <a href="{{ route('tasks.grid') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-grid-3x3-gap me-1"></i>Widok siatki
                </a>
                <x-ui.button 
                    variant="primary" 
                    x-data=""
                    x-on:click.prevent="$dispatch('open-modal', 'add-task-modal')"
                >
                    <i class="bi bi-plus-circle me-1"></i> Dodaj Zadanie
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <!-- Komunikaty sukcesu/błędu -->
    @if(session('success'))
        <x-ui.alert variant="success" title="Sukces" dismissible class="mb-3">
            {{ session('success') }}
        </x-ui.alert>
    @endif

    @if(session('error'))
        <x-ui.alert variant="danger" title="Błąd" dismissible class="mb-3">
            {{ session('error') }}
        </x-ui.alert>
    @endif

    <!-- Modal do dodawania zadań -->
    <x-modal name="add-task-modal" :show="$errors->any() || session('error')" focusable>
        <x-ui.card label="Dodaj nowe zadanie">
            <form action="{{ route('tasks.store') }}" method="POST" id="add-task-form" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <x-ui.input 
                        type="text" 
                        name="name" 
                        label="Nazwa zadania"
                        required
                        value="{{ old('name') }}"
                    />
                </div>
                
                    <div class="row mb-3">
                    <div class="col-md-4">
                        <x-ui.input
                            type="select"
                            name="sprint_id"
                            label="Sprint"
                        >
                            <option value="">Poza sprintem</option>
                            @foreach(\App\Models\Sprint::query()->orderByDesc('start_date')->get() as $sprintOption)
                                <option value="{{ $sprintOption->id }}" {{ old('sprint_id') == $sprintOption->id ? 'selected' : '' }}>
                                    {{ $sprintOption->label() }}
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
                    
                    <div class="col-md-3">
                        <x-ui.input 
                            type="date" 
                            name="due_date" 
                            label="Termin"
                            value="{{ old('due_date') }}"
                        />
                    </div>
                    
                    <div class="col-md-3">
                        <x-ui.input 
                            type="select" 
                            name="priority" 
                            label="Priorytet"
                        >
                            <option value="">Brak priorytetu</option>
                            <option value="1" {{ old('priority') == '1' ? 'selected' : '' }}>1 - Najniższy</option>
                            <option value="2" {{ old('priority') == '2' ? 'selected' : '' }}>2 - Niski</option>
                            <option value="3" {{ old('priority') == '3' ? 'selected' : '' }}>3 - Średni</option>
                            <option value="4" {{ old('priority') == '4' ? 'selected' : '' }}>4 - Wysoki</option>
                            <option value="5" {{ old('priority') == '5' ? 'selected' : '' }}>5 - Najwyższy</option>
                        </x-ui.input>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-12">
                        <x-ui.input 
                            type="text" 
                            name="category" 
                            label="Kategoria (opcjonalnie)"
                            value="{{ old('category') }}"
                            placeholder="np. Bug, Feature, Dokumentacja..."
                        />
                    </div>
                </div>
                
                <div class="mb-3">
                    <x-ui.input 
                        type="textarea" 
                        name="description" 
                        label="Opis"
                        rows="3"
                        value="{{ old('description') }}"
                    />
                </div>

                <div class="mb-3">
                    <label class="form-label">Załączniki (opcjonalnie)</label>
                    <input type="file" name="attachments[]" class="form-control" multiple accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.doc,.docx,.xls,.xlsx,.txt,.zip,application/pdf,image/*">
                    <small class="text-muted d-block mt-1">Do 15 plików, każdy max. 15 MB.</small>
                </div>
                
                <x-ui.errors />
                
                <div class="d-flex justify-content-end gap-2 mt-4">
                    <x-ui.button 
                        type="button" 
                        variant="ghost"
                        x-on:click="$dispatch('close-modal', 'add-task-modal')"
                    >
                        Anuluj
                    </x-ui.button>
                    <x-ui.button type="submit" variant="primary">
                        <i class="bi bi-plus-circle me-1"></i> Dodaj Zadanie
                    </x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </x-modal>

    <livewire:tasks-table />

    @push('scripts')
    <script>
        // Zamknij modal po sukcesie
        @if(session('task_created'))
            window.dispatchEvent(new CustomEvent('close-modal', { detail: 'add-task-modal' }));
        @endif

        // Przywróć pozycję scrollowania po akcji na zadaniu (używając URL hash)
        function scrollToTask() {
            if (window.location.hash) {
                const taskId = window.location.hash.substring(1); // Usuń #
                // Poczekaj na Livewire, żeby elementy były już zrenderowane
                setTimeout(function() {
                    const element = document.getElementById(taskId);
                    if (element) {
                        element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        // Usuń hash z URL po przewinięciu
                        history.replaceState(null, null, ' ');
                    }
                }, 300);
            }
        }

        // Wywołaj po załadowaniu strony
        document.addEventListener('DOMContentLoaded', scrollToTask);
        
        // Wywołaj również po aktualizacji Livewire (jeśli używa Livewire)
        document.addEventListener('livewire:load', scrollToTask);
        document.addEventListener('livewire:update', scrollToTask);
    </script>
    @endpush
</x-app-layout>
