<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Dodaj Dokument">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('documents.index') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Dodaj Nowy Dokument">
                <x-ui.errors />

                <form action="{{ route('documents.store') }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <x-ui.input 
                            type="text" 
                            name="name" 
                            label="Nazwa dokumentu"
                            value="{{ old('name') }}"
                            placeholder="np. Prawo jazdy, Licencja, Dowód osobisty"
                            required="true"
                        />
                    </div>

                    <div class="mb-3">
                        <x-ui.input 
                            type="textarea" 
                            name="description" 
                            label="Opis"
                            value="{{ old('description') }}"
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
                            <option value="1" {{ old('is_periodic', '1') == '1' ? 'selected' : '' }}>Tak</option>
                            <option value="0" {{ old('is_periodic') == '0' ? 'selected' : '' }}>Nie</option>
                        </x-ui.input>
                        <small class="form-text text-muted">Czy dokument ma datę ważności do?</small>
                    </div>

                    <div class="mb-4">
                        <x-ui.input 
                            type="checkbox" 
                            name="is_required" 
                            label="Dokument wymagany"
                            checked="{{ old('is_required') ? true : false }}"
                        />
                        <small class="form-text text-muted d-block mt-1">Zaznacz, jeśli dokument jest wymagany dla wszystkich pracowników</small>
                    </div>

                    <div class="d-flex justify-content-end align-items-center gap-2">
                        <x-ui.button 
                            variant="ghost" 
                            href="{{ route('documents.index') }}"
                            action="cancel"
                        >
                            Anuluj
                        </x-ui.button>
                        <x-ui.button 
                            variant="primary" 
                            type="submit"
                            action="save"
                        >
                            Dodaj Dokument
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
