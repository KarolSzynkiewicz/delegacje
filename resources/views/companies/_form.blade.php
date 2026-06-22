@php
    $company = $company ?? null;
    $countryOptions = \App\Enums\EuropeanCountry::cases();
@endphp

<div class="mb-3">
    <x-ui.input type="text" name="name" label="Nazwa spółki" value="{{ old('name', $company?->name ?? '') }}" required="true" />
</div>

<div class="row mb-3">
    <div class="col-md-4 mb-3 mb-md-0">
        <x-ui.input type="text" name="nip" label="NIP" value="{{ old('nip', $company?->nip ?? '') }}" placeholder="10 cyfr" required="true" maxlength="10" />
    </div>
    <div class="col-md-4 mb-3 mb-md-0">
        <x-ui.input type="text" name="regon" label="REGON" value="{{ old('regon', $company?->regon ?? '') }}" placeholder="9 lub 14 cyfr" maxlength="14" />
    </div>
    <div class="col-md-4">
        <x-ui.input type="text" name="krs" label="KRS" value="{{ old('krs', $company?->krs ?? '') }}" />
    </div>
</div>

<div class="mb-3">
    <x-ui.input type="text" name="address" label="Adres" value="{{ old('address', $company?->address ?? '') }}" />
</div>

<div class="row mb-3">
    <div class="col-md-4 mb-3 mb-md-0">
        <x-ui.input type="text" name="city" label="Miasto" value="{{ old('city', $company?->city ?? '') }}" />
    </div>
    <div class="col-md-4 mb-3 mb-md-0">
        <x-ui.input type="text" name="postal_code" label="Kod pocztowy" value="{{ old('postal_code', $company?->postal_code ?? '') }}" />
    </div>
    <div class="col-md-4">
        <x-ui.input type="select" name="country" label="Kraj">
            <option value="">—</option>
            @foreach($countryOptions as $countryOption)
                <option value="{{ $countryOption->value }}" {{ old('country', $company?->country?->value ?? 'PL') == $countryOption->value ? 'selected' : '' }}>
                    {{ $countryOption->labelWithFlag() }}
                </option>
            @endforeach
        </x-ui.input>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-6 mb-3 mb-md-0">
        <x-ui.input type="date" name="founded_at" label="Data założenia" value="{{ old('founded_at', $company?->founded_at?->format('Y-m-d') ?? '') }}" />
    </div>
    <div class="col-md-6">
        <x-ui.input type="text" name="president_name" label="Prezes" value="{{ old('president_name', $company?->president_name ?? '') }}" />
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-6 mb-3 mb-md-0">
        <x-ui.input type="email" name="email" label="E-mail" value="{{ old('email', $company?->email ?? '') }}" />
    </div>
    <div class="col-md-6">
        <x-ui.input type="text" name="phone" label="Telefon" value="{{ old('phone', $company?->phone ?? '') }}" />
    </div>
</div>

<div class="mb-3">
    <x-ui.input type="textarea" name="notes" label="Notatki" value="{{ old('notes', $company?->notes ?? '') }}" rows="4" />
</div>
