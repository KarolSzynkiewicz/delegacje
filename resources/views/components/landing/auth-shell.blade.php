@props([
    'ctaLabel' => 'Zaloguj się',
    'ctaHref' => null,
])
<div class="cl-landing cl-landing--auth">
    <x-landing.nav :cta-label="$ctaLabel" :cta-href="$ctaHref">
        <span class="cl-landing-clock font-mono"><span class="cl-landing-clock__dot"></span><span data-cl-clock>00:00:00</span></span>
    </x-landing.nav>

    <section class="cl-landing-auth">
        <div class="cl-landing-wrap cl-landing-auth__grid">
            <div class="cl-landing-auth__copy">
                @isset($eyebrow)
                    <span class="cl-landing-eyebrow">{{ $eyebrow }}</span>
                @endisset
                <h1>{{ $title }}</h1>
                @isset($subtitle)
                    <p class="cl-landing-sub">{{ $subtitle }}</p>
                @endisset
            </div>
            <div class="cl-landing-auth__form">
                {{ $slot }}
            </div>
        </div>
    </section>
</div>
