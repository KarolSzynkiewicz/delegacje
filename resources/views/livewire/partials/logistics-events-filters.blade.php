@props([
    'total' => 0,
    'vehicles',
])

<x-data-table-filters :count="$total">
    <x-data-table-search
        wire:model.live.debounce.300ms="employeeSearch"
        placeholder="Uczestnik: imię, nazwisko, telefon…"
        wide
    />
    <select wire:model.live="transport" class="form-select form-select-sm">
        <option value="">Transport: dowolny</option>
        <option value="vehicle">Pojazd firmowy</option>
        <option value="no_vehicle">Bez pojazdu</option>
    </select>
    <select wire:model.live="vehicleFilter" class="form-select form-select-sm">
        <option value="">Pojazd: dowolny</option>
        <option value="none">Bez pojazdu</option>
        @foreach($vehicles as $vehicle)
            <option value="{{ $vehicle->id }}">{{ $vehicle->registration_number }}</option>
        @endforeach
    </select>
</x-data-table-filters>
