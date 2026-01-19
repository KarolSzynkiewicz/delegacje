<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Dodaj Sprzęt">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('equipment.index') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Dodaj Sprzęt">
                <x-ui.errors />

                <form method="POST" action="{{ route('equipment.store') }}">
                    @csrf

                    <div class="mb-3">
                        <x-ui.input 
                            type="text" 
                            name="name" 
                            label="Nazwa"
                            value="{{ old('name') }}"
                            required="true"
                        />
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="textarea" 
                            name="description" 
                            label="Opis"
                            value="{{ old('description') }}"
                            rows="3"
                        />
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="text" 
                            name="category" 
                            label="Kategoria"
                            value="{{ old('category') }}"
                        />
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <x-ui.input 
                                type="number" 
                                name="quantity_in_stock" 
                                label="Ilość w magazynie"
                                value="{{ old('quantity_in_stock', 0) }}"
                                min="0"
                                required="true"
                            />
                        </div>
                        <div class="col-md-6">
                            <x-ui.input 
                                type="number" 
                                name="min_quantity" 
                                label="Minimalna ilość"
                                value="{{ old('min_quantity', 0) }}"
                                min="0"
                                required="true"
                            />
                        </div>
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="text" 
                            name="unit" 
                            label="Jednostka"
                            value="{{ old('unit', 'szt') }}"
                            required="true"
                        />
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="number" 
                            name="unit_cost" 
                            label="Koszt jednostkowy"
                            value="{{ old('unit_cost') }}"
                            step="0.01"
                            min="0"
                        />
                    </div>

                    <div class="mb-4">
                        <x-ui.input 
                            type="textarea" 
                            name="notes" 
                            label="Notatki"
                            value="{{ old('notes') }}"
                            rows="3"
                        />
                    </div>

                    <div class="d-flex justify-content-end align-items-center gap-2">
                        <x-ui.button 
                            variant="ghost" 
                            href="{{ route('equipment.index') }}"
                            action="cancel"
                        >
                            Anuluj
                        </x-ui.button>
                        <x-ui.button 
                            variant="primary" 
                            type="submit"
                            action="save"
                        >
                            Zapisz
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
