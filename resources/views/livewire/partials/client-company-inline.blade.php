@php
    $canManage = $canManage ?? false;
    $countryOptions = $countryOptions ?? \App\Enums\EuropeanCountry::cases();
    $typeOptions = $typeOptions ?? \App\Enums\ClientType::options();
    $summaryBits = array_filter([
        $client->type?->shortLabel(),
        $client->nip ? 'NIP '.$client->nip : null,
        $client->tax_id ?: null,
        $client->city ?: null,
        $client->country?->labelWithFlag(),
    ]);
@endphp

<div
    class="crm-company"
    x-data="{ open: @js($errors->hasAny(['name', 'type', 'nip', 'tax_id', 'country', 'notes'])) }"
>
    <button
        type="button"
        class="crm-company__toggle"
        @click="open = !open"
        :aria-expanded="open.toString()"
    >
        <span class="crm-company__toggle-main">
            <span class="crm-company__toggle-label">Dane firmy</span>
            <span class="crm-company__toggle-summary">
                {{ $client->name }}
                @if($summaryBits !== [])
                    <span class="text-muted">· {{ implode(' · ', $summaryBits) }}</span>
                @endif
            </span>
        </span>
        <i class="bi bi-chevron-down crm-company__chevron" :class="{ 'is-open': open }" aria-hidden="true"></i>
    </button>

    <div class="crm-company__body" x-show="open" x-cloak x-transition.opacity.duration.150ms>
        <div class="crm-company__grid pt-3">
            <div class="crm-company__field">
                <label class="crm-inline-label">Typ</label>
                @if($canManage)
                    <select class="form-select crm-inline @error('type') is-invalid @enderror" wire:change="updateClientField('type', $event.target.value)">
                        @foreach($typeOptions as $value => $label)
                            <option value="{{ $value }}" @selected($client->type?->value === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('type') <span class="crm-inline-error">{{ $message }}</span> @enderror
                @else
                    <div class="crm-inline-readonly">{{ $client->type?->label() ?? '—' }}</div>
                @endif
            </div>

            <div class="crm-company__field crm-company__field--wide">
                <label class="crm-inline-label">Nazwa</label>
                @if($canManage)
                    <input
                        type="text"
                        class="form-control crm-inline crm-inline--title @error('name') is-invalid @enderror"
                        value="{{ $client->name }}"
                        wire:blur="updateClientField('name', $event.target.value)"
                    >
                    @error('name') <span class="crm-inline-error">{{ $message }}</span> @enderror
                @else
                    <div class="crm-inline-readonly fw-semibold">{{ $client->name }}</div>
                @endif
            </div>

            <div class="crm-company__field">
                <label class="crm-inline-label">Nazwa skrócona</label>
                @if($canManage)
                    <input type="text" class="form-control crm-inline" value="{{ $client->short_name }}" wire:blur="updateClientField('short_name', $event.target.value)" placeholder="—">
                @else
                    <div class="crm-inline-readonly">{{ $client->short_name ?? '—' }}</div>
                @endif
            </div>

            <div class="crm-company__field">
                <label class="crm-inline-label">NIP</label>
                @if($canManage)
                    <input type="text" class="form-control crm-inline font-mono @error('nip') is-invalid @enderror" value="{{ $client->nip }}" maxlength="10" wire:blur="updateClientField('nip', $event.target.value)" placeholder="—">
                    @error('nip') <span class="crm-inline-error">{{ $message }}</span> @enderror
                @else
                    <div class="crm-inline-readonly font-mono">{{ $client->nip ?? '—' }}</div>
                @endif
            </div>

            <div class="crm-company__field">
                <label class="crm-inline-label">ID podatkowy</label>
                @if($canManage)
                    <input type="text" class="form-control crm-inline" value="{{ $client->tax_id }}" wire:blur="updateClientField('tax_id', $event.target.value)" placeholder="—">
                @else
                    <div class="crm-inline-readonly">{{ $client->tax_id ?? '—' }}</div>
                @endif
            </div>

            <div class="crm-company__field">
                <label class="crm-inline-label">Status</label>
                @if($canManage)
                    <select class="form-select crm-inline" wire:change="updateClientField('is_active', $event.target.value)">
                        <option value="1" @selected($client->is_active)>Aktywny</option>
                        <option value="0" @selected(! $client->is_active)>Nieaktywny</option>
                    </select>
                @else
                    <div class="crm-inline-readonly">{{ $client->is_active ? 'Aktywny' : 'Nieaktywny' }}</div>
                @endif
            </div>

            <div class="crm-company__field">
                <label class="crm-inline-label">Adres</label>
                @if($canManage)
                    <input type="text" class="form-control crm-inline" value="{{ $client->address }}" wire:blur="updateClientField('address', $event.target.value)" placeholder="—">
                @else
                    <div class="crm-inline-readonly">{{ $client->address ?? '—' }}</div>
                @endif
            </div>

            <div class="crm-company__field">
                <label class="crm-inline-label">Miasto</label>
                @if($canManage)
                    <input type="text" class="form-control crm-inline" value="{{ $client->city }}" wire:blur="updateClientField('city', $event.target.value)" placeholder="—">
                @else
                    <div class="crm-inline-readonly">{{ $client->city ?? '—' }}</div>
                @endif
            </div>

            <div class="crm-company__field">
                <label class="crm-inline-label">Kod pocztowy</label>
                @if($canManage)
                    <input type="text" class="form-control crm-inline" value="{{ $client->postal_code }}" wire:blur="updateClientField('postal_code', $event.target.value)" placeholder="—">
                @else
                    <div class="crm-inline-readonly">{{ $client->postal_code ?? '—' }}</div>
                @endif
            </div>

            <div class="crm-company__field">
                <label class="crm-inline-label">Kraj</label>
                @if($canManage)
                    <select class="form-select crm-inline" wire:change="updateClientField('country', $event.target.value)">
                        <option value="">—</option>
                        @foreach($countryOptions as $countryOption)
                            <option value="{{ $countryOption->value }}" @selected($client->country?->value === $countryOption->value)>
                                {{ $countryOption->labelWithFlag() }}
                            </option>
                        @endforeach
                    </select>
                @else
                    <div class="crm-inline-readonly">{{ $client->country?->labelWithFlag() ?? '—' }}</div>
                @endif
            </div>

            <div class="crm-company__field crm-company__field--wide">
                <label class="crm-inline-label">Notatki CRM</label>
                @if($canManage)
                    <textarea
                        class="form-control crm-inline crm-inline--muted"
                        rows="2"
                        wire:blur="updateClientField('notes', $event.target.value)"
                        placeholder="Opcjonalne notatki…"
                    >{{ $client->notes }}</textarea>
                @else
                    <div class="crm-inline-readonly text-muted">{{ $client->notes ?: '—' }}</div>
                @endif
            </div>
        </div>
    </div>
</div>
