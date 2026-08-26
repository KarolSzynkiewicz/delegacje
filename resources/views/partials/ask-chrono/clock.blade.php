{{-- Wariant „clock”: tarcza zegara jako twarz, antena i tułów. --}}
<circle class="ac-bot__aura" cx="36" cy="38" r="34" fill="url(#{{ $glow }})"/>

<path class="ac-bot__antenna" d="M36 12 L36 7" stroke="url(#{{ $grad }})" stroke-width="2" stroke-linecap="round"/>
<circle class="ac-bot__spark" cx="36" cy="4.5" r="3" fill="url(#{{ $grad }})"/>

<path class="ac-bot__body"
      d="M21 78 q-4 0 -4 -4 v-6 q0 -8 8 -8 h22 q8 0 8 8 v6 q0 4 -4 4 z"
      fill="#0d1424" stroke="url(#{{ $grad }})" stroke-width="1.6"/>
<circle class="ac-bot__core" cx="36" cy="70" r="2.8" fill="url(#{{ $grad }})"/>

<rect class="ac-bot__head" x="8" y="12" width="56" height="48" rx="17"
      fill="#0d1424" stroke="url(#{{ $grad }})" stroke-width="2"/>

<circle class="ac-bot__dial" cx="36" cy="36" r="18" fill="none"
        stroke="rgba(148,163,184,.22)" stroke-width="1.2"/>

<g class="ac-bot__ticks" stroke="rgba(148,163,184,.4)" stroke-width="1.6" stroke-linecap="round">
    <path d="M36 19.5 L36 22.5"/>
    <path d="M52.5 36 L49.5 36"/>
    <path d="M36 52.5 L36 49.5"/>
    <path d="M19.5 36 L22.5 36"/>
</g>

<circle class="ac-bot__scan" cx="36" cy="36" r="18" fill="none"
        stroke="url(#{{ $grad }})" stroke-width="1.8" stroke-linecap="round"
        stroke-dasharray="16 97"/>

<path class="ac-bot__hand ac-bot__hand--hour" d="M36 36 L44 40.6"
      stroke="#f59e0b" stroke-width="3" stroke-linecap="round"/>
<path class="ac-bot__hand ac-bot__hand--min" d="M36 36 L36 23"
      stroke="url(#{{ $grad }})" stroke-width="2" stroke-linecap="round"/>
<circle class="ac-bot__pivot" cx="36" cy="36" r="2.4" fill="url(#{{ $grad }})"/>

<g class="ac-bot__eyes">
    <ellipse class="ac-bot__eye" cx="27" cy="31" rx="3.6" ry="4.4" fill="#e0f2fe"/>
    <ellipse class="ac-bot__eye" cx="45" cy="31" rx="3.6" ry="4.4" fill="#e0f2fe"/>
</g>
<g class="ac-bot__eyes-happy" stroke="#e0f2fe" stroke-width="2.4" stroke-linecap="round" fill="none">
    <path d="M23.6 32.5 q3.4 -4.6 6.8 0"/>
    <path d="M41.6 32.5 q3.4 -4.6 6.8 0"/>
</g>

<path class="ac-bot__mouth" d="M31 45.5 q5 4 10 0" fill="none"
      stroke="rgba(148,163,184,.55)" stroke-width="1.8" stroke-linecap="round"/>

<g class="ac-bot__think" fill="url(#{{ $grad }})">
    <circle cx="56" cy="11" r="2.2"/>
    <circle cx="62" cy="6.5" r="1.6"/>
    <circle cx="66.5" cy="3" r="1.1"/>
</g>

<g class="ac-bot__check">
    <circle cx="56" cy="55" r="9" fill="#10b981"/>
    <path d="M51.8 55.2 L54.8 58.2 L60.4 52.4" fill="none"
          stroke="#052e1b" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/>
</g>
