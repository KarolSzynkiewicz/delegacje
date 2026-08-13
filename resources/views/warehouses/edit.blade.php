<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Edytuj magazyn">
            <x-slot name="left">
                <x-ui.button
                    variant="ghost"
                    href="{{ route('warehouses.index') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-6">
            <x-ui.card label="{{ $warehouse->display_name }}">
                <x-ui.errors />
                <form method="POST" action="{{ route('warehouses.update', $warehouse) }}">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label class="form-label">Lokalizacja</label>
                        <input type="text" class="form-control" value="{{ $warehouse->location?->name ?? '—' }}" disabled>
                        <small class="form-text text-muted">Lokalizacji nie zmieniasz tutaj — magazyn jest na stałe w tej lokalizacji.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="warehouse-name">Nazwa magazynu <span class="text-danger">*</span></label>
                        <input
                            id="warehouse-name"
                            type="text"
                            name="name"
                            value="{{ old('name', $warehouse->name) }}"
                            class="form-control @error('name') is-invalid @enderror"
                            required
                        >
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-4">
                        <div class="form-check">
                            <input
                                id="warehouse-default"
                                type="checkbox"
                                name="is_default"
                                value="1"
                                class="form-check-input"
                                @checked(old('is_default', $warehouse->is_default))
                            >
                            <label class="form-check-label" for="warehouse-default">Magazyn siedziby (domyślny)</label>
                        </div>
                        @error('is_default') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>
                    <div class="d-flex justify-content-between gap-2">
                        @if(! $warehouse->is_default)
                            <button
                                type="submit"
                                form="warehouse-delete-form"
                                class="btn btn-outline-danger"
                                onclick="return confirm('Usunąć ten magazyn? Stany muszą być zerowe, bez wydań i rozchodów.')"
                            >
                                Usuń magazyn
                            </button>
                        @else
                            <span></span>
                        @endif
                        <div class="d-flex gap-2">
                            <x-ui.button
                                variant="ghost"
                                href="{{ route('warehouses.index') }}"
                                action="cancel"
                            >
                                Anuluj
                            </x-ui.button>
                            <x-ui.button variant="primary" type="submit" action="save">
                                Zapisz
                            </x-ui.button>
                        </div>
                    </div>
                </form>
                @if(! $warehouse->is_default)
                    <form id="warehouse-delete-form" method="POST" action="{{ route('warehouses.destroy', $warehouse) }}" class="d-none">
                        @csrf
                        @method('DELETE')
                    </form>
                @endif
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
