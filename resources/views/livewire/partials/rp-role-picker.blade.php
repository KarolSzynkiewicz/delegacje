@php
    $wireModel = $wireModel ?? 'editRoles';
    $live = $live ?? true;
    $keyPrefix = $keyPrefix ?? 'role';
    $missing = $missing ?? false;
    $selectedIds = collect($selected ?? [])->map(fn ($id) => (int) $id);
@endphp
<div class="rp-roles {{ $missing ? 'is-empty' : '' }}"
     x-data="{ open: {{ $missing ? 'true' : 'false' }}, q: '' }"
     wire:ignore.self>
    <div class="rp-roles__bar">
        <div class="rp-field-label {{ $missing ? 'is-empty' : '' }}">
            Role / stanowiska
            @if($selectedIds->isNotEmpty())
                <span class="rp-field-count">{{ $selectedIds->count() }}</span>
            @endif
        </div>
        <button type="button" class="rp-roles__toggle" @click="open = !open; if (!open) q = ''">
            <span x-text="open ? 'Zwiń' : 'Wybierz role'"></span>
            <i class="bi" :class="open ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
        </button>
    </div>

    @if($selectedIds->isNotEmpty())
        <div class="rp-roles__chips">
            @foreach($roles as $role)
                @if($selectedIds->contains($role->id))
                    <label class="rp-role-chip" wire:key="{{ $keyPrefix }}-chip-{{ $role->id }}" title="Kliknij, aby odznaczyć">
                        @if($live)
                            <input type="checkbox" class="visually-hidden" wire:model.live.debounce.300ms="{{ $wireModel }}" value="{{ $role->id }}">
                        @else
                            <input type="checkbox" class="visually-hidden" wire:model="{{ $wireModel }}" value="{{ $role->id }}">
                        @endif
                        <span>{{ $role->name }}</span>
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </label>
                @endif
            @endforeach
        </div>
    @else
        <p class="rp-roles__hint">Nie wybrano stanowisk — otwórz listę i zaznacz te, które pasują.</p>
    @endif

    <div class="rp-roles__picker" x-show="open" x-cloak>
        <input type="search"
               class="form-control form-control-sm rp-roles__search"
               placeholder="Szukaj roli…"
               x-model="q"
               @click.stop>
        <div class="rp-roles__grid">
            @foreach($roles as $role)
                <label class="form-check-compact rp-role-option"
                       wire:key="{{ $keyPrefix }}-{{ $role->id }}"
                       x-show="!q || {{ json_encode(mb_strtolower($role->name)) }}.includes(q.toLowerCase())">
                    @if($live)
                        <input type="checkbox" wire:model.live.debounce.300ms="{{ $wireModel }}" value="{{ $role->id }}">
                    @else
                        <input type="checkbox" wire:model="{{ $wireModel }}" value="{{ $role->id }}">
                    @endif
                    <span>{{ $role->name }}</span>
                </label>
            @endforeach
        </div>
    </div>
</div>
