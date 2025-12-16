@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <h1>Edytuj Zapis Czasu Pracy</h1>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('time_logs.update', $timeLog) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="delegation_id" class="form-label">Delegacja *</label>
                    <select class="form-select @error('delegation_id') is-invalid @enderror" id="delegation_id" name="delegation_id" required>
                        <option value="">-- Wybierz Delegację --</option>
                        @foreach ($delegations as $delegation)
                            <option value="{{ $delegation->id }}" {{ old('delegation_id', $timeLog->delegation_id) == $delegation->id ? 'selected' : '' }}>
                                {{ $delegation->employee->name }} - {{ $delegation->project->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('delegation_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>

                <div class="mb-3">
                    <label for="start_time" class="form-label">Data i Czas Rozpoczęcia *</label>
                    <input type="datetime-local" class="form-control @error('start_time') is-invalid @enderror" id="start_time" name="start_time" value="{{ old('start_time', $timeLog->start_time->format('Y-m-d\TH:i')) }}" required>
                    @error('start_time') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>

                <div class="mb-3">
                    <label for="end_time" class="form-label">Data i Czas Zakończenia</label>
                    <input type="datetime-local" class="form-control @error('end_time') is-invalid @enderror" id="end_time" name="end_time" value="{{ old('end_time', $timeLog->end_time?->format('Y-m-d\TH:i')) }}">
                    @error('end_time') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>

                <div class="mb-3">
                    <label for="hours_worked" class="form-label">Godziny Pracy</label>
                    <input type="number" step="0.5" class="form-control @error('hours_worked') is-invalid @enderror" id="hours_worked" name="hours_worked" value="{{ old('hours_worked', $timeLog->hours_worked) }}">
                    @error('hours_worked') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>

                <div class="mb-3">
                    <label for="notes" class="form-label">Notatki</label>
                    <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="4">{{ old('notes', $timeLog->notes) }}</textarea>
                    @error('notes') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Zaktualizuj Zapis</button>
                    <a href="{{ route('time_logs.index') }}" class="btn btn-secondary">Anuluj</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
