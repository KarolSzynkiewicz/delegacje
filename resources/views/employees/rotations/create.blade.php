<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Dodaj Rotację: {{ $employee->full_name }}">
            <x-slot name="left">
                <x-ui.button
                    variant="ghost"
                    href="{{ route('employees.rotations.index', $employee) }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Dodaj Rotację">
                <x-ui.errors />

                <form method="POST" action="{{ route('employees.rotations.store', $employee) }}">
                    @csrf

                    <div class="mb-3">
                        <x-ui.input
                            type="text"
                            name="employee_name"
                            label="Pracownik"
                            value="{{ $employee->full_name }}"
                            disabled="true"
                        />
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <x-ui.input
                                type="date"
                                name="start_date"
                                id="start_date"
                                label="Data rozpoczęcia"
                                value="{{ old('start_date') }}"
                                required="true"
                            />
                        </div>
                        <div class="col-md-6">
                            <x-ui.input
                                type="date"
                                name="end_date"
                                id="end_date"
                                label="Data zakończenia"
                                value="{{ old('end_date') }}"
                                required="true"
                            />
                        </div>
                    </div>

                    <x-ui.alert variant="info" title="Uwaga" class="mb-4">
                        Status rotacji jest automatycznie określany na podstawie dat:
                        <ul class="mb-0 mt-2">
                            <li><strong>Zaplanowana</strong> — jeśli data rozpoczęcia jest w przyszłości</li>
                            <li><strong>Aktywna</strong> — jeśli trwa obecnie (dziś między datą rozpoczęcia a zakończenia)</li>
                            <li><strong>Zakończona</strong> — jeśli data zakończenia jest w przeszłości</li>
                        </ul>
                    </x-ui.alert>

                    <div class="mb-4">
                        <x-ui.input
                            type="textarea"
                            name="notes"
                            id="notes"
                            label="Notatki"
                            value="{{ old('notes') }}"
                            rows="4"
                        />
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <x-ui.button variant="primary" type="submit" action="save">
                            Zapisz
                        </x-ui.button>
                        <x-ui.button variant="ghost" href="{{ route('employees.rotations.index', $employee) }}" action="cancel">
                            Anuluj
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
