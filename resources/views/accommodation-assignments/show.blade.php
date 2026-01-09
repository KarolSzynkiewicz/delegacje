<x-app-layout>
    <div class="row justify-content-center">
        <div class="col-lg-10">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-bottom">
                        <h2 class="h4 fw-semibold text-dark mb-0">Szczegóły Przypisania Mieszkania</h2>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <div class="col-md-6 mb-3">
                                <dt class="fw-semibold mb-1">Pracownik:</dt>
                                <dd>
                                    <a href="{{ route('employees.show', $accommodationAssignment->employee) }}" class="text-primary text-decoration-none">
                                        {{ $accommodationAssignment->employee->full_name }}
                                    </a>
                                </dd>
                            </div>
                            <div class="col-md-6 mb-3">
                                <dt class="fw-semibold mb-1">Mieszkanie:</dt>
                                <dd>
                                    <a href="{{ route('accommodations.show', $accommodationAssignment->accommodation) }}" class="text-primary text-decoration-none">
                                        {{ $accommodationAssignment->accommodation->name }} - {{ $accommodationAssignment->accommodation->city }}
                                    </a>
                                </dd>
                            </div>
                            <div class="col-md-6 mb-3">
                                <dt class="fw-semibold mb-1">Data Rozpoczęcia:</dt>
                                <dd>{{ $accommodationAssignment->start_date->format('Y-m-d') }}</dd>
                            </div>
                            <div class="col-md-6 mb-3">
                                <dt class="fw-semibold mb-1">Data Zakończenia:</dt>
                                <dd>{{ $accommodationAssignment->end_date ? $accommodationAssignment->end_date->format('Y-m-d') : 'Bieżące' }}</dd>
                            </div>
                            @if($accommodationAssignment->notes)
                            <div class="col-12 mb-3">
                                <dt class="fw-semibold mb-1">Uwagi:</dt>
                                <dd>{{ $accommodationAssignment->notes }}</dd>
                            </div>
                            @endif
                        </dl>

                        <div class="mt-4 pt-3 border-top">
                            <a href="{{ route('accommodation-assignments.edit', $accommodationAssignment) }}" class="btn btn-primary me-2">
                                <i class="bi bi-pencil me-1"></i> Edytuj
                            </a>
                            <a href="{{ route('employees.accommodations.index', $accommodationAssignment->employee_id) }}" class="btn btn-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Powrót
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
