<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Edytuj Akomodację: {{ $accommodation->name }}">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('accommodations.show', $accommodation) }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Edytuj Akomodację">
                <x-ui.errors />

                <form action="{{ route('accommodations.update', $accommodation) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <x-ui.input 
                            type="text" 
                            name="name" 
                            label="Nazwa"
                            value="{{ old('name', $accommodation->name) }}"
                            required="true"
                        />
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="text" 
                            name="address" 
                            label="Adres"
                            value="{{ old('address', $accommodation->address) }}"
                            required="true"
                        />
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <x-ui.input 
                                    type="text" 
                                    name="city" 
                                    label="Miasto"
                                    value="{{ old('city', $accommodation->city) }}"
                                />
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <x-ui.input 
                                    type="text" 
                                    name="postal_code" 
                                    label="Kod Pocztowy"
                                    value="{{ old('postal_code', $accommodation->postal_code) }}"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="number" 
                            name="capacity" 
                            label="Pojemność (liczba osób)"
                            value="{{ old('capacity', $accommodation->capacity) }}"
                            min="1"
                            required="true"
                        />
                    </div>

                    <div x-data="{ type: '{{ old('type', $accommodation->type ?? 'własny') }}' }">
                        <div class="mb-3">
                            <x-ui.input 
                                type="select" 
                                name="type" 
                                label="Typ"
                                required="true"
                                x-model="type"
                            >
                                <option value="własny" {{ old('type', $accommodation->type ?? 'własny') === 'własny' ? 'selected' : '' }}>Własny</option>
                                <option value="wynajmowany" {{ old('type', $accommodation->type ?? 'własny') === 'wynajmowany' ? 'selected' : '' }}>Wynajmowany</option>
                            </x-ui.input>
                        </div>

                        <div class="mb-3" x-show="type === 'wynajmowany'" x-cloak>
                            <div class="row">
                                <div class="col-md-6">
                                    <x-ui.input 
                                        type="date" 
                                        name="lease_start_date" 
                                        label="Okres najmu - od"
                                        value="{{ old('lease_start_date', $accommodation->lease_start_date?->format('Y-m-d')) }}"
                                    />
                                </div>
                                <div class="col-md-6">
                                    <x-ui.input 
                                        type="date" 
                                        name="lease_end_date" 
                                        label="Okres najmu - do"
                                        value="{{ old('lease_end_date', $accommodation->lease_end_date?->format('Y-m-d')) }}"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="textarea" 
                            name="description" 
                            label="Opis"
                            value="{{ old('description', $accommodation->description) }}"
                            rows="4"
                        />
                    </div>

                    <x-ui.image-preview 
                        :showCurrentImage="$accommodation->image_path ? true : false"
                        :currentImageUrl="$accommodation->image_path ? $accommodation->image_url : null"
                        :currentImage="$accommodation->name"
                    />

                    <div class="d-flex justify-content-between align-items-center">
                        <x-ui.button 
                            variant="primary" 
                            type="submit"
                            action="save"
                        >
                            Zaktualizuj Akomodację
                        </x-ui.button>
                        <x-ui.button 
                            variant="ghost" 
                            href="{{ route('accommodations.show', $accommodation) }}"
                            action="cancel"
                        >
                            Anuluj
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
