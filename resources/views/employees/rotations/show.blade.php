<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 mb-0 fw-bold text-dark">
                Rotacja: {{ $employee->full_name }}
            </h2>
            <div class="d-flex gap-2">
                <a href="{{ route('employees.rotations.edit', [$employee, $rotation]) }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-pencil"></i> Edytuj
                </a>
                <a href="{{ route('employees.rotations.index', $employee) }}" class="btn btn-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Powrót
                </a>
            </div>
        </div>
    </x-slot>

    <div class="container-xxl py-4">
        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h5 class="card-title fw-bold mb-4">Szczegóły rotacji</h5>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <strong class="text-muted">Pracownik:</strong>
                            </div>
                            <div class="col-md-8">
                                <a href="{{ route('employees.show', $employee) }}" class="text-decoration-none">
                                    {{ $employee->full_name }}
                                </a>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <strong class="text-muted">Data rozpoczęcia:</strong>
                            </div>
                            <div class="col-md-8">
                                {{ $rotation->start_date->format('d.m.Y') }}
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <strong class="text-muted">Data zakończenia:</strong>
                            </div>
                            <div class="col-md-8">
                                {{ $rotation->end_date->format('d.m.Y') }}
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <strong class="text-muted">Status:</strong>
                            </div>
                            <div class="col-md-8">
                                @php
                                    $today = now()->toDateString();
                                    $isActive = $rotation->start_date->toDateString() <= $today && 
                                                $rotation->end_date->toDateString() >= $today;
                                    $isCompleted = $rotation->end_date->toDateString() < $today;
                                    $isScheduled = $rotation->start_date->toDateString() > $today;
                                    $isCancelled = $rotation->status === 'cancelled';
                                @endphp
                                @if($isCancelled)
                                    <span class="badge bg-danger">Anulowana</span>
                                @elseif($isActive)
                                    <span class="badge bg-success">Aktywna</span>
                                @elseif($isCompleted)
                                    <span class="badge bg-secondary">Zakończona</span>
                                @elseif($isScheduled)
                                    <span class="badge bg-info">Zaplanowana</span>
                                @else
                                    <span class="badge bg-secondary">Nieznany</span>
                                @endif
                            </div>
                        </div>

                        @if($rotation->notes)
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <strong class="text-muted">Uwagi:</strong>
                            </div>
                            <div class="col-md-8">
                                {{ $rotation->notes }}
                            </div>
                        </div>
                        @endif

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <strong class="text-muted">Utworzono:</strong>
                            </div>
                            <div class="col-md-8">
                                {{ $rotation->created_at->format('d.m.Y H:i') }}
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <strong class="text-muted">Zaktualizowano:</strong>
                            </div>
                            <div class="col-md-8">
                                {{ $rotation->updated_at->format('d.m.Y H:i') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
