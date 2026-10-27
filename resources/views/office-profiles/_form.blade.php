@php
    /** @var \App\Models\OfficeProfile|null $profile */
@endphp

@if(!$profile)
    <div class="mb-3">
        <x-ui.input type="select" name="user_id" id="user_id" label="Użytkownik" required="true">
            <option value="">Wybierz użytkownika</option>
            @foreach($users as $user)
                <option value="{{ $user->id }}" @selected((string) old('user_id') === (string) $user->id)>
                    {{ $user->name }} ({{ $user->email }})
                </option>
            @endforeach
        </x-ui.input>
    </div>
@else
    <div class="mb-3">
        <label class="form-label">Użytkownik</label>
        <div class="fw-semibold">{{ $profile->user?->name }}</div>
        <div class="small text-muted">{{ $profile->user?->email }}</div>
    </div>
@endif

<div class="row mb-3">
    <div class="col-md-6 mb-3 mb-md-0">
        <x-ui.input type="select" name="contract_type" id="contract_type" label="Typ umowy" required="true">
            @foreach($contractTypes as $value => $label)
                <option value="{{ $value }}" @selected((string) old('contract_type', $profile?->contract_type?->value) === (string) $value)>
                    {{ $label }}
                </option>
            @endforeach
        </x-ui.input>
    </div>
    <div class="col-md-6">
        <x-ui.input type="select" name="pay_mode" id="pay_mode" label="Tryb rozliczenia" required="true">
            @foreach($payModes as $value => $label)
                <option value="{{ $value }}" @selected((string) old('pay_mode', $profile?->pay_mode?->value ?? 'hourly') === (string) $value)>
                    {{ $label }}
                </option>
            @endforeach
        </x-ui.input>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-4 mb-3 mb-md-0">
        <x-ui.input
            type="number"
            name="fte"
            id="fte"
            label="Część etatu"
            value="{{ old('fte', $profile?->fte ?? '1.00') }}"
            step="0.01"
            min="0.01"
            max="1"
            required="true"
        />
    </div>
    <div class="col-md-4 mb-3 mb-md-0">
        <x-ui.input
            type="number"
            name="hourly_rate"
            id="hourly_rate"
            label="Stawka godzinowa"
            value="{{ old('hourly_rate', $profile?->hourly_rate) }}"
            step="0.01"
            min="0"
        />
    </div>
    <div class="col-md-4">
        <x-ui.input
            type="text"
            name="currency"
            id="currency"
            label="Waluta"
            value="{{ old('currency', $profile?->currency ?? 'PLN') }}"
            required="true"
        />
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-6 mb-3 mb-md-0">
        <x-ui.input
            type="date"
            name="started_on"
            id="started_on"
            label="Od"
            value="{{ old('started_on', $profile?->started_on?->format('Y-m-d')) }}"
        />
    </div>
    <div class="col-md-6">
        <x-ui.input
            type="date"
            name="ended_on"
            id="ended_on"
            label="Do"
            value="{{ old('ended_on', $profile?->ended_on?->format('Y-m-d')) }}"
        />
    </div>
</div>

<div class="mb-3 form-check">
    <input
        type="checkbox"
        class="form-check-input"
        name="is_active"
        id="is_active"
        value="1"
        @checked(old('is_active', $profile?->is_active ?? true))
    >
    <label class="form-check-label" for="is_active">Profil aktywny</label>
</div>

<div class="mb-0">
    <x-ui.input
        type="textarea"
        name="notes"
        id="notes"
        label="Notatki"
        value="{{ old('notes', $profile?->notes) }}"
    />
</div>
