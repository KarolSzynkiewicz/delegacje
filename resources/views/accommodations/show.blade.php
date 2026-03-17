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

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <x-ui.card label="Informacje podstawowe">
                @if($accommodation->image_path)
                    <div class="mb-4 text-center">
                        <img src="{{ $accommodation->image_url }}" alt="{{ $accommodation->name }}" class="img-fluid rounded">
                    </div>
                @endif

                <x-ui.detail-list>
                    <x-ui.detail-item label="Nazwa">{{ $accommodation->name }}</x-ui.detail-item>
                    <x-ui.detail-item label="Adres">{{ $accommodation->address }}</x-ui.detail-item>
                    @if($accommodation->city)
                    <x-ui.detail-item label="Miasto">{{ $accommodation->city }}</x-ui.detail-item>
                    @endif
                    @if($accommodation->postal_code)
                    <x-ui.detail-item label="Kod pocztowy">{{ $accommodation->postal_code }}</x-ui.detail-item>
                    @endif
                    @if($accommodation->country)
                    <x-ui.detail-item label="Kraj">{{ $accommodation->country->labelWithFlag() }}</x-ui.detail-item>
                    @endif
                    @if($accommodation->hasCoordinates())
                    <x-ui.detail-item label="Współrzędne geograficzne">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span>
                                <i class="bi bi-geo-alt-fill text-success"></i>
                                {{ number_format((float)$accommodation->latitude, 8) }}, {{ number_format((float)$accommodation->longitude, 8) }}
                            </span>
                            <a 
                                href="https://www.openstreetmap.org/?mlat={{ floatval($accommodation->latitude) }}&mlon={{ floatval($accommodation->longitude) }}&zoom=15" 
                                target="_blank"
                                class="btn btn-sm btn-outline-secondary"
                                title="Otwórz na mapie"
                            >
                                <i class="bi bi-map"></i> Otwórz na mapie
                            </a>
                        </div>
                    </x-ui.detail-item>
                    @endif
                    <x-ui.detail-item label="Pojemność">{{ $accommodation->capacity }} osób</x-ui.detail-item>
                    <x-ui.detail-item label="Typ">
                        @if($accommodation->type === 'własny')
                            <x-ui.badge variant="success">Własny</x-ui.badge>
                        @else
                            <x-ui.badge variant="info">Wynajmowany</x-ui.badge>
                        @endif
                    </x-ui.detail-item>
                    @if($accommodation->type === 'wynajmowany')
                        @if($accommodation->lease_start_date)
                        <x-ui.detail-item label="Okres najmu - od">{{ $accommodation->lease_start_date->format('d.m.Y') }}</x-ui.detail-item>
                        @endif
                        @if($accommodation->lease_end_date)
                        <x-ui.detail-item label="Okres najmu - do">{{ $accommodation->lease_end_date->format('d.m.Y') }}</x-ui.detail-item>
                        @endif
                    @endif
                    @if($accommodation->description)
                    <x-ui.detail-item label="Opis" fullWidth>{{ $accommodation->description }}</x-ui.detail-item>
                    @endif
                </x-ui.detail-list>
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
                                                <x-employee-cell :employee="$assignment->employee"  />
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
