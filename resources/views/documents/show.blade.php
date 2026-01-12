<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 mb-0">Dokument: {{ $document->name }}</h2>
            <div class="d-flex gap-2">
                <x-ui.button variant="warning" href="{{ route('documents.edit', $document) }}">Edytuj</x-ui.button>
                <x-ui.button variant="ghost" href="{{ route('documents.index') }}">Wróć do listy</x-ui.button>
            </div>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="container-xxl">
            <x-ui.card class="mb-4">
                <h5 class="fw-bold mb-3">Opis</h5>
                <p>{{ $document->description ?? 'Brak opisu' }}</p>
                <hr style="border-color: var(--glass-border);">
                <h5 class="fw-bold mb-3">Dokument okresowy</h5>
                <p>{{ $document->is_periodic ? 'Tak' : 'Nie' }}</p>
            </x-ui.card>

            <x-ui.card label="Przypisane dokumenty pracowników ({{ $document->employee_documents_count }})">
                @if($document->employeeDocuments->count() > 0)
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Pracownik</th>
                                    <th>Ważny od</th>
                                    <th>Ważny do</th>
                                    <th>Status</th>
                                    <th>Akcje</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($document->employeeDocuments as $employeeDocument)
                                    <tr>
                                        <td>
                                            <a href="{{ route('employees.show', $employeeDocument->employee) }}" class="text-decoration-none">
                                                {{ $employeeDocument->employee->full_name }}
                                            </a>
                                        </td>
                                        <td>{{ $employeeDocument->valid_from->format('Y-m-d') }}</td>
                                        <td>
                                            @if($employeeDocument->kind === 'bezokresowy')
                                                <span class="text-muted">Bezokresowy</span>
                                            @else
                                                {{ $employeeDocument->valid_to ? $employeeDocument->valid_to->format('Y-m-d') : '-' }}
                                            @endif
                                        </td>
                                        <td>
                                            @if($employeeDocument->kind === 'bezokresowy')
                                                <x-ui.badge variant="success">Ważny</x-ui.badge>
                                            @elseif($employeeDocument->isExpired())
                                                <x-ui.badge variant="danger">Wygasł</x-ui.badge>
                                            @elseif($employeeDocument->isExpiringSoon())
                                                <x-ui.badge variant="warning">Wygasa wkrótce</x-ui.badge>
                                            @else
                                                <x-ui.badge variant="success">Ważny</x-ui.badge>
                                            @endif
                                        </td>
                                        <td>
                                            <x-ui.button variant="warning" href="{{ route('employees.employee-documents.edit', [$employeeDocument->employee, $employeeDocument]) }}" class="btn-sm">Edytuj</x-ui.button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted">Brak przypisanych dokumentów</p>
                @endif
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
