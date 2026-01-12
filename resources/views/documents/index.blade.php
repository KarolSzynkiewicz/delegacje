<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 mb-0">Wymagania formalne</h2>
            <x-ui.button variant="primary" href="{{ route('documents.create') }}">
                <i class="bi bi-plus-circle"></i> Dodaj Dokument
            </x-ui.button>
        </div>
    </x-slot>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <x-ui.card>
        @if($documents->count() > 0)
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th class="text-start">Nazwa</th>
                            <th class="text-start">Opis</th>
                            <th class="text-start">Okresowy</th>
                            <th class="text-start">Wymagane</th>
                            <th class="text-start">Liczba przypisań</th>
                            <th class="text-end">Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($documents as $document)
                            <tr>
                                <td class="fw-medium">{{ $document->name }}</td>
                                <td>{{ $document->description ?? '-' }}</td>
                                <td>{{ $document->is_periodic ? 'Tak' : 'Nie' }}</td>
                                <td>
                                    @if($document->is_required)
                                        <x-ui.badge variant="danger">Tak</x-ui.badge>
                                    @else
                                        <x-ui.badge variant="info">Nie</x-ui.badge>
                                    @endif
                                </td>
                                <td>{{ $document->employee_documents_count }}</td>
                                <td class="text-end">
                                    <x-action-buttons
                                        viewRoute="{{ route('documents.show', $document) }}"
                                        editRoute="{{ route('documents.edit', $document) }}"
                                        deleteRoute="{{ route('documents.destroy', $document) }}"
                                        deleteMessage="Czy na pewno chcesz usunąć ten dokument?"
                                    />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($documents->hasPages())
                <div class="mt-3">
                    {{ $documents->links() }}
                </div>
            @endif
        @else
            <div class="text-center py-5">
                <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
                <p class="text-muted mb-3">Brak dokumentów w systemie.</p>
                <x-ui.button variant="primary" href="{{ route('documents.create') }}">
                    <i class="bi bi-plus-circle"></i> Dodaj pierwszy dokument
                </x-ui.button>
            </div>
        @endif
    </x-ui.card>
</x-app-layout>
