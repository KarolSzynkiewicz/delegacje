@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <h1>Dodaj Nową Akomodację</h1>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('accommodations.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="mb-3">
                    <x-ui.input 
                        type="text" 
                        name="name" 
                        label="Nazwa"
                        value="{{ old('name') }}"
                        placeholder="np. Mieszkanie 1, Mieszkanie 2"
                        required="true"
                    />
                </div>

                <div class="mb-3">
                    <x-ui.input 
                        type="text" 
                        name="address" 
                        label="Adres"
                        value="{{ old('address') }}"
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
                                value="{{ old('city') }}"
                            />
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <x-ui.input 
                                type="text" 
                                name="postal_code" 
                                label="Kod Pocztowy"
                                value="{{ old('postal_code') }}"
                            />
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <x-ui.input 
                        type="number" 
                        name="capacity" 
                        label="Pojemność (liczba osób)"
                        value="{{ old('capacity') }}"
                        min="1"
                        required="true"
                    />
                </div>

                <div class="mb-3">
                    <x-ui.input 
                        type="textarea" 
                        name="description" 
                        label="Opis"
                        value="{{ old('description') }}"
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
                    <x-ui.button variant="primary" type="submit">Dodaj Akomodację</x-ui.button>
                    <x-ui.button variant="ghost" href="{{ route('accommodations.index') }}">Anuluj</x-ui.button>
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
