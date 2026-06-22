<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Edytuj Przypisanie do Spółki">
            <x-slot name="left">
                <x-ui.button variant="ghost" href="{{ route('company-assignments.index') }}" action="back">Powrót</x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Edytuj przypisanie">
                <x-ui.errors />

                <form method="POST" action="{{ route('company-assignments.update', $assignment) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <x-ui.input type="select" name="employee_id" label="Pracownik" required="true">
                            <option value="">Wybierz pracownika</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}" {{ old('employee_id', $assignment->employee_id) == $emp->id ? 'selected' : '' }}>
                                    {{ $emp->full_name }} ({{ $emp->email }})
                                </option>
                            @endforeach
                        </x-ui.input>
                    </div>

                    <div class="mb-3">
                        <x-ui.input type="select" name="company_id" label="Spółka" required="true">
                            <option value="">Wybierz spółkę</option>
                            @foreach($companies as $comp)
                                <option value="{{ $comp->id }}" {{ old('company_id', $assignment->company_id) == $comp->id ? 'selected' : '' }}>
                                    {{ $comp->name }} (NIP: {{ $comp->nip }})
                                </option>
                            @endforeach
                        </x-ui.input>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <x-ui.input type="date" name="start_date" label="Data od" value="{{ old('start_date', $assignment->start_date->format('Y-m-d')) }}" required="true" />
                        </div>
                        <div class="col-md-6">
                            <x-ui.input type="date" name="end_date" label="Data do (opcjonalnie)" value="{{ old('end_date', $assignment->end_date ? $assignment->end_date->format('Y-m-d') : '') }}" />
                        </div>
                    </div>

                    <div class="mb-4">
                        <x-ui.input type="textarea" name="notes" label="Notatki" value="{{ old('notes', $assignment->notes) }}" rows="4" />
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <x-ui.button variant="primary" type="submit" action="save">Zapisz</x-ui.button>
                        <x-ui.button variant="ghost" href="{{ route('company-assignments.index') }}" action="cancel">Anuluj</x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
