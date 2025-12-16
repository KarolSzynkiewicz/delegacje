@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <h1>Edytuj Pojazd</h1>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('vehicles.update', $vehicle) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="registration_number" class="form-label">Numer Rejestracyjny *</label>
                    <input type="text" class="form-control @error('registration_number') is-invalid @enderror" id="registration_number" name="registration_number" value="{{ old('registration_number', $vehicle->registration_number) }}" required>
                    @error('registration_number') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="brand" class="form-label">Marka</label>
                            <input type="text" class="form-control @error('brand') is-invalid @enderror" id="brand" name="brand" value="{{ old('brand', $vehicle->brand) }}">
                            @error('brand') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="model" class="form-label">Model</label>
                            <input type="text" class="form-control @error('model') is-invalid @enderror" id="model" name="model" value="{{ old('model', $vehicle->model) }}">
                            @error('model') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="capacity" class="form-label">Pojemność (liczba osób)</label>
                            <input type="number" class="form-control @error('capacity') is-invalid @enderror" id="capacity" name="capacity" value="{{ old('capacity', $vehicle->capacity) }}" min="1">
                            @error('capacity') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="technical_condition" class="form-label">Stan Techniczny *</label>
                            <select class="form-select @error('technical_condition') is-invalid @enderror" id="technical_condition" name="technical_condition" required>
                                <option value="">-- Wybierz Stan --</option>
                                <option value="excellent" {{ old('technical_condition', $vehicle->technical_condition) == 'excellent' ? 'selected' : '' }}>Doskonały</option>
                                <option value="good" {{ old('technical_condition', $vehicle->technical_condition) == 'good' ? 'selected' : '' }}>Dobry</option>
                                <option value="fair" {{ old('technical_condition', $vehicle->technical_condition) == 'fair' ? 'selected' : '' }}>Zadowalający</option>
                                <option value="poor" {{ old('technical_condition', $vehicle->technical_condition) == 'poor' ? 'selected' : '' }}>Słaby</option>
                            </select>
                            @error('technical_condition') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="inspection_valid_to" class="form-label">Przegląd Ważny Do</label>
                    <input type="date" class="form-control @error('inspection_valid_to') is-invalid @enderror" id="inspection_valid_to" name="inspection_valid_to" value="{{ old('inspection_valid_to', $vehicle->inspection_valid_to?->format('Y-m-d')) }}">
                    @error('inspection_valid_to') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>

                <div class="mb-3">
                    <label for="notes" class="form-label">Notatki</label>
                    <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="4">{{ old('notes', $vehicle->notes) }}</textarea>
                    @error('notes') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Zaktualizuj Pojazd</button>
                    <a href="{{ route('vehicles.show', $vehicle) }}" class="btn btn-secondary">Anuluj</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
