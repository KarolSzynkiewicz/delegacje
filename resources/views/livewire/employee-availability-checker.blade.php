<div>
    @if($employeeId && $startDate)
        <div class="card border-0 shadow-sm mb-3 {{ $isAvailable === null ? 'bg-light' : ($isAvailable ? 'border-success' : 'border-danger') }}">
            <div class="card-body">
                <h5 class="card-title fw-semibold mb-3">Status Dostępności</h5>
                
                @if($isAvailable === null)
                    <p class="small text-muted mb-0">
                        <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                        Sprawdzanie dostępności...
                    </p>
                @elseif($isAvailable)
                    <div class="alert alert-success mb-0">
                        <i class="bi bi-check-circle me-2"></i>
                        <strong>Pracownik jest dostępny</strong> w wybranym terminie.
                    </div>
                @else
                    <div class="alert alert-danger mb-3">
                        <i class="bi bi-x-circle me-2"></i>
                        <strong>Pracownik niedostępny!</strong>
                    </div>
                    
                    @if($availabilityStatus && !empty($availabilityStatus['reasons']))
                        <div class="mb-3">
                            <h6 class="small fw-semibold mb-2">Powody:</h6>
                            <ul class="list-unstyled mb-0 small">
                                @foreach($availabilityStatus['reasons'] as $reason)
                                    <li class="mb-1">
                                        <i class="bi bi-dash text-danger me-2"></i>{{ $reason }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    
                    @if(!empty($missingDocuments))
                        <div class="alert alert-warning mb-3">
                            <h6 class="alert-heading small fw-semibold mb-2">
                                Problemy z wymaganymi dokumentami ({{ count($missingDocuments) }}):
                            </h6>
                            <div class="list-group list-group-flush">
                                @foreach($missingDocuments as $doc)
                                    <div class="list-group-item bg-transparent border-bottom px-0 py-2">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                    <span class="fw-semibold text-dark">{{ $doc['document_name'] ?? 'Nieznany dokument' }}</span>
                                                    @if(isset($doc['is_required']) && $doc['is_required'])
                                                        <span class="badge bg-danger">Wymagane</span>
                                                    @endif
                                                </div>
                                                <div class="small text-muted mt-1">
                                                    <span>{{ $doc['problem'] ?? 'Brak dokumentu' }}</span>
                                                    @if(isset($doc['valid_from']) || isset($doc['valid_to']))
                                                        <span class="ms-2">
                                                            @if(isset($doc['valid_from']))
                                                                Od: {{ $doc['valid_from'] }}
                                                            @endif
                                                            @if(isset($doc['valid_to']))
                                                                Do: {{ $doc['valid_to'] }}
                                                            @endif
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                            @if(isset($doc['employee_id']))
                                                <a href="{{ route('employees.employee-documents.index', $doc['employee_id']) }}" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-pencil"></i> Edytuj
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    
                    @if($availabilityStatus && isset($availabilityStatus['rotation_conflicts']) && !empty($availabilityStatus['rotation_conflicts']))
                        <div class="alert alert-info mb-3">
                            <h6 class="alert-heading small fw-semibold mb-2">Konflikty z rotacjami:</h6>
                            <ul class="list-unstyled mb-0 small">
                                @foreach($availabilityStatus['rotation_conflicts'] as $conflict)
                                    <li class="mb-1">
                                        <i class="bi bi-calendar-x me-2"></i>{{ $conflict }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    
                    @if($availabilityStatus && isset($availabilityStatus['assignment_conflicts']) && !empty($availabilityStatus['assignment_conflicts']))
                        <div class="alert alert-warning mb-0">
                            <h6 class="alert-heading small fw-semibold mb-2">Konflikty z przypisaniami:</h6>
                            <ul class="list-unstyled mb-0 small">
                                @foreach($availabilityStatus['assignment_conflicts'] as $conflict)
                                    <li class="mb-1">
                                        <i class="bi bi-exclamation-triangle me-2"></i>{{ $conflict }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    @else
        <div class="card border-0 shadow-sm bg-light">
            <div class="card-body">
                <p class="small text-muted mb-0">
                    <i class="bi bi-info-circle me-2"></i>
                    Wybierz pracownika i daty, aby sprawdzić dostępność.
                </p>
            </div>
        </div>
    @endif
</div>
