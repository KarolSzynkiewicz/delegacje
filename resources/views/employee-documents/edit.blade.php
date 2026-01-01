@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <h1>Edytuj Dokument: {{ $employeeDocument->document->name ?? '-' }}</h1>
            <p class="text-muted">Pracownik: {{ $employee->full_name }}</p>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('employees.employee-documents.update', [$employee, $employeeDocument]) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="document_id" class="form-label">Typ dokumentu *</label>
                    <select class="form-select @error('document_id') is-invalid @enderror" id="document_id" name="document_id" required>
                        <option value="">-- Wybierz dokument --</option>
                        @foreach($documents as $document)
                            <option value="{{ $document->id }}" {{ old('document_id', $employeeDocument->document_id) == $document->id ? 'selected' : '' }}>
                                {{ $document->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('document_id') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    <small class="form-text text-muted">
                        <a href="{{ route('documents.create') }}" target="_blank">Dodaj nowy typ dokumentu</a>
                    </small>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="valid_from" class="form-label">Dokument ważny od *</label>
                            <input type="date" class="form-control @error('valid_from') is-invalid @enderror" id="valid_from" name="valid_from" value="{{ old('valid_from', $employeeDocument->valid_from ? $employeeDocument->valid_from->format('Y-m-d') : '') }}" required>
                            @error('valid_from') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="valid_to" class="form-label">Dokument ważny do</label>
                            <input type="date" class="form-control @error('valid_to') is-invalid @enderror" id="valid_to" name="valid_to" value="{{ old('valid_to', $employeeDocument->valid_to?->format('Y-m-d')) }}">
                            <small class="form-text text-muted">Wymagane dla dokumentów okresowych</small>
                            @error('valid_to') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input @error('is_okresowy') is-invalid @enderror" type="checkbox" id="is_okresowy" name="is_okresowy" value="1" {{ old('is_okresowy', $employeeDocument->kind === 'okresowy') ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_okresowy">
                            Dokument okresowy (z datą ważności do)
                        </label>
                        @error('is_okresowy') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        <small class="form-text text-muted d-block">Odznacz, jeśli dokument jest bezokresowy</small>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="notes" class="form-label">Notatki</label>
                    <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3">{{ old('notes', $employeeDocument->notes) }}</textarea>
                    @error('notes') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>

                <div class="mb-3">
                    <label for="file" class="form-label">Załącz plik</label>
                    @if($employeeDocument->file_path)
                        <div class="mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <a href="{{ $employeeDocument->file_url }}" target="_blank" class="btn btn-sm btn-info">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-file-earmark" viewBox="0 0 16 16">
                                        <path d="M14 4.5V14a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h5.5L14 4.5zm-3 0A1.5 1.5 0 0 1 9.5 3V1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V4.5h-2z"/>
                                    </svg>
                                    Pobierz obecny plik
                                </a>
                            </div>
                            <small class="text-muted d-block mt-1">Obecny plik: {{ basename($employeeDocument->file_path) }}</small>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="remove_file" name="remove_file" value="1">
                            <label class="form-check-label" for="remove_file">
                                Usuń obecny plik
                            </label>
                        </div>
                    @endif
                    <input type="file" class="form-control @error('file') is-invalid @enderror" id="file" name="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.odt,.txt">
                    @error('file') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    <small class="form-text text-muted">Dozwolone formaty: PDF, DOC, DOCX, XLS, XLSX, ODT, TXT (max 10MB). Zostaw puste, aby zachować obecny plik.</small>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Zaktualizuj Dokument</button>
                    <a href="{{ route('employees.show', $employee) }}" class="btn btn-secondary">Anuluj</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Automatycznie wyczyść pole valid_to gdy odznaczono checkbox
    document.getElementById('is_okresowy').addEventListener('change', function() {
        const validToField = document.getElementById('valid_to');
        if (!this.checked) {
            validToField.value = '';
            validToField.disabled = true;
        } else {
            validToField.disabled = false;
        }
    });

    // Wywołaj przy załadowaniu strony
    document.getElementById('is_okresowy').dispatchEvent(new Event('change'));
</script>
@endsection
