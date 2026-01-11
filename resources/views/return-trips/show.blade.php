<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 text-dark mb-0">Szczegóły Zjazdu</h2>
            <a href="{{ route('return-trips.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Powrót
            </a>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="container-xxl">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold text-dark mb-0">Informacje podstawowe</h5>
                        <div class="d-flex gap-2">
                            @if($returnTrip->status === \App\Enums\LogisticsEventStatus::PLANNED)
                                <a href="{{ route('return-trips.edit', $returnTrip) }}" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-pencil"></i> Edytuj
                                </a>
                                <form method="POST" action="{{ route('return-trips.cancel', $returnTrip) }}" class="d-inline" onsubmit="return confirm('Czy na pewno chcesz anulować ten zjazd?');">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-danger btn-sm">
                                        <i class="bi bi-x-circle"></i> Anuluj Zjazd
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <h6 class="text-muted small mb-1">Data zjazdu</h6>
                            <p class="fw-semibold">{{ $returnTrip->event_date->format('Y-m-d H:i') }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted small mb-1">Status</h6>
                            @php
                                $badgeClass = match($returnTrip->status->value) {
                                    'planned' => 'bg-primary',
                                    'in_progress' => 'bg-info',
                                    'completed' => 'bg-success',
                                    'cancelled' => 'bg-danger',
                                    default => 'bg-secondary'
                                };
                            @endphp
                            <span class="badge {{ $badgeClass }}">{{ $returnTrip->status->label() }}</span>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted small mb-1">Pojazd</h6>
                            <p class="fw-semibold">
                                {{ $returnTrip->vehicle ? $returnTrip->vehicle->registration_number . ' - ' . $returnTrip->vehicle->brand . ' ' . $returnTrip->vehicle->model : '-' }}
                            </p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted small mb-1">Z</h6>
                            <p class="fw-semibold">{{ $returnTrip->fromLocation->name }}</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted small mb-1">Do</h6>
                            <p class="fw-semibold">{{ $returnTrip->toLocation->name }}</p>
                        </div>
                        @if($returnTrip->notes)
                        <div class="col-12">
                            <h6 class="text-muted small mb-1">Notatki</h6>
                            <p>{{ $returnTrip->notes }}</p>
                        </div>
                        @endif
                    </div>

                    <div class="border-top pt-4">
                        <h5 class="fw-bold text-dark mb-4">Uczestnicy ({{ $returnTrip->participants->count() }})</h5>
                        @if($returnTrip->participants->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="text-start">Pracownik</th>
                                            <th class="text-start">Przypisanie</th>
                                            <th class="text-start">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($returnTrip->participants as $participant)
                                            <tr>
                                                <td>{{ $participant->employee->full_name }}</td>
                                                <td>
                                                    @if($participant->assignment)
                                                        {{ class_basename($participant->assignment) }} #{{ $participant->assignment_id }}
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary">{{ ucfirst($participant->status) }}</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-muted">Brak uczestników</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
