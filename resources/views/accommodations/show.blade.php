<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Akomodacja: {{ $accommodation->name }}">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('accommodations.index') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
            <x-slot name="right">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('accommodations.edit', $accommodation) }}"
                    routeName="accommodations.edit"
                    action="edit"
                >
                    Edytuj
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row">
        <div class="col-md-8 offset-md-2">
            <x-ui.card label="Szczegóły Mieszkania">
                @if($accommodation->image_path)
                    <div class="mb-4 text-center">
                        <img src="{{ $accommodation->image_url }}" alt="{{ $accommodation->name }}" class="img-fluid rounded">
                    </div>
                @endif

                <div class="row mb-3">
                    <div class="col-md-6">
                        <h5>Nazwa</h5>
                        <p>{{ $accommodation->name }}</p>
                    </div>
                    <div class="col-md-6">
                        <h5>Pojemność</h5>
                        <p>{{ $accommodation->capacity }} osób</p>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <h5>Adres</h5>
                        <p>{{ $accommodation->address }}</p>
                    </div>
                    <div class="col-md-6">
                        <h5>Miasto</h5>
                        <p>{{ $accommodation->city ?? '-' }}</p>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <h5>Kod Pocztowy</h5>
                        <p>{{ $accommodation->postal_code ?? '-' }}</p>
                    </div>
                </div>

                @if ($accommodation->description)
                    <div class="mb-3">
                        <h5>Opis</h5>
                        <p>{{ $accommodation->description }}</p>
                    </div>
                @endif

                <div class="d-flex gap-2 mt-4 pt-3 border-top">
                    <x-ui.button variant="primary" href="{{ route('accommodations.edit', $accommodation) }}">Edytuj</x-ui.button>
                    <x-ui.button variant="ghost" href="{{ route('accommodations.index') }}">Wróć do Listy</x-ui.button>
                </div>
            </x-ui.card>

            <x-ui.card label="Przypisania do mieszkania" class="mt-4">
                @if($assignments->count() > 0)
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Pracownik</th>
                                    <th>Okres</th>
                                    <th>Status</th>
                                    <th class="text-end">Akcje</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($assignments as $assignment)
                                    <tr>
                                        <td>
                                            <a href="{{ route('employees.show', $assignment->employee) }}" class="text-primary text-decoration-none">
                                                {{ $assignment->employee->full_name }}
                                            </a>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ $assignment->start_date->format('Y-m-d') }}
                                                @if($assignment->end_date)
                                                    - {{ $assignment->end_date->format('Y-m-d') }}
                                                @else
                                                    - ...
                                                @endif
                                            </small>
                                        </td>
                                        <td>
                                            @php
                                                $status = $assignment->status ?? \App\Enums\AssignmentStatus::ACTIVE;
                                                $statusValue = $status instanceof \App\Enums\AssignmentStatus ? $status->value : $status;
                                                $statusLabel = $status instanceof \App\Enums\AssignmentStatus ? $status->label() : ucfirst($status);
                                                $badgeVariant = match($statusValue) {
                                                    'active' => 'success',
                                                    'completed' => 'info',
                                                    'cancelled' => 'danger',
                                                    'in_transit' => 'warning',
                                                    'at_base' => 'info',
                                                    default => 'info'
                                                };
                                            @endphp
                                            <x-ui.badge variant="{{ $badgeVariant }}">{{ $statusLabel }}</x-ui.badge>
                                        </td>
                                        <td class="text-end">
                                            <x-ui.button variant="ghost" href="{{ route('accommodation-assignments.show', $assignment) }}">Szczegóły</x-ui.button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <x-ui.empty-state 
                        icon="inbox"
                        message="Brak przypisań do tego mieszkania."
                    />
                @endif
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
