<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Wymagania formalne">
            <x-slot name="right">
                <x-ui.button 
                    variant="primary" 
                    href="{{ route('documents.create') }}"
                    routeName="documents.create"
                    action="create"
                >
                    Dodaj Dokument
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    @if (session('success'))
        <x-alert type="success" dismissible icon="check-circle">
            {{ session('success') }}
        </x-alert>
    @endif

    @if (session('error'))
        <x-alert type="danger" dismissible icon="exclamation-triangle">
            {{ session('error') }}
        </x-alert>
    @endif

    <x-ui.card>
        @if($documents->count() > 0)
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Nazwa</th>
                            <th>Opis</th>
                            <th>Okresowy</th>
                            <th>Wymagane</th>
                            <th>Liczba przypisań</th>
                            <th>Akcje</th>
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
                                <td>
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
            <x-ui.empty-state 
                icon="inbox" 
                message="Brak dokumentów w systemie."
            >
                <x-ui.button 
                    variant="primary" 
                    href="{{ route('documents.create') }}"
                    routeName="documents.create"
                    action="create"
                >
                    Dodaj pierwszy dokument
                </x-ui.button>
            </x-ui.empty-state>
        @endif
    </x-ui.card>
</x-app-layout>
