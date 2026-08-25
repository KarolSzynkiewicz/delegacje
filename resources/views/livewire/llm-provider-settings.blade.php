<div>
    @if($successMessage)
        <x-ui.alert variant="success" dismissible class="mb-3">{{ $successMessage }}</x-ui.alert>
    @endif

    @if($errorMessage)
        <x-ui.alert variant="danger" dismissible class="mb-3">{{ $errorMessage }}</x-ui.alert>
    @endif

    @if($testOutput)
        <x-ui.alert variant="success" dismissible class="mb-3">
            <i class="bi bi-plug"></i> Połączenie działa. {{ $testOutput }}
        </x-ui.alert>
    @endif

    <div class="row g-3">
        <div class="col-md-5">
            <label class="form-label" for="llm-provider">Dostawca</label>
            <select id="llm-provider" class="form-select" wire:model.live="provider">
                @foreach($providers as $key => $providerInstance)
                    <option value="{{ $key }}">{{ $providerInstance->label() }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-7">
            <label class="form-label" for="llm-model">Model</label>
            <input
                id="llm-model"
                class="form-control font-mono"
                list="llm-model-options"
                wire:model="model"
                placeholder="{{ $currentProvider->defaultModel() }}"
                autocomplete="off"
            >
            <datalist id="llm-model-options">
                @foreach($currentProvider->availableModels() as $modelId => $modelLabel)
                    <option value="{{ $modelId }}">{{ $modelLabel }}</option>
                @endforeach
            </datalist>
            @error('model') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        </div>

        <div class="col-12">
            <label class="form-label" for="llm-api-key">
                Klucz API
                @if($keyUrl)
                    <a href="{{ $keyUrl }}" target="_blank" rel="noopener" class="small ms-2">
                        skąd go wziąć <i class="bi bi-box-arrow-up-right"></i>
                    </a>
                @endif
            </label>
            <input
                id="llm-api-key"
                type="password"
                class="form-control font-mono"
                wire:model="apiKey"
                autocomplete="off"
                placeholder="{{ $stored ? 'Zostaw puste, żeby nie zmieniać zapisanego klucza' : 'Wklej klucz z panelu dostawcy' }}"
            >
            @error('apiKey') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
        <x-ui.button variant="primary" wire:click="save" wire:loading.attr="disabled" wire:target="save">
            <span wire:loading.remove wire:target="save"><i class="bi bi-save"></i> Zapisz i ustaw jako aktywny</span>
            <span wire:loading wire:target="save">Zapisywanie…</span>
        </x-ui.button>

        <x-ui.button
            variant="ghost"
            wire:click="testConnection"
            wire:loading.attr="disabled"
            wire:target="testConnection"
            :disabled="! $credentials"
        >
            <span wire:loading.remove wire:target="testConnection"><i class="bi bi-plug"></i> Testuj połączenie</span>
            <span wire:loading wire:target="testConnection">Pytam model…</span>
        </x-ui.button>

        @if($stored)
            <x-ui.button
                variant="danger"
                wire:click="removeKey"
                wire:confirm="Usunąć zapisany klucz tego dostawcy?"
                wire:loading.attr="disabled"
                wire:target="removeKey"
            >
                <i class="bi bi-trash"></i> Usuń klucz
            </x-ui.button>
        @endif
    </div>

    <dl class="row mb-0 mt-3 small">
        <dt class="col-sm-4 fw-semibold text-muted">Stan</dt>
        <dd class="col-sm-8">
            @if($credentials)
                <x-ui.badge variant="success">Klucz zapisany</x-ui.badge>
                <span class="font-mono ms-2">{{ $credentials->maskedKey() }}</span>
                @if($credentials->source === 'env')
                    <x-ui.badge variant="secondary">z .env</x-ui.badge>
                @endif
            @else
                <x-ui.badge variant="secondary">Brak klucza</x-ui.badge>
            @endif
        </dd>

        <dt class="col-sm-4 fw-semibold text-muted">Aktywny dostawca</dt>
        <dd class="col-sm-8">
            @if($activeProvider)
                {{ isset($providers[$activeProvider]) ? $providers[$activeProvider]->label() : $activeProvider }}
            @else
                <span class="text-muted">nie ustawiono</span>
            @endif
        </dd>

        <dt class="col-sm-4 fw-semibold text-muted">Ostatnie użycie</dt>
        <dd class="col-sm-8 mb-0 font-mono">
            {{ $stored?->last_used_at?->format('Y-m-d H:i') ?? '—' }}
        </dd>
    </dl>
</div>
