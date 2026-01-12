@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <h1>Dodaj Nowy Pojazd</h1>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('vehicles.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="mb-3">
                    <x-ui.input 
                        type="text" 
                        name="registration_number" 
                        label="Numer Rejestracyjny"
                        value="{{ old('registration_number') }}"
                        placeholder="np. WA 12345"
                        required="true"
                    />
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <x-ui.input 
                                type="text" 
                                name="brand" 
                                label="Marka"
                                value="{{ old('brand') }}"
                                placeholder="np. Volkswagen"
                            />
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <x-ui.input 
                                type="text" 
                                name="model" 
                                label="Model"
                                value="{{ old('model') }}"
                                placeholder="np. Transporter"
                            />
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <x-ui.input 
                                type="number" 
                                name="capacity" 
                                label="Pojemność (liczba osób)"
                                value="{{ old('capacity') }}"
                                min="1"
                            />
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <x-ui.input 
                                type="select" 
                                name="technical_condition" 
                                label="Stan Techniczny"
                                required="true"
                            >
                                <option value="">-- Wybierz Stan --</option>
                                <option value="excellent" {{ old('technical_condition') == 'excellent' ? 'selected' : '' }}>Doskonały</option>
                                <option value="good" {{ old('technical_condition') == 'good' ? 'selected' : '' }}>Dobry</option>
                                <option value="fair" {{ old('technical_condition') == 'fair' ? 'selected' : '' }}>Zadowalający</option>
                                <option value="poor" {{ old('technical_condition') == 'poor' ? 'selected' : '' }}>Słaby</option>
                            </x-ui.input>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <x-ui.input 
                        type="date" 
                        name="inspection_valid_to" 
                        label="Przegląd Ważny Do"
                        value="{{ old('inspection_valid_to') }}"
                    />
                </div>

                <div class="mb-3">
                    <x-ui.input 
                        type="textarea" 
                        name="notes" 
                        label="Notatki"
                        value="{{ old('notes') }}"
                        rows="4"
                    />
                </div>

                <div class="mb-3">
                    <x-ui.input 
                        type="file" 
                        name="image" 
                        label="Zdjęcie"
                        accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
                    />
                    <small class="form-text text-muted">Maksymalny rozmiar: 2MB. Dozwolone formaty: JPEG, PNG, JPG, GIF, WEBP</small>
                    <div id="imagePreview" class="mt-3" style="display: none;">
                        <img id="previewImg" src="" alt="Podgląd" class="img-thumbnail">
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <x-ui.button variant="primary" type="submit">Dodaj Pojazd</x-ui.button>
                    <x-ui.button variant="ghost" href="{{ route('vehicles.index') }}">Anuluj</x-ui.button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.getElementById('image').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('previewImg').src = e.target.result;
                document.getElementById('imagePreview').style.display = 'block';
            }
            reader.readAsDataURL(file);
        } else {
            document.getElementById('imagePreview').style.display = 'none';
        }
    });
</script>
@endsection
