<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Edytuj Koszt Transportu
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('transport-costs.update', $transportCost) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Zdarzenie logistyczne (opcjonalne)</label>
                        <select name="logistics_event_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                            <option value="">Brak</option>
                            @foreach($events as $event)
                                <option value="{{ $event->id }}" {{ old('logistics_event_id', $transportCost->logistics_event_id) == $event->id ? 'selected' : '' }}>
                                    {{ $event->type->label() }} - {{ $event->event_date->format('Y-m-d H:i') }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Pojazd (opcjonalne)</label>
                        <select name="vehicle_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                            <option value="">Brak</option>
                            @foreach($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}" {{ old('vehicle_id', $transportCost->vehicle_id) == $vehicle->id ? 'selected' : '' }}>
                                    {{ $vehicle->registration_number }} - {{ $vehicle->brand }} {{ $vehicle->model }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Transport (opcjonalne)</label>
                        <select name="transport_id" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                            <option value="">Brak</option>
                            @foreach($transports as $transport)
                                <option value="{{ $transport->id }}" {{ old('transport_id', $transportCost->transport_id) == $transport->id ? 'selected' : '' }}>
                                    {{ $transport->mode->label() }} - {{ $transport->departure_datetime->format('Y-m-d H:i') }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Typ kosztu *</label>
                        <select name="cost_type" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                            <option value="fuel" {{ old('cost_type', $transportCost->cost_type) == 'fuel' ? 'selected' : '' }}>Paliwo</option>
                            <option value="ticket" {{ old('cost_type', $transportCost->cost_type) == 'ticket' ? 'selected' : '' }}>Bilet</option>
                            <option value="parking" {{ old('cost_type', $transportCost->cost_type) == 'parking' ? 'selected' : '' }}>Parking</option>
                            <option value="toll" {{ old('cost_type', $transportCost->cost_type) == 'toll' ? 'selected' : '' }}>Opłata drogowa</option>
                            <option value="other" {{ old('cost_type', $transportCost->cost_type) == 'other' ? 'selected' : '' }}>Inne</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2">Kwota *</label>
                            <input type="number" name="amount" value="{{ old('amount', $transportCost->amount) }}" step="0.01" min="0" required
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-bold mb-2">Waluta *</label>
                            <input type="text" name="currency" value="{{ old('currency', $transportCost->currency) }}" maxlength="3" required
                                class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Data kosztu *</label>
                        <input type="date" name="cost_date" value="{{ old('cost_date', $transportCost->cost_date->format('Y-m-d')) }}" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Opis</label>
                        <input type="text" name="description" value="{{ old('description', $transportCost->description) }}"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Numer paragonu</label>
                        <input type="text" name="receipt_number" value="{{ old('receipt_number', $transportCost->receipt_number) }}"
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Notatki</label>
                        <textarea name="notes" rows="3" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700">{{ old('notes', $transportCost->notes) }}</textarea>
                    </div>

                    <div class="flex items-center justify-end">
                        <a href="{{ route('transport-costs.show', $transportCost) }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded mr-3">
                            Anuluj
                        </a>
                        <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Zapisz
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
