{{-- orb / Impek: kurier — trzyma paczke w prawej rece, tulow jak reszta robotow --}}
<circle class="ac-bot__aura" cx="36" cy="42" r="34" fill="url(#{{ $glow }})"/>

<path class="ac-bot__antenna" d="M36 10 L36 5.5" stroke="url(#{{ $grad }})" stroke-width="2" stroke-linecap="round"/>
<circle class="ac-bot__spark" cx="36" cy="3" r="2.6" fill="url(#{{ $grad }})"/>

<circle class="ac-bot__head" cx="36" cy="33" r="23"
        fill="#0d1424" stroke="url(#{{ $grad }})" stroke-width="2"/>

{{-- tarcza zegara: czarna plyta pod wskazowkami --}}
<circle class="ac-bot__dial" cx="36" cy="33" r="17" fill="#050912"
        stroke="rgba(148,163,184,.2)" stroke-width="1.1"/>
<g class="ac-bot__ticks" stroke="rgba(148,163,184,.35)" stroke-width="1.5" stroke-linecap="round">
    <path d="M36 16.5 L36 19"/>
    <path d="M53.5 33 L51 33"/>
    <path d="M36 49.5 L36 47"/>
    <path d="M18.5 33 L21 33"/>
</g>

<circle class="ac-bot__scan" cx="36" cy="33" r="20" fill="none"
        stroke="url(#{{ $grad }})" stroke-width="1.8" stroke-linecap="round"
        stroke-dasharray="17 110"/>

<path class="ac-bot__hand ac-bot__hand--hour" d="M36 33 L42.5 36.5"
      stroke="#f59e0b" stroke-width="2.5" stroke-linecap="round"/>
<path class="ac-bot__hand ac-bot__hand--min" d="M36 33 L36 22.5"
      stroke="url(#{{ $grad }})" stroke-width="1.7" stroke-linecap="round"/>
<circle class="ac-bot__core" cx="36" cy="33" r="2" fill="url(#{{ $grad }})"/>

{{-- oczy w kolorze reszty robotow rodziny --}}
<g class="ac-bot__eyes">
    <ellipse class="ac-bot__eye" cx="29" cy="29.5" rx="3" ry="3.6" fill="#7dd3fc"/>
    <ellipse class="ac-bot__eye" cx="43" cy="29.5" rx="3" ry="3.6" fill="#7dd3fc"/>
</g>
<g class="ac-bot__eyes-happy" stroke="#7dd3fc" stroke-width="2.1" stroke-linecap="round" fill="none">
    <path d="M26.2 30.6 q2.8 -3.8 5.6 0"/>
    <path d="M40.2 30.6 q2.8 -3.8 5.6 0"/>
</g>

{{-- szyja + tulow, jak reszta robotow --}}
<path d="M31.5 56 h9 v4 h-9 z" fill="#0d1424" stroke="url(#{{ $grad }})" stroke-width="1.2"/>
<rect class="ac-bot__body" x="14" y="60" width="44" height="20" rx="10"
      fill="#0d1424" stroke="url(#{{ $grad }})" stroke-width="1.7"/>

{{-- robocza reka: paczka (transfer) --}}
<g class="ac-bot__artifact">
    <path d="M50 65 q6 -1 11 4" fill="none" stroke="url(#{{ $grad }})" stroke-width="2.3" stroke-linecap="round"/>
    <circle cx="51" cy="64.5" r="2.1" fill="#0d1424" stroke="url(#{{ $grad }})" stroke-width="1.2"/>
    <rect x="55" y="64" width="15" height="12" rx="2.2"
          fill="#0d1424" stroke="url(#{{ $grad }})" stroke-width="1.5"/>
    <path d="M55 68.5 h15" stroke="rgba(148,163,184,.4)" stroke-width="1.1"/>
    {{-- mini strzalki na paczce --}}
    <g fill="none" stroke="url(#{{ $grad }})" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
        <path d="M59 71 v4"/>
        <path d="M57.5 73.5 l1.5 2 l1.5 -2"/>
        <path d="M66 74.5 v-4"/>
        <path d="M64.5 72 l1.5 -2 l1.5 2"/>
    </g>
</g>

<g class="ac-bot__think" fill="url(#{{ $grad }})">
    <circle cx="57" cy="10" r="2.1"/>
    <circle cx="62.5" cy="6" r="1.5"/>
    <circle cx="66.5" cy="3" r="1"/>
</g>

<g class="ac-bot__check">
    <circle cx="14" cy="72" r="8" fill="#10b981"/>
    <path d="M10.2 72.2 L12.8 74.8 L18 69.4" fill="none"
          stroke="#052e1b" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"/>
</g>
