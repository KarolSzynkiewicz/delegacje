@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <h1>Akomodacja: {{ $accommodation->name }}</h1>

            <div class="card">
                <div class="card-body">
                    @if($accommodation->image_path)
                        <div class="mb-4 text-center">
                            <img src="{{ $accommodation->image_url }}" alt="{{ $accommodation->name }}" class="img-fluid rounded" style="max-width: 500px; max-height: 400px; object-fit: cover;">
                        </div>
                    @endif

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h5>Nazwa</h5>
                            <p>{{ $accommodation->name }}</p>
                        </div>
                        <div class="col-md-6">
                            <h5>Pojemność</h5>
                            <p>{{ $accommodation->capacity }} osób</p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h5>Adres</h5>
                            <p>{{ $accommodation->address }}</p>
                        </div>
                        <div class="col-md-6">
                            <h5>Miasto</h5>
                            <p>{{ $accommodation->city ?? '-' }}</p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <h5>Kod Pocztowy</h5>
                            <p>{{ $accommodation->postal_code ?? '-' }}</p>
                        </div>
                    </div>

                    @if ($accommodation->description)
                        <div class="mb-3">
                            <h5>Opis</h5>
                            <p>{{ $accommodation->description }}</p>
                        </div>
                    @endif

                    <div class="d-flex gap-2">
                        <a href="{{ route('accommodations.edit', $accommodation) }}" class="btn btn-warning">Edytuj</a>
                        <a href="{{ route('accommodations.index') }}" class="btn btn-secondary">Wróć do Listy</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
