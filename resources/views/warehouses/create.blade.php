<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Nowy magazyn">
            <x-slot name="left">
                <x-ui.button
                    variant="ghost"
                    href="{{ route('warehouses.index') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-6">
            <x-ui.card label="Magazyn w lokalizacji">
                <p class="text-muted small mb-3">
                    Katalog sprzętu jest wspólny. Ten magazyn ma własne stany — np. siedziba i warsztat na projekcie.
                    Lokalizacja dostanie typ <strong>Magazyn</strong>.
                </p>
                <form method="POST" action="{{ route('warehouses.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="warehouse-location">Lokalizacja <span class="text-danger">*</span></label>
                        <select id="warehouse-location" name="location_id" class="form-select @error('location_id') is-invalid @enderror">
                            <option value="">Wybierz lokalizację</option>
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}" @selected(old('location_id') == $location->id)>
                                    {{ $location->name }}@if($location->city) ({{ $location->city }})@endif
                                </option>
                            @endforeach
                        </select>
                        @error('location_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-4">
                        <label class="form-label" for="warehouse-name">Nazwa magazynu</label>
                        <input
                            id="warehouse-name"
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            class="form-control @error('name') is-invalid @enderror"
                            placeholder="Domyślnie nazwa lokalizacji"
                        >
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <x-ui.button
                            variant="ghost"
                            href="{{ route('warehouses.index') }}"
                            action="cancel"
                        >
                            Anuluj
                        </x-ui.button>
                        <x-ui.button variant="primary" type="submit" action="save">
                            Dodaj magazyn
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
