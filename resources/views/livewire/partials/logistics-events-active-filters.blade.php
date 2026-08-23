@if($employeeSearch !== '')
    <x-data-table-filter-chip label="Uczestnik: {{ $employeeSearch }}" wire:click="$set('employeeSearch', '')" />
@endif
@if($transport === 'vehicle')
    <x-data-table-filter-chip label="Transport: pojazd firmowy" wire:click="$set('transport', '')" />
@elseif($transport === 'no_vehicle')
    <x-data-table-filter-chip label="Transport: bez pojazdu" wire:click="$set('transport', '')" />
@endif
@if($vehicleFilter === 'none')
    <x-data-table-filter-chip label="Pojazd: bez pojazdu" wire:click="$set('vehicleFilter', '')" />
@elseif(is_numeric($vehicleFilter))
    @php $v = $vehicles->firstWhere('id', (int) $vehicleFilter); @endphp
    <x-data-table-filter-chip label="Pojazd: {{ $v?->registration_number ?? $vehicleFilter }}" wire:click="$set('vehicleFilter', '')" />
@endif
