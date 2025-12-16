@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <h1>Zapis Czasu Pracy #{{ $timeLog->id }}</h1>

            <div class="card">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h5>Delegacja</h5>
                            <p>{{ $timeLog->delegation->id }}</p>
                        </div>
                        <div class="col-md-6">
                            <h5>Pracownik</h5>
                            <p>{{ $timeLog->delegation->employee->name }}</p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h5>Data Rozpoczęcia</h5>
                            <p>{{ $timeLog->start_time->format('Y-m-d H:i') }}</p>
                        </div>
                        <div class="col-md-6">
                            <h5>Data Zakończenia</h5>
                            <p>{{ $timeLog->end_time?->format('Y-m-d H:i') ?? '-' }}</p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h5>Godziny Pracy</h5>
                            <p>{{ $timeLog->hours_worked ?? '-' }}</p>
                        </div>
                    </div>

                    @if ($timeLog->notes)
                        <div class="mb-3">
                            <h5>Notatki</h5>
                            <p>{{ $timeLog->notes }}</p>
                        </div>
                    @endif

                    <div class="d-flex gap-2">
                        <a href="{{ route('time_logs.edit', $timeLog) }}" class="btn btn-warning">Edytuj</a>
                        <a href="{{ route('time_logs.index') }}" class="btn btn-secondary">Wróć do Listy</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
