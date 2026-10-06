<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Usuń dokument i dzieci">
            <x-slot name="left">
                <x-ui.button variant="ghost" href="{{ route('system-actions.index') }}" action="back">
                    Wróć
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            @if (session('success'))
                <x-ui.alert variant="success" dismissible class="mb-3">
                    {{ session('success') }}
                </x-ui.alert>
            @endif

            <x-ui.errors />

            <x-ui.card label="Typ do skasowania">
                <p class="text-muted small">
                    Znika typ ze słownika, jego wpisy u pracowników i pliki tych wpisów.
                    Kopia na innym typie zostaje, razem ze swoją nazwą pliku.
                </p>

                @if($documents->isEmpty())
                    <p class="mb-0">Brak typów dokumentów.</p>
                @else
                    <form method="POST" action="{{ route('system-actions.documents.destroy.store') }}" id="delete-document-form">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="document_id">Typ</label>
                            <select class="form-select" name="document_id" id="document_id" required>
                                <option value="">— wybierz —</option>
                                @foreach($documents as $document)
                                    <option value="{{ $document->id }}" data-count="{{ $document->employee_documents_count }}" data-name="{{ $document->name }}">
                                        {{ $document->name }} · {{ $document->employee_documents_count }} wpisów
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="d-flex justify-content-end">
                            <x-ui.button variant="danger" type="submit">Usuń typ i wpisy</x-ui.button>
                        </div>
                    </form>
                @endif
            </x-ui.card>
        </div>
    </div>

    @if($documents->isNotEmpty())
        <script>
            document.getElementById('delete-document-form').addEventListener('submit', function (event) {
                const select = document.getElementById('document_id');
                const option = select.options[select.selectedIndex];
                if (!option || !option.value) {
                    return;
                }
                const name = option.dataset.name || 'ten typ';
                const count = option.dataset.count || '0';
                if (!confirm('Usunąć „' + name + '” razem z ' + count + ' wpisami i ich plikami?')) {
                    event.preventDefault();
                }
            });
        </script>
    @endif
</x-app-layout>
