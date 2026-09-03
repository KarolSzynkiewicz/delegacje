@props([
    'kicker' => '',
    'title',
    'caption' => '',
    'href' => null,
    'hrefLabel' => 'Otwórz w systemie',
    'interactive' => false,
    'tall' => false,
])

<section {{ $attributes->class(['dash-snap', 'dash-snap--interactive' => $interactive]) }}>
    <header class="dash-snap__head">
        @if($kicker)
            <span class="dash-snap__kicker font-mono">{{ $kicker }}</span>
        @endif
        <div class="dash-snap__title-row">
            <h3 class="dash-snap__title">{{ $title }}</h3>
            <x-ui.badge variant="accent">demo</x-ui.badge>
            @if($href)
                <a href="{{ $href }}" class="dash-snap__live small">{{ $hrefLabel }} <i class="bi bi-box-arrow-up-right"></i></a>
            @endif
        </div>
        @if($caption)
            <p class="dash-snap__caption text-muted mb-0">{{ $caption }}</p>
        @endif
        @isset($note)
            <p class="dash-snap__note mb-0">{{ $note }}</p>
        @endisset
    </header>
    <div @class(['dash-snap__stage', 'dash-snap__stage--tall' => $tall])>
        {{ $slot }}
    </div>
</section>
