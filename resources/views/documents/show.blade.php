<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Dokument: {{ $document->name }}">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('documents.index') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
            <x-slot name="right">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('documents.edit', $document) }}"
                    routeName="documents.edit"
                    action="edit"
                >
                    Edytuj
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>
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
</x-app-layout>
