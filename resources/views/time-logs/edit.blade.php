<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Edytuj Wpis Ewidencji Godzin">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('time-logs.show', $timeLog) }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <x-ui.card label="Edytuj Wpis Ewidencji Godzin">
                <x-ui.errors />

                <form method="POST" action="{{ route('time-logs.update', $timeLog) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Przypisanie do projektu</label>
                        <input type="text" value="{{ $timeLog->projectAssignment->employee->full_name }} - {{ $timeLog->projectAssignment->project->name }}" disabled
                            class="form-control bg-light">
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="date" 
                            name="work_date" 
                            label="Data pracy"
                            value="{{ old('work_date', $timeLog->start_time->format('Y-m-d')) }}"
                            required="true"
                        />
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="number" 
                            name="hours_worked" 
                            label="Liczba godzin"
                            value="{{ old('hours_worked', $timeLog->hours_worked) }}"
                            step="0.25"
                            min="0"
                            max="24"
                            required="true"
                        />
                        <small class="form-text text-muted">Wprowadź liczbę godzin (0-24, możesz użyć 0.25 dla 15 minut)</small>
                    </div>

                    <div class="mb-4">
                        <x-ui.input 
                            type="textarea" 
                            name="notes" 
                            label="Notatki"
                            value="{{ old('notes', $timeLog->notes) }}"
                            rows="3"
                        />
                    </div>

                    <div class="d-flex justify-content-end align-items-center gap-2">
                        <x-ui.button 
                            variant="ghost" 
                            href="{{ route('time-logs.show', $timeLog) }}"
                            action="cancel"
                        >
                            Anuluj
                        </x-ui.button>
                        <x-ui.button 
                            variant="primary" 
                            type="submit"
                            action="save"
                        >
                            Zapisz
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
