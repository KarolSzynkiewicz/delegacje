<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Naprawa #{{ $vehicleRepair->id }} – {{ $vehicleRepair->vehicle->registration_number }}">
            <x-slot name="left">
                <x-ui.button variant="ghost" href="{{ route('vehicle-repairs.index') }}" action="back">
                    Powrót
                </x-ui.button>
            </x-slot>
            <x-slot name="right">
                <div class="d-flex gap-2">
                    <x-ui.button variant="ghost" href="{{ route('vehicle-repairs.edit', $vehicleRepair) }}" action="edit">
                        Edytuj
                    </x-ui.button>
                    @if(!$vehicleRepair->isCompleted())
                        <x-ui.button variant="success" href="{{ route('vehicle-repairs.complete-form', $vehicleRepair) }}">
                            <i class="bi bi-check-circle me-1"></i> Zakończ naprawę
                        </x-ui.button>
                    @endif
                    <form method="POST" action="{{ route('vehicle-repairs.destroy', $vehicleRepair) }}" class="d-inline"
                          onsubmit="return confirm('Usunąć tę naprawę? Powiązany koszt księgowy również zostanie usunięty.')">
                        @csrf
                        @method('DELETE')
                        <x-ui.button variant="danger" type="submit" action="delete">Usuń</x-ui.button>
                    </form>
                </div>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row">
        <div class="col-md-8 offset-md-2">

            {{-- Status banner --}}
            <div class="alert mb-4
                @if($vehicleRepair->status === 'completed') alert-success
                @elseif($vehicleRepair->status === 'in_progress') alert-warning
                @else alert-info @endif"
                style="background: rgba(0,0,0,0.1); border-color: var(--glass-border); color: var(--text-main);"
            >
                <x-ui.badge variant="{{ $vehicleRepair->status_badge_variant }}">
                    {{ $vehicleRepair->status_label }}
                </x-ui.badge>
                @if($vehicleRepair->status === 'in_progress')
                    <span class="ms-2">Pojazd przebywa w warsztacie.</span>
                @elseif($vehicleRepair->status === 'completed')
                    <span class="ms-2">Naprawa zakończona {{ $vehicleRepair->end_date->format('Y-m-d') }}.</span>
                @endif
            </div>

            <x-ui.card label="Szczegóły naprawy">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6>Pojazd</h6>
                        <a href="{{ route('vehicles.show', $vehicleRepair->vehicle) }}" class="text-decoration-none">
                            <strong>{{ $vehicleRepair->vehicle->registration_number }}</strong>
                        </a>
                        @if($vehicleRepair->vehicle->brand)
                            <br><small class="text-muted">{{ $vehicleRepair->vehicle->brand }} {{ $vehicleRepair->vehicle->model }}</small>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <h6>Typ akcji serwisowej</h6>
                        <x-ui.badge variant="{{ $vehicleRepair->action_type->badgeVariant() }}">
                            {{ $vehicleRepair->action_type->label() }}
                        </x-ui.badge>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6>Data oddania</h6>
                        <p>{{ $vehicleRepair->start_date->format('Y-m-d') }}</p>
                    </div>
                    <div class="col-md-6">
                        <h6>Data odbioru</h6>
                        <p>{{ $vehicleRepair->end_date ? $vehicleRepair->end_date->format('Y-m-d') : '–' }}</p>
                    </div>
                </div>

                @if($vehicleRepair->end_date)
                    <div class="mb-3">
                        <h6>Czas naprawy</h6>
                        <p>{{ $vehicleRepair->start_date->diffInDays($vehicleRepair->end_date) }} dni</p>
                    </div>
                @endif

                <div class="row mb-3">
                    <div class="col-md-6">
                        <h6>Koszt</h6>
                        <p>
                            @if($vehicleRepair->price)
                                <strong>{{ number_format($vehicleRepair->price, 2) }} {{ $vehicleRepair->currency }}</strong>
                            @else
                                <span class="text-muted">Nieznany</span>
                            @endif
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h6>Poprzedni stan techniczny</h6>
                        @php
                            $prevCond = \App\Enums\VehicleCondition::tryFrom($vehicleRepair->previous_technical_condition ?? '');
                        @endphp
                        <p>{{ $prevCond?->label() ?? ($vehicleRepair->previous_technical_condition ?? '–') }}</p>
                    </div>
                </div>

                {{-- Workshop --}}
                @if($vehicleRepair->location)
                    <div class="mb-3">
                        <h6>Warsztat</h6>
                        <p>
                            <strong>{{ $vehicleRepair->location->name }}</strong>
                            @if($vehicleRepair->location->address)
                                <br>{{ $vehicleRepair->location->address }}
                                @if($vehicleRepair->location->city), {{ $vehicleRepair->location->city }}@endif
                            @endif
                            @if($vehicleRepair->location->phone)
                                <br><small class="text-muted"><i class="bi bi-telephone me-1"></i>{{ $vehicleRepair->location->phone }}</small>
                            @endif
                        </p>
                    </div>
                @endif

                @if($vehicleRepair->notes)
                    <div class="mb-3">
                        <h6>Notatki</h6>
                        <p>{{ $vehicleRepair->notes }}</p>
                    </div>
                @endif
            </x-ui.card>

            {{-- Linked cost entry --}}
            @if($vehicleRepair->fixedCostEntry)
                <x-ui.card class="mt-4" label="Powiązany koszt księgowy">
                    <div class="row">
                        <div class="col-md-4">
                            <h6>Tytuł</h6>
                            <p>{{ $vehicleRepair->fixedCostEntry->name }}</p>
                        </div>
                        <div class="col-md-4">
                            <h6>Kwota</h6>
                            <p><strong>{{ number_format($vehicleRepair->fixedCostEntry->amount, 2) }} {{ $vehicleRepair->fixedCostEntry->currency }}</strong></p>
                        </div>
                        <div class="col-md-4">
                            <h6>Data księgowania</h6>
                            <p>{{ $vehicleRepair->fixedCostEntry->accounting_date->format('Y-m-d') }}</p>
                        </div>
                    </div>
                    <div class="mt-2">
                        <x-ui.button variant="ghost" href="{{ route('fixed-cost-entries.show', $vehicleRepair->fixedCostEntry) }}" class="btn-sm">
                            <i class="bi bi-box-arrow-up-right me-1"></i> Otwórz koszt
                        </x-ui.button>
                    </div>
                </x-ui.card>
            @elseif(!$vehicleRepair->isCompleted())
                <div class="alert mt-4" style="background: rgba(245,158,11,0.1); border-color: rgba(245,158,11,0.3); color: var(--text-main);">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Koszt serwisu zostanie automatycznie zaksięgowany po zakończeniu naprawy.
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
