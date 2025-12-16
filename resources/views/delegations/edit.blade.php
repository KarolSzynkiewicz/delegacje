@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <h1>Edytuj Delegację</h1>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('delegations.update', $delegation) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="employee_id" class="form-label">Pracownik *</label>
                    <select class="form-select @error('employee_id') is-invalid @enderror" id="employee_id" name="employee_id" required>
                        <option value="">-- Wybierz Pracownika --</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}" {{ old('employee_id', $delegation->employee_id) == $employee->id ? 'selected' : '' }}>
                                {{ $employee->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('employee_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>

                <div class="mb-3">
                    <label for="project_id" class="form-label">Projekt *</label>
                    <select class="form-select @error('project_id') is-invalid @enderror" id="project_id" name="project_id" required>
                        <option value="">-- Wybierz Projekt --</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}" {{ old('project_id', $delegation->project_id) == $project->id ? 'selected' : '' }}>
                                {{ $project->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('project_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>

                <div class="mb-3">
                    <label for="start_time" class="form-label">Data i Czas Rozpoczęcia *</label>
                    <input type="datetime-local" class="form-control @error('start_time') is-invalid @enderror" id="start_time" name="start_time" value="{{ old('start_time', $delegation->start_time->format('Y-m-d\TH:i')) }}" required>
                    @error('start_time') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>

                <div class="mb-3">
                    <label for="end_time" class="form-label">Data i Czas Zakończenia</label>
                    <input type="datetime-local" class="form-control @error('end_time') is-invalid @enderror" id="end_time" name="end_time" value="{{ old('end_time', $delegation->end_time?->format('Y-m-d\TH:i')) }}">
                    @error('end_time') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>

                <div class="mb-3">
                    <label for="status" class="form-label">Status *</label>
                    <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                        <option value="pending" {{ old('status', $delegation->status) === 'pending' ? 'selected' : '' }}>Oczekujące</option>
                        <option value="active" {{ old('status', $delegation->status) === 'active' ? 'selected' : '' }}>Aktywne</option>
                        <option value="completed" {{ old('status', $delegation->status) === 'completed' ? 'selected' : '' }}>Zakończone</option>
                        <option value="cancelled" {{ old('status', $delegation->status) === 'cancelled' ? 'selected' : '' }}>Anulowane</option>
                    </select>
                    @error('status') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>

                <div class="mb-3">
                    <label for="notes" class="form-label">Notatki</label>
                    <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="4">{{ old('notes', $delegation->notes) }}</textarea>
                    @error('notes') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Zaktualizuj Delegację</button>
                    <a href="{{ route('delegations.index') }}" class="btn btn-secondary">Anuluj</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
