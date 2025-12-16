@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <h1 class="mb-4">Generuj Nowy Raport</h1>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Parametry Raportu</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('reports.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="report_type" class="form-label">Typ Raportu</label>
                            <select class="form-control @error('report_type') is-invalid @enderror" 
                                    id="report_type" name="report_type" required>
                                <option value="">-- Wybierz typ raportu --</option>
                                <option value="delegation_summary">Podsumowanie Delegacji</option>
                                <option value="employee_hours">Godziny Pracowników</option>
                                <option value="project_status">Status Projektów</option>
                            </select>
                            @error('report_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="start_date" class="form-label">Data Początkowa</label>
                            <input type="date" class="form-control @error('start_date') is-invalid @enderror" 
                                   id="start_date" name="start_date" required>
                            @error('start_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="end_date" class="form-label">Data Końcowa</label>
                            <input type="date" class="form-control @error('end_date') is-invalid @enderror" 
                                   id="end_date" name="end_date" required>
                            @error('end_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="format" class="form-label">Format Eksportu</label>
                            <select class="form-control @error('format') is-invalid @enderror" 
                                    id="format" name="format" required>
                                <option value="">-- Wybierz format --</option>
                                <option value="pdf">PDF</option>
                                <option value="excel">Excel</option>
                                <option value="html">HTML</option>
                            </select>
                            @error('format')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="{{ route('reports.index') }}" class="btn btn-secondary">Anuluj</a>
                            <button type="submit" class="btn btn-primary">Generuj Raport</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="alert alert-warning mt-4">
                <strong>Uwaga:</strong> Ta funkcjonalność jest w fazie rozwoju. 
                Szczegóły implementacji znajdują się w pliku <code>ReportController.php</code>.
            </div>
        </div>
    </div>
</div>
@endsection
