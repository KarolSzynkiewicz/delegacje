@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="row">
        <div class="col-md-12">
            <h1 class="mb-4">Raporty z Delegacji</h1>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Dostępne Raporty</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">TODO: Wyświetl listę dostępnych raportów</p>
                    
                    <div class="alert alert-info">
                        <strong>Funkcjonalności do implementacji:</strong>
                        <ul class="mb-0">
                            <li>Raport podsumowania delegacji (liczba, czas trwania, pracownicy)</li>
                            <li>Raport godzin pracowników (razem godzin, nadgodziny, obecność)</li>
                            <li>Raport statusu projektów (postęp, delegacje na projekt, oś czasu)</li>
                            <li>Eksport do PDF, Excel, HTML</li>
                            <li>Filtrowanie po dacie, pracowniku, projekcie</li>
                        </ul>
                    </div>

                    <a href="{{ route('reports.create') }}" class="btn btn-primary">
                        Generuj Nowy Raport
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
