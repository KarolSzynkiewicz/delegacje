@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row align-items-center" style="min-height: 60vh;">
        <div class="col-md-8 offset-md-2 text-center">
            <h1 class="display-4 mb-4">Stocznia</h1>
            <p class="lead mb-4">System zarządzania logistyką i delegowaniem pracowników</p>
            
            <div class="row mt-5">
                <div class="col-md-6 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Lokalizacje</h5>
                            <p class="card-text">Zarządzaj miejscami pracy i stoczniami</p>
                            <a href="{{ route('locations.index') }}" class="btn btn-primary">Przejdź</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Projekty</h5>
                            <p class="card-text">Twórz i zarządzaj projektami</p>
                            <a href="{{ route('projects.index') }}" class="btn btn-primary">Przejdź</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-6 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Delegacje</h5>
                            <p class="card-text">Przydzielaj pracowników do projektów</p>
                            <a href="{{ route('delegations.index') }}" class="btn btn-primary">Przejdź</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Zapisy Czasu Pracy</h5>
                            <p class="card-text">Rejestruj czas pracy pracowników</p>
                            <a href="{{ route('time_logs.index') }}" class="btn btn-primary">Przejdź</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-4 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Pracownicy</h5>
                            <p class="card-text">Zarządzaj pracownikami i ich dokumentami</p>
                            <a href="{{ route('employees.index') }}" class="btn btn-primary">Przejdź</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Akomodacje</h5>
                            <p class="card-text">Zarządzaj dostępnymi mieszkaniami</p>
                            <a href="{{ route('accommodations.index') }}" class="btn btn-primary">Przejdź</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Pojazdy</h5>
                            <p class="card-text">Zarządzaj flotą pojazdów</p>
                            <a href="{{ route('vehicles.index') }}" class="btn btn-primary">Przejdź</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
