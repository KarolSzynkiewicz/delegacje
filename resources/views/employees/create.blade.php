@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <h1>Dodaj Nowego Pracownika</h1>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('employees.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="mb-3">
                    <x-ui.input 
                        type="text" 
                        name="first_name" 
                        label="Imię"
                        value="{{ old('first_name') }}"
                        required="true"
                    />
                </div>

                <div class="mb-3">
                    <x-ui.input 
                        type="text" 
                        name="last_name" 
                        label="Nazwisko"
                        value="{{ old('last_name') }}"
                        required="true"
                    />
                </div>

                <div class="mb-3">
                    <x-ui.input 
                        type="email" 
                        name="email" 
                        label="Email"
                        value="{{ old('email') }}"
                        required="true"
                    />
                </div>

                <div class="mb-3">
                    <x-ui.input 
                        type="text" 
                        name="phone" 
                        label="Telefon"
                        value="{{ old('phone') }}"
                    />
                </div>

                <div class="mb-3">
                    <label class="form-label">Role *</label>
                    <div class="border rounded p-3 @error('roles') border-danger @enderror">
                        @foreach ($roles as $role)
                            <div class="form-check @error('roles') is-invalid @enderror">
                                <input 
                                    type="checkbox" 
                                    id="role_{{ $role->id }}" 
                                    name="roles[]" 
                                    value="{{ $role->id }}"
                                    {{ in_array($role->id, old('roles', [])) ? 'checked' : '' }}
                                    class="@error('roles') is-invalid @enderror"
                                />
                                <label for="role_{{ $role->id }}">
                                    {{ $role->name }}
                                    @if($role->description)
                                        <small class="text-muted d-block">({{ $role->description }})</small>
                                    @endif
                                </label>
                            </div>
                        @endforeach
                    </div>
                    @error('roles') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
                    <small class="form-text text-muted">Wybierz przynajmniej jedną rolę. Pracownik może mieć wiele ról jednocześnie.</small>
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
                    <x-ui.button variant="primary" type="submit">Dodaj Pracownika</x-ui.button>
                    <x-ui.button variant="ghost" href="{{ route('employees.index') }}">Anuluj</x-ui.button>
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
