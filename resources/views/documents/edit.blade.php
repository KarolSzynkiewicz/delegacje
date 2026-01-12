<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="fw-semibold fs-4 mb-0">Edytuj Dokument: {{ $document->name }}</h2>
            <x-ui.button variant="ghost" href="{{ route('documents.index') }}">
                <i class="bi bi-arrow-left"></i> Powrót
            </x-ui.button>
        </div>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Edytuj Dokument">
                @if ($errors->any())
                    <div class="alert alert-danger mb-4" role="alert">
                        <h5 class="alert-heading mb-2">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>Wystąpiły błędy:
                        </h5>
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('documents.update', $document) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <x-ui.input 
                            type="text" 
                            name="name" 
                            label="Nazwa dokumentu"
                            value="{{ old('name', $document->name) }}"
                            required="true"
                        />
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="textarea" 
                            name="description" 
                            label="Opis"
                            value="{{ old('description', $document->description) }}"
                            rows="3"
                        />
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="select" 
                            name="is_periodic" 
                            label="Dokument okresowy"
                            required="true"
                        >
                            <option value="">-- Wybierz --</option>
                            <option value="1" {{ old('is_periodic', $document->is_periodic) == '1' || old('is_periodic', $document->is_periodic) === true ? 'selected' : '' }}>Tak</option>
                            <option value="0" {{ old('is_periodic', $document->is_periodic) == '0' || old('is_periodic', $document->is_periodic) === false ? 'selected' : '' }}>Nie</option>
                        </x-ui.input>
                        <small class="form-text text-muted">Czy dokument ma datę ważności do?</small>
                    </div>

                    <div class="mb-4">
                        <x-ui.input 
                            type="checkbox" 
                            name="is_required" 
                            label="Dokument wymagany"
                            checked="{{ old('is_required', $document->is_required) ? true : false }}"
                        />
                        <small class="form-text text-muted d-block mt-1">Zaznacz, jeśli dokument jest wymagany dla wszystkich pracowników</small>
                    </div>

                    <div class="d-flex justify-content-end align-items-center gap-2">
                        <x-ui.button variant="ghost" href="{{ route('documents.index') }}">Anuluj</x-ui.button>
                        <x-ui.button variant="primary" type="submit">
                            <i class="bi bi-save me-1"></i> Zaktualizuj Dokument
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
