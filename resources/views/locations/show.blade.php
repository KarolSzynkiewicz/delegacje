@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <h1>{{ $location->name }}</h1>

            <div class="card">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h5>Adres</h5>
                            <p>{{ $location->address }}</p>
                        </div>
                        <div class="col-md-6">
                            <h5>Miasto</h5>
                            <p>{{ $location->city ?? '-' }}</p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h5>Kod Pocztowy</h5>
                            <p>{{ $location->postal_code ?? '-' }}</p>
                        </div>
                        <div class="col-md-6">
                            <h5>Osoba Kontaktowa</h5>
                            <p>{{ $location->contact_person ?? '-' }}</p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h5>Telefon</h5>
                            <p>{{ $location->phone ?? '-' }}</p>
                        </div>
                        <div class="col-md-6">
                            <h5>Email</h5>
                            <p>{{ $location->email ?? '-' }}</p>
                        </div>
                    </div>

                    @if ($location->description)
                        <div class="mb-3">
                            <h5>Opis</h5>
                            <p>{{ $location->description }}</p>
                        </div>
                    @endif

                    <div class="d-flex gap-2">
                        <a href="{{ route('locations.edit', $location) }}" class="btn btn-warning">Edytuj</a>
                        <a href="{{ route('locations.index') }}" class="btn btn-secondary">Wróć do Listy</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
