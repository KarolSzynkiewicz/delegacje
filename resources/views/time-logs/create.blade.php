<x-app-layout>
    <x-slot name="header">
        <h2 class="fw-semibold fs-4 mb-0">
            Dodaj Wpis Ewidencji Godzin
        </h2>
    </x-slot>

    <div class="py-4">
        <div class="container-xxl">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <x-ui.card>
                        <x-ui.errors />

                        <form method="POST" action="{{ route('time-logs.store') }}">
                            @csrf

                            <div class="mb-3">
                                <x-ui.input 
                                    type="select" 
                                    name="project_assignment_id" 
                                    label="Przypisanie do projektu"
                                    required
                                >
                                    <option value="">Wybierz przypisanie</option>
                                    @foreach($assignments as $assignment)
                                        <option value="{{ $assignment->id }}" {{ old('project_assignment_id') == $assignment->id ? 'selected' : '' }}>
                                            {{ $assignment->employee->full_name }} - {{ $assignment->project->name }} 
                                            ({{ $assignment->start_date->format('Y-m-d') }} - {{ $assignment->end_date ? $assignment->end_date->format('Y-m-d') : '...' }})
                                        </option>
                                    @endforeach
                                </x-ui.input>
                            </div>

                            <div class="mb-3">
                                <x-ui.input 
                                    type="date" 
                                    name="work_date" 
                                    label="Data pracy"
                                    value="{{ old('work_date', date('Y-m-d')) }}"
                                    required
                                />
                            </div>

                            <div class="mb-3">
                                <x-ui.input 
                                    type="number" 
                                    name="hours_worked" 
                                    label="Liczba godzin"
                                    value="{{ old('hours_worked') }}"
                                    step="0.25"
                                    min="0"
                                    max="24"
                                    required
                                />
                                <small class="text-muted d-block mt-1">Wprowadź liczbę godzin (0-24, możesz użyć 0.25 dla 15 minut)</small>
                            </div>

                            <div class="mb-3">
                                <x-ui.input 
                                    type="textarea" 
                                    name="notes" 
                                    label="Notatki"
                                    value="{{ old('notes') }}"
                                    rows="3"
                                />
                            </div>

                            <div class="d-flex gap-2">
                                <x-ui.button variant="primary" type="submit">
                                    Zapisz
                                </x-ui.button>
                                <x-ui.button variant="ghost" href="{{ route('time-logs.index') }}">
                                    Anuluj
                                </x-ui.button>
                            </div>
                        </form>
                    </x-ui.card>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
