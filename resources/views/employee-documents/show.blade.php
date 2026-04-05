<x-app-layout>
    <x-slot name="header">
        <h2 class="fw-semibold fs-4 mb-0">
            {{ $employeeDocument->document->name ?? 'Dokument pracownika' }}
        </h2>
        <p class="text-muted small mb-0">Podgląd — bez edycji</p>
    </x-slot>

    <div class="py-4">
        <div class="container-xxl">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <x-ui.card>
                        <dl class="row mb-0">
                            <dt class="col-sm-4 text-muted small">ID wpisu</dt>
                            <dd class="col-sm-8"><span class="font-monospace">{{ $employeeDocument->id }}</span></dd>

                            <dt class="col-sm-4 text-muted small">Pracownik</dt>
                            <dd class="col-sm-8">
                                <a href="{{ route('employees.show', $employee) }}" class="text-decoration-none">
                                    {{ $employee->full_name }}
                                </a>
                            </dd>

                            <dt class="col-sm-4 text-muted small">Typ dokumentu</dt>
                            <dd class="col-sm-8">
                                {{ $employeeDocument->document->name ?? '—' }}
                                @if($employeeDocument->document)
                                    <span class="text-muted small">(ID typu: <span class="font-monospace">{{ $employeeDocument->document_id }}</span>)</span>
                                @endif
                            </dd>

                            <dt class="col-sm-4 text-muted small">Rodzaj</dt>
                            <dd class="col-sm-8">
                                {{ $employeeDocument->kind === 'okresowy' ? 'Okresowy' : 'Bezokresowy' }}
                            </dd>

                            <dt class="col-sm-4 text-muted small">Ważny od</dt>
                            <dd class="col-sm-8">
                                {{ $employeeDocument->valid_from?->format('Y-m-d') ?? '—' }}
                            </dd>

                            <dt class="col-sm-4 text-muted small">Ważny do</dt>
                            <dd class="col-sm-8">
                                @if($employeeDocument->kind === 'bezokresowy')
                                    <span class="text-muted">— (bezokresowy)</span>
                                @else
                                    {{ $employeeDocument->valid_to?->format('Y-m-d') ?? '—' }}
                                @endif
                            </dd>

                            <dt class="col-sm-4 text-muted small">Notatki</dt>
                            <dd class="col-sm-8">
                                @if($employeeDocument->notes)
                                    {!! nl2br(e($employeeDocument->notes)) !!}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </dd>

                            <dt class="col-sm-4 text-muted small">Plik</dt>
                            <dd class="col-sm-8">
                                @if($employeeDocument->file_path)
                                    <x-ui.button variant="ghost" href="{{ $employeeDocument->file_url }}" target="_blank" class="btn-sm">
                                        <i class="bi bi-download"></i>
                                        Pobierz załącznik
                                    </x-ui.button>
                                @else
                                    <span class="text-muted">Brak pliku</span>
                                @endif
                            </dd>
                        </dl>

                        <div class="d-flex flex-wrap gap-2 mt-4 pt-3 border-top">
                            <x-ui.button variant="warning" href="{{ route('employee-documents.edit', $employeeDocument) }}">
                                Edytuj
                            </x-ui.button>
                            <x-ui.button variant="ghost" href="{{ route('employee-documents.index') }}">
                                Lista dokumentów
                            </x-ui.button>
                            <x-ui.button variant="ghost" href="{{ route('employees.show', $employee) }}">
                                Karta pracownika
                            </x-ui.button>
                        </div>
                    </x-ui.card>

                    @if($otherDocumentsOfSameType->isNotEmpty())
                        <x-ui.card class="mt-4">
                            <h3 class="h6 fw-semibold mb-3">Inne dokumenty tego typu u tego pracownika</h3>
                            <p class="small text-muted mb-3">
                                Pozostałe wpisy typu „{{ $employeeDocument->document->name ?? '—' }}” — nie obejmują bieżącego podglądu (ID {{ $employeeDocument->id }}).
                            </p>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Status</th>
                                            <th>Rodzaj</th>
                                            <th>Okres</th>
                                            <th>Plik</th>
                                            <th class="text-end">Akcje</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($otherDocumentsOfSameType as $other)
                                            @php $os = $other->getUiValidityStatus(); @endphp
                                            <tr>
                                                <td class="font-monospace small">{{ $other->id }}</td>
                                                <td>
                                                    @if($os === 'wygasł')
                                                        <x-ui.badge variant="danger">Wygasł</x-ui.badge>
                                                    @elseif($os === 'wygasa_wkrotce')
                                                        <x-ui.badge variant="warning">Wygasa wkrótce</x-ui.badge>
                                                    @elseif($os === 'przyszły')
                                                        <x-ui.badge variant="info">Przyszły</x-ui.badge>
                                                    @else
                                                        <x-ui.badge variant="success">Ważny</x-ui.badge>
                                                    @endif
                                                </td>
                                                <td class="text-muted small">
                                                    {{ $other->kind === 'okresowy' ? 'Okresowy' : 'Bezokresowy' }}
                                                </td>
                                                <td class="small text-muted">
                                                    @if($other->kind === 'bezokresowy')
                                                        od {{ $other->valid_from?->format('d.m.Y') ?? '—' }}
                                                    @else
                                                        {{ $other->valid_from?->format('d.m.Y') ?? '—' }} – {{ $other->valid_to?->format('d.m.Y') ?? '—' }}
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($other->file_path)
                                                        <x-ui.button variant="ghost" href="{{ $other->file_url }}" target="_blank" class="btn-sm px-0" title="Pobierz">
                                                            <i class="bi bi-download"></i>
                                                        </x-ui.button>
                                                    @else
                                                        <span class="text-muted small">—</span>
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    <div class="d-inline-flex gap-1">
                                                        <a href="{{ route('employee-documents.show', $other) }}" class="btn btn-sm btn-outline-secondary" title="Zobacz">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                        <a href="{{ route('employee-documents.edit', $other) }}" class="btn btn-sm btn-outline-warning" title="Edytuj">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </x-ui.card>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
