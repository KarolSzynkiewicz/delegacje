<div>
    <div class="container">
        <div class="row mb-4">
            <div class="col-md-12">
                <h1 class="mb-3">Dokumenty Pracowników</h1>

                <!-- Filtry -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="searchEmployee" class="form-label">Szukaj pracownika</label>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    id="searchEmployee" 
                                    wire:model.live.debounce.300ms="searchEmployee"
                                    placeholder="Imię lub nazwisko..."
                                >
                            </div>
                            <div class="col-md-3">
                                <label for="filterStatus" class="form-label">Status dokumentu</label>
                                <select 
                                    class="form-select" 
                                    id="filterStatus" 
                                    wire:model.live="filterStatus"
                                >
                                    <option value="">Wszystkie</option>
                                    <option value="brak">Brak dokumentu</option>
                                    <option value="has">Ma dokument</option>
                                    <option value="ważny">Ważny</option>
                                    <option value="wygasł">Wygasł</option>
                                    <option value="wygasa_wkrotce">Wygasa wkrótce</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="filterDocument" class="form-label">Typ dokumentu</label>
                                <select 
                                    class="form-select" 
                                    id="filterDocument" 
                                    wire:model.live="filterDocument"
                                >
                                    <option value="">Wszystkie dokumenty</option>
                                    @foreach($allDocuments as $doc)
                                        <option value="{{ $doc->id }}">{{ $doc->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button 
                                    type="button" 
                                    class="btn btn-outline-secondary w-100" 
                                    wire:click="resetFilters"
                                >
                                    Wyczyść filtry
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if(empty($groupedData))
                    <div class="alert alert-info">
                        <strong>Brak wyników</strong> - spróbuj zmienić filtry.
                    </div>
                @endif

                @foreach($groupedData as $group)
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">
                                <a href="{{ route('employees.show', $group['employee']) }}" class="text-decoration-none">
                                    {{ $group['employee']->full_name }}
                                </a>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>Dokument</th>
                                            <th>Rodzaj</th>
                                            <th>Ważny od</th>
                                            <th>Ważny do</th>
                                            <th>Plik</th>
                                            <th>Status</th>
                                            <th>Akcje</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($group['documents'] as $docStatus)
                                            <tr class="{{ !$docStatus['hasDocument'] ? 'table-secondary' : '' }}">
                                                <td>
                                                    <strong>{{ $docStatus['document']->name }}</strong>
                                                    @if($docStatus['document']->is_required ?? false)
                                                        <span class="badge bg-danger ms-2">Wymagany</span>
                                                    @else
                                                        <span class="badge bg-secondary ms-2">Niewymagany</span>
                                                    @endif
                                                    @if($docStatus['document']->description)
                                                        <br><small class="text-muted">{{ $docStatus['document']->description }}</small>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($docStatus['hasDocument'])
                                                        <span class="badge bg-{{ $docStatus['employeeDocument']->kind === 'bezokresowy' ? 'info' : 'secondary' }}">
                                                            {{ $docStatus['employeeDocument']->kind === 'okresowy' ? 'Okresowy' : 'Bezokresowy' }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($docStatus['hasDocument'])
                                                        {{ $docStatus['employeeDocument']->valid_from ? $docStatus['employeeDocument']->valid_from->format('Y-m-d') : '-' }}
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($docStatus['hasDocument'])
                                                        @if($docStatus['employeeDocument']->kind === 'bezokresowy')
                                                            <span class="text-muted">Bezokresowy</span>
                                                        @else
                                                            {{ $docStatus['employeeDocument']->valid_to ? $docStatus['employeeDocument']->valid_to->format('Y-m-d') : '-' }}
                                                        @endif
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($docStatus['hasDocument'] && $docStatus['employeeDocument']->file_path)
                                                        <a href="{{ $docStatus['employeeDocument']->file_url }}" target="_blank" class="btn btn-sm btn-info" title="Pobierz plik">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-download" viewBox="0 0 16 16">
                                                                <path d="M.5 9.9a.5.5 0 0 1 .5.5h2.5a.5.5 0 0 1 0 1H3a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h2.717a.5.5 0 0 1 .357.135l2.415 2.414A.5.5 0 0 1 8 6.5v3.9a.5.5 0 0 1 .5.5h2.5a.5.5 0 0 1 0 1H9a1 1 0 0 1-1-1V6.5a.5.5 0 0 1 .146-.354l2.415-2.414A.5.5 0 0 1 11 3.5v-.5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v6.5a.5.5 0 0 1-.5.5h-2.5a.5.5 0 0 1 0-1H13V4h-2v.5a.5.5 0 0 1-.5.5H9.5a.5.5 0 0 1-.5-.5V3H6.5a.5.5 0 0 1-.5.5v6.9z"/>
                                                                <path d="M14 10.5a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1 0-1h2a.5.5 0 0 1 .5.5zm-2.5-2a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5z"/>
                                                            </svg>
                                                        </a>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($docStatus['status'] === 'brak')
                                                        <span class="badge bg-danger">Brak</span>
                                                    @elseif($docStatus['status'] === 'wygasł')
                                                        <span class="badge bg-danger">Wygasł</span>
                                                    @elseif($docStatus['status'] === 'wygasa_wkrotce')
                                                        <span class="badge bg-warning">Wygasa wkrótce</span>
                                                    @else
                                                        <span class="badge bg-success">Ważny</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($docStatus['hasDocument'])
                                                        <a href="{{ route('employees.employee-documents.edit', [$group['employee'], $docStatus['employeeDocument']]) }}" class="btn btn-sm btn-warning me-1">Edytuj</a>
                                                        <form action="{{ route('employees.employee-documents.destroy', [$group['employee'], $docStatus['employeeDocument']]) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Czy na pewno chcesz usunąć ten dokument?')">Usuń</button>
                                                        </form>
                                                    @else
                                                        <a href="{{ route('employees.employee-documents.create', $group['employee']) }}?document_id={{ $docStatus['document']->id }}" class="btn btn-sm btn-primary">Dodaj</a>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
