@props([
    'target',                       // metoda Livewire wywoływana kliknięciem
    'label' => 'Chrono Assist',
    'hint' => null,                 // podpis pod nazwą; null = sam label
    'hintLoading' => 'Budzę zespół…',
    'size' => 36,
    'title' => null,
    'icon' => 'workshop',           // workshop = 4 boty (wybór persony); bot = pojedynczy Chrono
])

<button
    type="button"
    {{ $attributes->merge(['class' => 'ac-trigger']) }}
    wire:click="{{ $target }}"
    wire:loading.attr="disabled"
    wire:target="{{ $target }}"
    title="{{ $title ?? ($hint ? $label.' — '.$hint : $label) }}"
>
    @if($icon === 'workshop')
        <x-chrono.workshop-icon :size="$size" wire:loading.class="ac-bot--thinking" wire:target="{{ $target }}" />
    @else
        <x-ask-chrono-bot
            :size="$size"
            wire:loading.class="ac-bot--thinking"
            wire:target="{{ $target }}"
        />
    @endif
    <span class="ac-trigger__text d-none d-md-flex">
        <span class="ac-trigger__name">{{ $label }}</span>
        @if($hint !== null)
            <span class="ac-trigger__hint">
                <span wire:loading.remove wire:target="{{ $target }}">{{ $hint }}</span>
                <span wire:loading wire:target="{{ $target }}">{{ $hintLoading }}</span>
            </span>
        @endif
    </span>
</button>
