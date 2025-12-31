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
                    <label for="role_id" class="form-label">Rola *</label>
                    <select class="form-select @error('role_id') is-invalid @enderror" id="role_id" name="role_id" required>
                        <option value="">-- Wybierz Rolę --</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}" {{ old('role_id', $employee->role_id) == $role->id ? 'selected' : '' }}>{{ $role->name }}</option>
                        @endforeach
                    </select>
                    @error('role_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="a1_valid_from" class="form-label">Prawo Jazdy A1 Ważne Od</label>
                            <input type="date" class="form-control @error('a1_valid_from') is-invalid @enderror" id="a1_valid_from" name="a1_valid_from" value="{{ old('a1_valid_from', $employee->a1_valid_from?->format('Y-m-d')) }}">
                            @error('a1_valid_from') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="a1_valid_to" class="form-label">Prawo Jazdy A1 Ważne Do</label>
                            <input type="date" class="form-control @error('a1_valid_to') is-invalid @enderror" id="a1_valid_to" name="a1_valid_to" value="{{ old('a1_valid_to', $employee->a1_valid_to?->format('Y-m-d')) }}">
                            @error('a1_valid_to') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="document_1" class="form-label">Dokument 1</label>
                    <input type="text" class="form-control @error('document_1') is-invalid @enderror" id="document_1" name="document_1" value="{{ old('document_1', $employee->document_1) }}" placeholder="np. Certyfikat, Licencja">
                    @error('document_1') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>

                <div class="mb-3">
                    <label for="document_2" class="form-label">Dokument 2</label>
                    <input type="text" class="form-control @error('document_2') is-invalid @enderror" id="document_2" name="document_2" value="{{ old('document_2', $employee->document_2) }}" placeholder="np. Certyfikat, Licencja">
                    @error('document_2') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>

                <div class="mb-3">
                    <label for="document_3" class="form-label">Dokument 3</label>
                    <input type="text" class="form-control @error('document_3') is-invalid @enderror" id="document_3" name="document_3" value="{{ old('document_3', $employee->document_3) }}" placeholder="np. Certyfikat, Licencja">
                    @error('document_3') <span class="invalid-feedback">{{ $message }}</span> @enderror
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
