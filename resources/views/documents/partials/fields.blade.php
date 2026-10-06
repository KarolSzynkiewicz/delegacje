<div class="mb-3">
    <x-ui.input
        type="text"
        name="name"
        label="Nazwa dokumentu"
        value="{{ old('name') }}"
        placeholder="np. Prawo jazdy, A1, Dowód osobisty"
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
        :checked="(bool) old('is_required')"
    />
    <small class="form-text text-muted d-block mt-1">Bez „na spółkę”: wszyscy. Z „na spółkę”: tylko gdy pracownik ma wtedy spółkę — i musi mieć wpis na tę spółkę.</small>
</div>

<div class="mb-4">
    <x-ui.input
        type="checkbox"
        name="is_company_scoped"
        label="Na spółkę pracownika"
        :checked="(bool) old('is_company_scoped', $companyScopedDefault ?? false)"
    />
    <small class="form-text text-muted d-block mt-1">Umowa o pracę, A1 — jedna pozycja w słowniku. Spółkę wybierasz przy wpisie u człowieka (jak seniority przy zawodzie).</small>
</div>

<x-document-icon-picker />
