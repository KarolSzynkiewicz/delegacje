<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Kierownik — {{ $project->name }}">
            <x-slot name="left">
                <x-ui.button variant="ghost" href="{{ route('projects.show', $project) }}" action="back">Powrót</x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Ustaw kierownika">
                <p class="small text-muted mb-3">
                    Nowy wpis zamyka poprzedniego kierownika dniem wcześniej, jeśli okresy by się nakładały.
                    Jedna osoba na raz na projekt.
                </p>
                <x-ui.errors />

                <form method="POST" action="{{ route('projects.site-leads.store', $project) }}">
                    @csrf

                    <div class="mb-3">
                        <x-ui.input type="select" name="employee_id" label="Pracownik" required="true">
                            <option value="">Wybierz pracownika</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}" {{ old('employee_id') == $emp->id ? 'selected' : '' }}>
                                    {{ $emp->full_name }}
                                </option>
                            @endforeach
                        </x-ui.input>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <x-ui.input type="date" name="start_date" label="Data od" value="{{ old('start_date', now()->toDateString()) }}" required="true" />
                        </div>
                        <div class="col-md-6">
                            <x-ui.input type="date" name="end_date" label="Data do (opcjonalnie)" value="{{ old('end_date') }}" />
                        </div>
                    </div>

                    <div class="mb-4">
                        <x-ui.input type="textarea" name="notes" label="Notatki" value="{{ old('notes') }}" rows="3" />
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <x-ui.button variant="primary" type="submit" action="save">Zapisz</x-ui.button>
                        <x-ui.button variant="ghost" href="{{ route('projects.show', $project) }}" action="cancel">Anuluj</x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
