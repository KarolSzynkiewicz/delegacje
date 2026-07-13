@php
    $exchangeRate = $exchangeRate ?? null;
    $currencies = ['PLN', 'EUR', 'USD', 'GBP'];
@endphp

<div class="row mb-3">
    <div class="col-md-4 mb-3 mb-md-0">
        <x-ui.input
            type="date"
            name="rate_date"
            label="Data kursu"
            value="{{ old('rate_date', $exchangeRate?->rate_date?->toDateString() ?? now()->toDateString()) }}"
            required="true"
        />
        <small class="text-muted">Dla przeliczeń używany jest najnowszy znany kurs na dzień lub wcześniej.</small>
    </div>
    <div class="col-md-4 mb-3 mb-md-0">
        <x-ui.input
            type="select"
            name="base_currency"
            label="Z waluty (bazowa)"
            required="true"
        >
            @foreach($currencies as $currency)
                <option value="{{ $currency }}" {{ old('base_currency', $exchangeRate?->base_currency) === $currency ? 'selected' : '' }}>{{ $currency }}</option>
            @endforeach
        </x-ui.input>
    </div>
    <div class="col-md-4">
        <x-ui.input
            type="select"
            name="quote_currency"
            label="Na walutę (docelowa)"
            required="true"
        >
            @foreach($currencies as $currency)
                <option value="{{ $currency }}" {{ old('quote_currency', $exchangeRate?->quote_currency ?? 'PLN') === $currency ? 'selected' : '' }}>{{ $currency }}</option>
            @endforeach
        </x-ui.input>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-6 mb-3 mb-md-0">
        <x-ui.input
            type="number"
            name="rate"
            label="Kurs (1 jednostka bazowej = X jednostek docelowej)"
            value="{{ old('rate', $exchangeRate?->rate) }}"
            step="0.000001"
            min="0.000001"
            required="true"
        />
    </div>
    <div class="col-md-6">
        <x-ui.input
            type="text"
            name="source"
            label="Źródło (opcjonalnie)"
            value="{{ old('source', $exchangeRate?->source) }}"
            placeholder="np. NBP, ECB, ręcznie"
        />
    </div>
</div>

<div class="mb-2">
    <x-ui.input
        type="textarea"
        name="notes"
        label="Notatki"
        value="{{ old('notes', $exchangeRate?->notes) }}"
        rows="2"
    />
</div>
