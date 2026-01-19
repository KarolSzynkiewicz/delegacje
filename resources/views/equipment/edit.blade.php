<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Edytuj Sprzęt: {{ $equipment->name }}">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('equipment.show', $equipment) }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Edytuj Sprzęt">
                <x-ui.errors />

                <form method="POST" action="{{ route('equipment.update', $equipment) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <x-ui.input 
                            type="text" 
                            name="name" 
                            label="Nazwa"
                            value="{{ old('name', $equipment->name) }}"
                            required="true"
                        />
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="textarea" 
                            name="description" 
                            label="Opis"
                            value="{{ old('description', $equipment->description) }}"
                            rows="3"
                        />
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="text" 
                            name="category" 
                            label="Kategoria"
                            value="{{ old('category', $equipment->category) }}"
                        />
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <x-ui.input 
                                type="number" 
                                name="quantity_in_stock" 
                                label="Ilość w magazynie"
                                value="{{ old('quantity_in_stock', $equipment->quantity_in_stock) }}"
                                min="0"
                                required="true"
                            />
                        </div>
                        <div class="col-md-6">
                            <x-ui.input 
                                type="number" 
                                name="min_quantity" 
                                label="Minimalna ilość"
                                value="{{ old('min_quantity', $equipment->min_quantity) }}"
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
                            value="{{ old('unit', $equipment->unit) }}"
                            required="true"
                        />
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="number" 
                            name="unit_cost" 
                            label="Koszt jednostkowy"
                            value="{{ old('unit_cost', $equipment->unit_cost) }}"
                            step="0.01"
                            min="0"
                        />
                    </div>

                    <div class="mb-4">
                        <x-ui.input 
                            type="textarea" 
                            name="notes" 
                            label="Notatki"
                            value="{{ old('notes', $equipment->notes) }}"
                            rows="3"
                        />
                    </div>

                    <div class="d-flex justify-content-end align-items-center gap-2">
                        <x-ui.button 
                            variant="ghost" 
                            href="{{ route('equipment.show', $equipment) }}"
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
