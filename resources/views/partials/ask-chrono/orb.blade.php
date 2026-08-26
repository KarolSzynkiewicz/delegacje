{{-- Wariant „orb”: sama głowa-kula, cały zegar jako obwódka twarzy. --}}
<circle class="ac-bot__aura" cx="36" cy="36" r="35" fill="url(#{{ $glow }})"/>

<circle class="ac-bot__head" cx="36" cy="34" r="27"
        fill="#0d1424" stroke="url(#{{ $grad }})" stroke-width="2"/>

<circle class="ac-bot__dial" cx="36" cy="34" r="21" fill="none"
        stroke="rgba(148,163,184,.2)" stroke-width="1.1"/>

<g class="ac-bot__ticks" stroke="rgba(148,163,184,.42)" stroke-width="1.7" stroke-linecap="round">
    <path d="M36 13 L36 16.2"/>
    <path d="M57 34 L53.8 34"/>
    <path d="M36 55 L36 51.8"/>
    <path d="M15 34 L18.2 34"/>
</g>

<circle class="ac-bot__scan" cx="36" cy="34" r="24" fill="none"
        stroke="url(#{{ $grad }})" stroke-width="2" stroke-linecap="round"
        stroke-dasharray="21 130"/>

<path class="ac-bot__hand ac-bot__hand--hour" d="M36 34 L43.5 38.3"
      stroke="#f59e0b" stroke-width="3" stroke-linecap="round"/>
<path class="ac-bot__hand ac-bot__hand--min" d="M36 34 L36 21.5"
      stroke="url(#{{ $grad }})" stroke-width="2" stroke-linecap="round"/>
<circle class="ac-bot__core" cx="36" cy="34" r="2.3" fill="url(#{{ $grad }})"/>

<g class="ac-bot__eyes">
    <ellipse class="ac-bot__eye" cx="28" cy="29.5" rx="3.4" ry="4.2" fill="#e0f2fe"/>
    <ellipse class="ac-bot__eye" cx="44" cy="29.5" rx="3.4" ry="4.2" fill="#e0f2fe"/>
</g>
<g class="ac-bot__eyes-happy" stroke="#e0f2fe" stroke-width="2.3" stroke-linecap="round" fill="none">
    <path d="M24.8 31 q3.2 -4.4 6.4 0"/>
    <path d="M40.8 31 q3.2 -4.4 6.4 0"/>
</g>

<path class="ac-bot__mouth" d="M31.5 43 q4.5 3.6 9 0" fill="none"
      stroke="rgba(148,163,184,.55)" stroke-width="1.7" stroke-linecap="round"/>

<g class="ac-bot__think" fill="url(#{{ $grad }})">
    <circle cx="60" cy="12" r="2.2"/>
    <circle cx="65.5" cy="7.5" r="1.6"/>
    <circle cx="69" cy="4" r="1.1"/>
</g>

<g class="ac-bot__check">
    <circle cx="57" cy="55" r="8.5" fill="#10b981"/>
    <path d="M53 55.2 L55.8 58 L61.2 52.6" fill="none"
          stroke="#052e1b" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
</g>
