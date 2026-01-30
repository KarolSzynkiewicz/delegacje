<div>
    <!-- Filtry -->
            <x-ui.card class="mb-4">
                <div class="row g-3">
                    <div class="col-md-3">
                        <x-ui.input 
                            type="text" 
                            name="searchEmployee" 
                            label="Szukaj pracownika"
                            wire:model.live.debounce.300ms="searchEmployee"
                            placeholder="Imię lub nazwisko..."
                        />
                    </div>
                    <div class="col-md-2">
                        <x-ui.input 
                            type="select" 
                            name="filterStatus" 
                            label="Status dokumentu"
                            wire:model.live="filterStatus"
                        >
                            <option value="">Wszystkie</option>
                            <option value="brak">Brak dokumentu</option>
                            <option value="has">Ma dokument</option>
                            <option value="ważny">Ważny</option>
                            <option value="wygasł">Wygasł</option>
                            <option value="wygasa_wkrotce">Wygasa wkrótce</option>
                        </x-ui.input>
                    </div>
                    <div class="col-md-2">
                        <x-ui.input 
                            type="select" 
                            name="filterDocument" 
                            label="Typ dokumentu"
                            wire:model.live="filterDocument"
                        >
                            <option value="">Wszystkie dokumenty</option>
                            @foreach($allDocuments as $doc)
                                <option value="{{ $doc->id }}">{{ $doc->name }}</option>
                            @endforeach
                        </x-ui.input>
                    </div>
                    <div class="col-md-2">
                        <x-ui.input 
                            type="select" 
                            name="filterRequired" 
                            label="Wymagane"
                            wire:model.live="filterRequired"
                        >
                            <option value="">Wszystkie</option>
                            <option value="required">Wymagane</option>
                            <option value="not_required">Niewymagane</option>
                        </x-ui.input>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <x-ui.button variant="ghost" wire:click="resetFilters" class="w-100">
                            Wyczyść filtry
                        </x-ui.button>
                    </div>
                </div>
            </x-ui.card>

        @if (session('success'))
            <x-ui.alert variant="success" title="Sukces">
                {{ session('success') }}
            </x-ui.alert>
        @endif

        @if(empty($groupedData))
            <x-ui.alert variant="info" title="Brak wyników">
                Spróbuj zmienić filtry.
            </x-ui.alert>
        @endif

        @foreach($groupedData as $group)
            <x-ui.card class="mb-4">
                <div class="mb-3">
                    <h5 class="mb-0">
                        <a href="{{ route('employees.show', $group['employee']) }}" class="text-decoration-none">
                            {{ $group['employee']->full_name }}
                        </a>
                    </h5>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Dokument</th>
                                <th>Wymagany</th>
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
                                <tr>
                                    <td>
                                        <strong>{{ $docStatus['document']->name }}</strong>
                                        @if($docStatus['document']->description)
                                            <br><small class="text-muted">{{ $docStatus['document']->description }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($docStatus['document']->is_required ?? false)
                                            <x-ui.badge variant="danger">Wymagany</x-ui.badge>
                                        @else
                                            <x-ui.badge variant="info">Niewymagany</x-ui.badge>
                                        @endif
                                    </td>
                                    <td>
                                        @if($docStatus['hasDocument'])
                                            <x-ui.badge variant="{{ $docStatus['employeeDocument']->kind === 'bezokresowy' ? 'info' : 'info' }}">
                                                {{ $docStatus['employeeDocument']->kind === 'okresowy' ? 'Okresowy' : 'Bezokresowy' }}
                                            </x-ui.badge>
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
                                            <x-ui.button variant="ghost" href="{{ $docStatus['employeeDocument']->file_url }}" target="_blank" class="btn-sm" title="Pobierz plik">
                                                <i class="bi bi-download"></i>
                                            </x-ui.button>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($docStatus['status'] === 'brak')
                                            <x-ui.badge variant="danger">Brak</x-ui.badge>
                                        @elseif($docStatus['status'] === 'wygasł')
                                            <x-ui.badge variant="danger">Wygasł</x-ui.badge>
                                        @elseif($docStatus['status'] === 'wygasa_wkrotce')
                                            <x-ui.badge variant="warning">Wygasa wkrótce</x-ui.badge>
                                        @else
                                            <x-ui.badge variant="success">Ważny</x-ui.badge>
                                        @endif
                                    </td>
                                    <td>
                                        @if($docStatus['hasDocument'])
                                            <div class="d-flex gap-1">
                                                <x-ui.button variant="warning" href="{{ route('employee-documents.edit', $docStatus['employeeDocument']) }}" class="btn-sm">Edytuj</x-ui.button>
                                                <form action="{{ route('employee-documents.destroy', $docStatus['employeeDocument']) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <x-ui.button variant="danger" type="submit" class="btn-sm" onclick="return confirm('Czy na pewno chcesz usunąć ten dokument?')">Usuń</x-ui.button>
                                                </form>
                                            </div>
                                        @else
                                            <x-ui.button variant="primary" href="{{ route('employee-documents.create', ['employee_id' => $group['employee']->id, 'document_id' => $docStatus['document']->id]) }}" class="btn-sm">Dodaj</x-ui.button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
        @endforeach

        @if(isset($employees) && $employees->hasPages())
            <div class="mt-4">
                {{ $employees->links() }}
            </div>
        @elseif(isset($employees))
            <div class="mt-4">
                <p class="small text-muted mb-0">
                    Pokazano <span class="fw-semibold">{{ $employees->total() }}</span> wyników
                </p>
            </div>
        @endif
</div>
