@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <h1>Edytuj Pracownika</h1>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('employees.update', $employee) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="first_name" class="form-label">Imię *</label>
                    <input type="text" class="form-control @error('first_name') is-invalid @enderror" id="first_name" name="first_name" value="{{ old('first_name', $employee->first_name) }}" required>
                    @error('first_name') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>

                <div class="mb-3">
                    <label for="last_name" class="form-label">Nazwisko *</label>
                    <input type="text" class="form-control @error('last_name') is-invalid @enderror" id="last_name" name="last_name" value="{{ old('last_name', $employee->last_name) }}" required>
                    @error('last_name') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email *</label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $employee->email) }}" required>
                    @error('email') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>

                <div class="mb-3">
                    <label for="phone" class="form-label">Telefon</label>
                    <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $employee->phone) }}">
                    @error('phone') <span class="invalid-feedback">{{ $message }}</span> @enderror
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
                                    {{ in_array($role->id, old('roles', $employee->roles->pluck('id')->toArray())) ? 'checked' : '' }}
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
                    <label for="notes" class="form-label">Notatki</label>
                    <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="4">{{ old('notes', $employee->notes) }}</textarea>
                    @error('notes') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>

                <div class="mb-3">
                    <label for="image" class="form-label">Zdjęcie</label>
                    @if($employee->image_path)
                        <div class="mb-2">
                            <p class="text-muted">Aktualne zdjęcie:</p>
                            <img src="{{ $employee->image_url }}" alt="{{ $employee->full_name }}" class="img-thumbnail" style="max-width: 300px; max-height: 300px;">
                        </div>
                    @endif
                    <input type="file" class="form-control @error('image') is-invalid @enderror" id="image" name="image" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp">
                    <small class="form-text text-muted">Maksymalny rozmiar: 2MB. Dozwolone formaty: JPEG, PNG, JPG, GIF, WEBP. Zostaw puste, aby zachować obecne zdjęcie.</small>
                    @error('image') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    <div id="imagePreview" class="mt-3" style="display: none;">
                        <p class="text-muted">Nowe zdjęcie:</p>
                        <img id="previewImg" src="" alt="Podgląd" class="img-thumbnail" style="max-width: 300px; max-height: 300px;">
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Zaktualizuj Pracownika</button>
                    <a href="{{ route('employees.show', $employee) }}" class="btn btn-secondary">Anuluj</a>
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
