<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Koszt Transportu">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('transport-costs.index') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
            <x-slot name="right">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('transport-costs.edit', $transportCost) }}"
                    routeName="transport-costs.edit"
                    action="edit"
                >
                    Edytuj
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <x-ui.card label="Informacje podstawowe">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <h6 class="text-muted small mb-1">Typ kosztu</h6>
                            <p class="fw-semibold">{{ ucfirst($transportCost->cost_type) }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted small mb-1">Kwota</h6>
                            <p class="fw-semibold fs-5">{{ number_format($transportCost->amount, 2) }} {{ $transportCost->currency }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted small mb-1">Data kosztu</h6>
                            <p class="fw-semibold">{{ $transportCost->cost_date->format('Y-m-d') }}</p>
                        </div>
                        @if($transportCost->logisticsEvent)
                        <div class="col-md-6">
                            <h6 class="text-muted small mb-1">Zdarzenie logistyczne</h6>
                            <a href="{{ route('return-trips.show', $transportCost->logisticsEvent) }}" class="text-decoration-none">
                                Zdarzenie #{{ $transportCost->logisticsEvent->id }}
                            </a>
                        </div>
                        @endif
                        @if($transportCost->vehicle)
                        <div class="col-md-6">
                            <h6 class="text-muted small mb-1">Pojazd</h6>
                            <p class="fw-semibold">{{ $transportCost->vehicle->registration_number }}</p>
                        </div>
                        @endif
                        @if($transportCost->transport)
                        <div class="col-md-6">
                            <h6 class="text-muted small mb-1">Transport</h6>
                            <p class="fw-semibold">{{ $transportCost->transport->mode->label() }}</p>
                        </div>
                        @endif
                        @if($transportCost->description)
                        <div class="col-md-6">
                            <h6 class="text-muted small mb-1">Opis</h6>
                            <p class="fw-semibold">{{ $transportCost->description }}</p>
                        </div>
                        @endif
                        @if($transportCost->receipt_number)
                        <div class="col-md-6">
                            <h6 class="text-muted small mb-1">Numer paragonu</h6>
                            <p class="fw-semibold">{{ $transportCost->receipt_number }}</p>
                        </div>
                        @endif
                        @if($transportCost->notes)
                        <div class="col-12">
                            <h6 class="text-muted small mb-1">Notatki</h6>
                            <p>{{ $transportCost->notes }}</p>
                        </div>
                        @endif
                    </div>
    </x-ui.card>
</x-app-layout>
