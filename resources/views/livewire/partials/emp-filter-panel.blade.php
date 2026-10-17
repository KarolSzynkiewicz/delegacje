<div class="rp-filter-panel__inner">
    <div class="rp-filter-panel__header">
        <span class="rp-filter-panel__title">Filtry</span>
        <button type="button" wire:click="clearFilters" @click="open = false" class="rp-filter-panel__clear">Wyczyść</button>
    </div>

    <div class="emp-filter-grid">
        <label class="emp-filter-grid__field">
            <span>Zatrudniony</span>
            <select wire:model.live="hirePeriod" class="form-select form-select-sm">
                <option value="">Wszyscy</option>
                <option value="7d">Ostatnie 7 dni</option>
                <option value="30d">Ostatnie 30 dni</option>
            </select>
        </label>
        <label class="emp-filter-grid__field">
            <span>Rola</span>
            <select wire:model.live="roleFilter" class="form-select form-select-sm">
                <option value="">Wszystkie role</option>
                @foreach($roles as $role)
                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="emp-filter-grid__field">
            <span>Lokalizacja</span>
            <select wire:model.live="locationFilter" class="form-select form-select-sm">
                <option value="">Wszystkie</option>
                <option value="base">Baza</option>
                <option value="field">W terenie</option>
                <option value="transit">W podróży</option>
            </select>
        </label>
        <label class="emp-filter-grid__field">
            <span>Rotacja</span>
            <select wire:model.live="rotationFilter" class="form-select form-select-sm">
                <option value="">Wszystkie</option>
                <option value="active">Aktywna</option>
                <option value="inactive">Nieaktywna</option>
            </select>
        </label>
        <label class="emp-filter-grid__field">
            <span>Komornik</span>
            <select wire:model.live="komornikFilter" class="form-select form-select-sm">
                <option value="">Wszyscy</option>
                <option value="yes">Z komornikiem</option>
                <option value="no">Bez komornika</option>
            </select>
        </label>
        <label class="emp-filter-grid__field">
            <span>Spółka</span>
            <select wire:model.live="companyFilter" class="form-select form-select-sm">
                <option value="">Wszystkie spółki</option>
                @foreach($companies as $company)
                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                @endforeach
            </select>
        </label>
    </div>

    <label class="form-check mt-3 mb-0">
        <input type="checkbox" class="form-check-input" wire:model.live="showTerminated">
        <span class="form-check-label small">Pokaż zwolnionych</span>
    </label>
</div>
