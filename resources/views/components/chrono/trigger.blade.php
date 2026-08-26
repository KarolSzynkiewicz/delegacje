@props([
    'target',                       // metoda Livewire wywoływana kliknięciem
    'label' => 'AskChrono',
    'hint' => null,                 // podpis pod nazwą; null = sam label
    'hintLoading' => 'Budzę bota…',
    'size' => 36,
    'title' => null,
])

<button
    type="button"
    {{ $attributes->merge(['class' => 'ac-trigger']) }}
    wire:click="{{ $target }}"
    wire:loading.attr="disabled"
    wire:target="{{ $target }}"
    title="{{ $title ?? ($hint ? $label.' — '.$hint : $label) }}"
>
    <x-ask-chrono-bot
        :size="$size"
        wire:loading.class="ac-bot--thinking"
        wire:target="{{ $target }}"
    />
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
