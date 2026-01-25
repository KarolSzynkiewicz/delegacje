<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Edytuj Rotację: {{ $employee->full_name }}">
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
            <x-ui.card label="Edytuj Rotację">
                <x-ui.errors />

                <form method="POST" action="{{ route('employees.rotations.update', [$employee, $rotation]) }}">
                    @csrf
                    @method('PUT')

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
                                label="Data rozpoczęcia"
                                value="{{ old('start_date', $rotation->start_date->format('Y-m-d')) }}"
                                required="true"
                            />
                        </div>
                        <div class="col-md-6">
                            <x-ui.input 
                                type="date" 
                                name="end_date" 
                                label="Data zakończenia"
                                value="{{ old('end_date', $rotation->end_date->format('Y-m-d')) }}"
                                required="true"
                            />
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Aktualny status</label>
                        <div class="border rounded p-3 bg-light">
                            @php
                                $currentStatus = $rotation->status;
                                $statusLabels = [
                                    'scheduled' => ['Zaplanowana', 'info'],
                                    'active' => ['Aktywna', 'success'],
                                    'completed' => ['Zakończona', 'secondary'],
                                ];
                                $badgeVariant = $statusLabels[$currentStatus][1] ?? 'secondary';
                                $statusLabel = $statusLabels[$currentStatus][0] ?? 'Nieznany';
                            @endphp
                            <x-ui.badge variant="{{ $badgeVariant }}">
                                {{ $statusLabel }}
                            </x-ui.badge>
                            <p class="small text-muted mt-2 mb-0">
                                Status jest automatycznie obliczany na podstawie dat.
                            </p>
                        </div>
                    </div>

                    <div class="mb-4">
                        <x-ui.input 
                            type="textarea" 
                            name="notes" 
                            label="Notatki"
                            value="{{ old('notes', $rotation->notes) }}"
                            rows="4"
                        />
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <x-ui.button variant="primary" type="submit" action="save">
                            Zaktualizuj Rotację
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
