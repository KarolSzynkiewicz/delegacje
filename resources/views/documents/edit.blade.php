@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <h1>Edytuj Dokument: {{ $document->name }}</h1>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('documents.update', $document) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="name" class="form-label">Nazwa dokumentu *</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $document->name) }}" required>
                    @error('name') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Opis</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $document->description) }}</textarea>
                    @error('description') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>

                <div class="mb-3">
                    <label for="is_periodic" class="form-label">Dokument okresowy *</label>
                    <select class="form-select @error('is_periodic') is-invalid @enderror" id="is_periodic" name="is_periodic" required>
                        <option value="">-- Wybierz --</option>
                        <option value="1" {{ old('is_periodic', $document->is_periodic) == '1' || old('is_periodic', $document->is_periodic) === true ? 'selected' : '' }}>Tak</option>
                        <option value="0" {{ old('is_periodic', $document->is_periodic) == '0' || old('is_periodic', $document->is_periodic) === false ? 'selected' : '' }}>Nie</option>
                    </select>
                    @error('is_periodic') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    <small class="form-text text-muted">Czy dokument ma datę ważności do?</small>
                </div>

                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input @error('is_required') is-invalid @enderror" type="checkbox" id="is_required" name="is_required" value="1" {{ old('is_required', $document->is_required) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_required">
                            Dokument wymagany
                        </label>
                        @error('is_required') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        <small class="form-text text-muted d-block">Zaznacz, jeśli dokument jest wymagany dla wszystkich pracowników</small>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Zaktualizuj Dokument</button>
                    <a href="{{ route('documents.index') }}" class="btn btn-secondary">Anuluj</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
