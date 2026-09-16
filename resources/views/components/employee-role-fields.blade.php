@props([
    'roles',
    'selected' => [],
    'seniority' => [],
])

@php
    $selected = collect(old('roles', $selected))->map(fn ($id) => (int) $id)->all();
    $oldSeniority = old('role_seniority', $seniority);
@endphp

<div class="emp-role-fields @error('roles') is-invalid @enderror">
    @foreach ($roles as $role)
        @php
            $checked = in_array((int) $role->id, $selected, true);
            $level = $oldSeniority[$role->id] ?? $oldSeniority[(string) $role->id] ?? null;
        @endphp
        <div class="emp-role-fields__row">
            <div class="form-check mb-0">
                <input
                    type="checkbox"
                    id="role_{{ $role->id }}"
                    name="roles[]"
                    value="{{ $role->id }}"
                    {{ $checked ? 'checked' : '' }}
                    class="form-check-input @error('roles') is-invalid @enderror"
                >
                <label for="role_{{ $role->id }}" class="form-check-label">
                    {{ $role->name }}
                    @if($role->description)
                        <small class="text-muted d-block">{{ $role->description }}</small>
                    @endif
                </label>
            </div>
            <select
                name="role_seniority[{{ $role->id }}]"
                class="form-select form-select-sm emp-role-fields__seniority"
                title="Seniority dla {{ $role->name }}"
                aria-label="Seniority — {{ $role->name }}"
            >
                <option value="" {{ $level === null || $level === '' ? 'selected' : '' }}>Nieustalone</option>
                @foreach(\App\Enums\RoleSeniority::cases() as $case)
                    <option value="{{ $case->value }}" {{ (string) $level === (string) $case->value ? 'selected' : '' }}>
                        {{ $case->value }} — {{ $case->label() }}
                    </option>
                @endforeach
            </select>
        </div>
    @endforeach
</div>
@error('roles') <span class="invalid-feedback d-block">{{ $message }}</span> @enderror
<small class="form-text text-muted">Zawód to malarz, nie „malarz 1”. Poziom 1–4 dopisujesz obok. Puste = nieustalone, nie poziom 1.</small>
