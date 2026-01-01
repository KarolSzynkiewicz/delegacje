@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <h1>Pojazd: {{ $vehicle->registration_number }}</h1>

            <div class="card">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h5>Numer Rejestracyjny</h5>
                            <p><strong>{{ $vehicle->registration_number }}</strong></p>
                        </div>
                        <div class="col-md-6">
                            <h5>Stan Techniczny</h5>
                            <p>
                                <span class="badge bg-{{ $vehicle->technical_condition == 'excellent' ? 'success' : ($vehicle->technical_condition == 'good' ? 'info' : ($vehicle->technical_condition == 'fair' ? 'warning' : 'danger')) }}">
                                    {{ ucfirst($vehicle->technical_condition) }}
                                </span>
                            </p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h5>Marka</h5>
                            <p>{{ $vehicle->brand ?? '-' }}</p>
                        </div>
                        <div class="col-md-6">
                            <h5>Model</h5>
                            <p>{{ $vehicle->model ?? '-' }}</p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h5>Pojemność</h5>
                            <p>{{ $vehicle->capacity ?? '-' }} osób</p>
                        </div>
                        <div class="col-md-6">
                            <h5>Przegląd Ważny Do</h5>
                            <p>
                                @if ($vehicle->inspection_valid_to)
                                    <span class="badge bg-{{ $vehicle->inspection_valid_to < now() ? 'danger' : 'success' }}">
                                        {{ $vehicle->inspection_valid_to->format('Y-m-d') }}
                                    </span>
                                @else
                                    -
                                @endif
                            </p>
                        </div>
                    </div>

                    @if ($vehicle->notes)
                        <div class="mb-3">
                            <h5>Notatki</h5>
                            <p>{{ $vehicle->notes }}</p>
                        </div>
                    @endif

                    <div class="d-flex gap-2">
                        <a href="{{ route('vehicles.edit', $vehicle) }}" class="btn btn-warning">Edytuj</a>
                        <a href="{{ route('vehicles.index') }}" class="btn btn-secondary">Wróć do Listy</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
