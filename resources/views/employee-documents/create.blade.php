@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <h1>Dodaj Dokument dla: {{ $employee->full_name }}</h1>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('employees.employee-documents.store', $employee) }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="mb-3">
                    <label for="document_id" class="form-label">Typ dokumentu *</label>
                    <select class="form-select @error('document_id') is-invalid @enderror" id="document_id" name="document_id" required>
                        <option value="">-- Wybierz dokument --</option>
                        @foreach($documents as $document)
                            <option value="{{ $document->id }}" {{ old('document_id', $selectedDocumentId ?? null) == $document->id ? 'selected' : '' }}>
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
                            <input type="date" class="form-control @error('valid_from') is-invalid @enderror" id="valid_from" name="valid_from" value="{{ old('valid_from') }}" required>
                            @error('valid_from') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="valid_to" class="form-label">Dokument ważny do</label>
                            <input type="date" class="form-control @error('valid_to') is-invalid @enderror" id="valid_to" name="valid_to" value="{{ old('valid_to') }}">
                            <small class="form-text text-muted">Wymagane dla dokumentów okresowych</small>
                            @error('valid_to') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="form-check">
                        <input class="form-check-input @error('is_okresowy') is-invalid @enderror" type="checkbox" id="is_okresowy" name="is_okresowy" value="1" {{ old('is_okresowy', true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_okresowy">
                            Dokument okresowy (z datą ważności do)
                        </label>
                        @error('is_okresowy') <span class="invalid-feedback">{{ $message }}</span> @enderror
                        <small class="form-text text-muted d-block">Odznacz, jeśli dokument jest bezokresowy</small>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="notes" class="form-label">Notatki</label>
                    <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3">{{ old('notes') }}</textarea>
                    @error('notes') <span class="invalid-feedback">{{ $message }}</span> @enderror
                </div>

                <div class="mb-3">
                    <label for="file" class="form-label">Załącz plik</label>
                    <input type="file" class="form-control @error('file') is-invalid @enderror" id="file" name="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.odt,.txt">
                    @error('file') <span class="invalid-feedback">{{ $message }}</span> @enderror
                    <small class="form-text text-muted">Dozwolone formaty: PDF, DOC, DOCX, XLS, XLSX, ODT, TXT (max 10MB)</small>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Dodaj Dokument</button>
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
