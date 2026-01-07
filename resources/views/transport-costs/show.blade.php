<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Koszt Transportu
            </h2>
            <div>
                <a href="{{ route('transport-costs.edit', $transportCost) }}" class="bg-indigo-500 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded mr-2">
                    Edytuj
                </a>
                <a href="{{ route('transport-costs.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    Powrót
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="mb-6">
                    <h3 class="text-lg font-semibold mb-4">Informacje podstawowe</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500">Typ kosztu</p>
                            <p class="font-semibold">{{ ucfirst($transportCost->cost_type) }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Kwota</p>
                            <p class="font-semibold text-lg">{{ number_format($transportCost->amount, 2) }} {{ $transportCost->currency }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Data kosztu</p>
                            <p class="font-semibold">{{ $transportCost->cost_date->format('Y-m-d') }}</p>
                        </div>
                        @if($transportCost->logisticsEvent)
                        <div>
                            <p class="text-sm text-gray-500">Zdarzenie logistyczne</p>
                            <a href="{{ route('return-trips.show', $transportCost->logisticsEvent) }}" class="font-semibold text-blue-600 hover:text-blue-900">
                                Zdarzenie #{{ $transportCost->logisticsEvent->id }}
                            </a>
                        </div>
                        @endif
                        @if($transportCost->vehicle)
                        <div>
                            <p class="text-sm text-gray-500">Pojazd</p>
                            <p class="font-semibold">{{ $transportCost->vehicle->registration_number }}</p>
                        </div>
                        @endif
                        @if($transportCost->transport)
                        <div>
                            <p class="text-sm text-gray-500">Transport</p>
                            <p class="font-semibold">{{ $transportCost->transport->mode->label() }}</p>
                        </div>
                        @endif
                        @if($transportCost->description)
                        <div>
                            <p class="text-sm text-gray-500">Opis</p>
                            <p class="font-semibold">{{ $transportCost->description }}</p>
                        </div>
                        @endif
                        @if($transportCost->receipt_number)
                        <div>
                            <p class="text-sm text-gray-500">Numer paragonu</p>
                            <p class="font-semibold">{{ $transportCost->receipt_number }}</p>
                        </div>
                        @endif
                        @if($transportCost->notes)
                        <div class="col-span-2">
                            <p class="text-sm text-gray-500">Notatki</p>
                            <p>{{ $transportCost->notes }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
