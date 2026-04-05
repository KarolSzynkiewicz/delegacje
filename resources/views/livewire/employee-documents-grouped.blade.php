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
                            <option value="wygasa_wkrotce">Wygasa wkrótce</option>
                            <option value="przyszły">Przyszły (jeszcze nieaktywny)</option>
                            <option value="wygasł">Wygasł</option>
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
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th style="width:22%">Dokument</th>
                                <th style="width:11%" class="text-center" title="Czy typ jest wymagany w słowniku oraz czy dziś istnieje choć jeden wpis z okresem nachodzącym na dziś (jak przy przypisaniach).">
                                    Wymaganie <span class="text-muted fw-normal small">(dziś)</span>
                                </th>
                                <th>Wpisy</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($group['documents'] as $docStatus)
                                <tr>
                                    <td class="align-top">
                                        <strong>{{ $docStatus['document']->name }}</strong>
                                        @if($docStatus['document']->description)
                                            <br><small class="text-muted">{{ $docStatus['document']->description }}</small>
                                        @endif
                                    </td>
                                    <td class="align-top text-center">
                                        @if(($docStatus['requiredCompliance'] ?? 'not_required') === 'not_required')
                                            <span class="d-inline-flex flex-column align-items-center gap-1 text-success" title="Typ niewymagany w słowniku — brak obowiązku pokrycia.">
                                                <i class="bi bi-check-circle-fill fs-5"></i>
                                                <span class="small fw-semibold">Niewymagany</span>
                                            </span>
                                        @elseif($docStatus['requiredCompliance'] === 'ok')
                                            <span class="d-inline-flex flex-column align-items-center gap-1 text-success">
                                                <i class="bi bi-check-circle-fill fs-5"></i>
                                                <span class="small fw-semibold">OK</span>
                                            </span>
                                        @else
                                            <span class="d-inline-flex flex-column align-items-center gap-1 text-danger" title="Brak wpisu z okresem obowiązywania obejmującym dzisiejszy dzień.">
                                                <i class="bi bi-x-circle-fill fs-5"></i>
                                                <span class="small fw-semibold">Brak wym. dok.</span>
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(!$docStatus['hasDocument'])
                                            <div class="d-flex align-items-center justify-content-between gap-3 py-1">
                                                <span class="text-muted small">Brak wpisów</span>
                                                <x-ui.button variant="primary" href="{{ route('employee-documents.create', ['employee_id' => $group['employee']->id, 'document_id' => $docStatus['document']->id]) }}" class="btn-sm flex-shrink-0">
                                                    <i class="bi bi-plus-lg me-1"></i>Dodaj
                                                </x-ui.button>
                                            </div>
                                        @else
                                            @foreach($docStatus['instances'] as $inst)
                                                @php
                                                    $ed         = $inst['employeeDocument'];
                                                    $status     = $inst['status'];
                                                    $isExpired  = $status === 'wygasł';
                                                    $isExpiring = $status === 'wygasa_wkrotce';
                                                    $isFuture   = $status === 'przyszły';
                                                    $isValid    = $status === 'ważny';
                                                @endphp
                                                <div @class([
                                                    'd-flex align-items-center justify-content-between gap-3 px-2 py-2 rounded',
                                                    'mb-1'                         => !$loop->last,
                                                    'bg-success bg-opacity-10'    => $isValid,
                                                    'bg-warning bg-opacity-10'    => $isExpiring,
                                                    'bg-secondary bg-opacity-10'  => $isFuture,
                                                    'opacity-60'                   => $isExpired,
                                                ])>
                                                    {{-- lewa: dane instancji --}}
                                                    <div class="d-flex align-items-center flex-grow-1 min-w-0">
                                                        {{-- slot 1: badge statusu (stała szer.) --}}
                                                        <div style="width:9.5rem;flex-shrink:0">
                                                            @if($isExpired)
                                                                <x-ui.badge variant="danger">Wygasł</x-ui.badge>
                                                            @elseif($isExpiring)
                                                                <x-ui.badge variant="warning">Wygasa wkrótce</x-ui.badge>
                                                            @elseif($isFuture)
                                                                <x-ui.badge variant="info">Przyszły</x-ui.badge>
                                                            @else
                                                                <x-ui.badge variant="success">Ważny</x-ui.badge>
                                                            @endif
                                                        </div>

                                                        {{-- slot 2: rodzaj jako tekst (stała szer.) --}}
                                                        <div class="text-muted small" style="width:6.5rem;flex-shrink:0">
                                                            {{ $ed->kind === 'okresowy' ? 'Okresowy' : 'Bezokresowy' }}
                                                        </div>

                                                        {{-- slot 3: daty --}}
                                                        <div class="small text-muted d-flex align-items-center gap-2">
                                                            @if($ed->kind === 'bezokresowy')
                                                                od {{ $ed->valid_from?->format('d.m.Y') ?? '—' }}
                                                            @else
                                                                @if($isExpired)
                                                                    <s>{{ $ed->valid_from?->format('d.m.Y') ?? '—' }} – {{ $ed->valid_to?->format('d.m.Y') ?? '—' }}</s>
                                                                @else
                                                                    {{ $ed->valid_from?->format('d.m.Y') ?? '—' }} – {{ $ed->valid_to?->format('d.m.Y') ?? '—' }}
                                                                @endif
                                                            @endif

                                                            @if($ed->file_path)
                                                                <a href="{{ $ed->file_url }}" target="_blank" class="text-muted text-decoration-none" title="Pobierz plik">
                                                                    <i class="bi bi-paperclip"></i>
                                                                </a>
                                                            @endif
                                                        </div>
                                                    </div>

                                                    {{-- prawa: przyciski --}}
                                                    <div class="d-flex gap-1 flex-shrink-0">
                                                        <a href="{{ route('employee-documents.show', $ed) }}" class="btn btn-sm btn-outline-secondary" title="Zobacz szczegóły">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                        <a href="{{ route('employee-documents.edit', $ed) }}" class="btn btn-sm btn-outline-warning" title="Edytuj">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <form action="{{ route('employee-documents.destroy', $ed) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Usuń" onclick="return confirm('Usunąć ten wpis dokumentu?')">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            @endforeach

                                            @if($docStatus['showAddAnother'] ?? false)
                                                <div class="pt-2">
                                                    <x-ui.button variant="ghost" href="{{ route('employee-documents.create', ['employee_id' => $group['employee']->id, 'document_id' => $docStatus['document']->id]) }}" class="btn-sm">
                                                        <i class="bi bi-plus-lg me-1"></i>Dodaj kolejny wpis
                                                    </x-ui.button>
                                                </div>
                                            @endif
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
