@php
    $client = $client ?? null;
    $countryOptions = \App\Enums\EuropeanCountry::cases();
    $typeOptions = \App\Enums\ClientType::options();
@endphp

<div class="mb-3">
    <x-ui.input type="select" name="type" label="Typ kontrahenta" required="true">
        @foreach($typeOptions as $value => $label)
            <option value="{{ $value }}" {{ old('type', $client?->type?->value ?? 'client') === $value ? 'selected' : '' }}>
                {{ $label }}
            </option>
        @endforeach
    </x-ui.input>
</div>

<div class="mb-3">
    <x-ui.input type="text" name="name" label="Nazwa" value="{{ old('name', $client?->name ?? '') }}" required="true" />
</div>

<div class="mb-3">
    <x-ui.input type="text" name="short_name" label="Nazwa skrócona" value="{{ old('short_name', $client?->short_name ?? '') }}" />
    <div class="form-text">Osoby kontaktowe dodajesz w kartotece (zakładka „Osoby”).</div>
</div>

<div class="row mb-3">
    <div class="col-md-6 mb-3 mb-md-0">
        <x-ui.input type="text" name="nip" label="NIP (PL)" value="{{ old('nip', $client?->nip ?? '') }}" placeholder="10 cyfr" maxlength="10" />
    </div>
    <div class="col-md-6">
        <x-ui.input type="text" name="tax_id" label="Identyfikator podatkowy (zagraniczny)" value="{{ old('tax_id', $client?->tax_id ?? '') }}" />
    </div>
</div>

<div class="mb-3">
    <x-ui.input type="text" name="address" label="Adres" value="{{ old('address', $client?->address ?? '') }}" />
</div>

<div class="row mb-3">
    <div class="col-md-4 mb-3 mb-md-0">
        <x-ui.input type="text" name="city" label="Miasto" value="{{ old('city', $client?->city ?? '') }}" />
    </div>
    <div class="col-md-4 mb-3 mb-md-0">
        <x-ui.input type="text" name="postal_code" label="Kod pocztowy" value="{{ old('postal_code', $client?->postal_code ?? '') }}" />
    </div>
    <div class="col-md-4">
        <x-ui.input type="select" name="country" label="Kraj">
            <option value="">—</option>
            @foreach($countryOptions as $countryOption)
                <option value="{{ $countryOption->value }}" {{ old('country', $client?->country?->value ?? 'PL') == $countryOption->value ? 'selected' : '' }}>
                    {{ $countryOption->labelWithFlag() }}
                </option>
            @endforeach
        </x-ui.input>
    </div>
</div>

<div class="mb-3">
    <x-ui.input type="textarea" name="notes" label="Notatki CRM" value="{{ old('notes', $client?->notes ?? '') }}" rows="4" />
</div>

<div class="form-check mb-3">
    <input
        class="form-check-input"
        type="checkbox"
        name="is_active"
        id="is_active"
        value="1"
        {{ old('is_active', $client?->is_active ?? true) ? 'checked' : '' }}
    >
    <label class="form-check-label" for="is_active">Kontrahent aktywny</label>
</div>
