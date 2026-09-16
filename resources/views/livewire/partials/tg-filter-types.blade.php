@include('livewire.partials.tg-filter-op', ['field' => 'selectedTypes'])
<span class="rp-filter-hint">Kilka typów = lub. Między typem a statusem / osobą zawsze i.</span>
<div class="rp-filter-chips">
    @foreach(\App\Enums\WorkItemType::cases() as $wt)
        <button type="button" wire:click="toggleType('{{ $wt->value }}')"
                class="rp-filter-option {{ in_array($wt->value, $selectedTypes, true) ? 'is-active' : '' }}">
            <span class="rp-filter-check {{ in_array($wt->value, $selectedTypes, true) ? 'is-checked' : '' }}"><i class="bi bi-check"></i></span>
            <span class="rp-filter-option__label"><i class="bi {{ $wt->icon() }} me-1 opacity-75"></i>{{ $wt->label() }}</span>
        </button>
    @endforeach
</div>
