{{-- Wariant „visor”: oczy w ciemnym wizjerze, zegar na klatce piersiowej. --}}
<circle class="ac-bot__aura" cx="36" cy="40" r="34" fill="url(#{{ $glow }})"/>

<path class="ac-bot__antenna" d="M36 10 L36 5.5" stroke="url(#{{ $grad }})" stroke-width="2" stroke-linecap="round"/>
<circle class="ac-bot__spark" cx="36" cy="3" r="2.6" fill="url(#{{ $grad }})"/>

<rect class="ac-bot__head" x="9" y="10" width="54" height="38" rx="15"
      fill="#0d1424" stroke="url(#{{ $grad }})" stroke-width="2"/>

<rect x="15" y="17" width="42" height="21" rx="10.5"
      fill="#050912" stroke="rgba(148,163,184,.18)" stroke-width="1"/>

<g class="ac-bot__eyes">
    <ellipse class="ac-bot__eye" cx="28" cy="27.5" rx="3.4" ry="3.9" fill="#7dd3fc"/>
    <ellipse class="ac-bot__eye" cx="44" cy="27.5" rx="3.4" ry="3.9" fill="#7dd3fc"/>
</g>
<g class="ac-bot__eyes-happy" stroke="#7dd3fc" stroke-width="2.4" stroke-linecap="round" fill="none">
    <path d="M24.6 29 q3.4 -4.4 6.8 0"/>
    <path d="M40.6 29 q3.4 -4.4 6.8 0"/>
</g>

<path d="M31.5 48 h9 v4.5 h-9 z" fill="#0d1424" stroke="url(#{{ $grad }})" stroke-width="1.3"/>

<rect class="ac-bot__body" x="12" y="52" width="48" height="27" rx="13"
      fill="#0d1424" stroke="url(#{{ $grad }})" stroke-width="1.7"/>

<circle class="ac-bot__dial" cx="36" cy="65.5" r="9.5" fill="none"
        stroke="rgba(148,163,184,.25)" stroke-width="1.1"/>

<g class="ac-bot__ticks" stroke="rgba(148,163,184,.4)" stroke-width="1.3" stroke-linecap="round">
    <path d="M36 57.5 L36 59.3"/>
    <path d="M44 65.5 L42.2 65.5"/>
    <path d="M36 73.5 L36 71.7"/>
    <path d="M28 65.5 L29.8 65.5"/>
</g>

<circle class="ac-bot__scan" cx="36" cy="65.5" r="9.5" fill="none"
        stroke="url(#{{ $grad }})" stroke-width="1.6" stroke-linecap="round"
        stroke-dasharray="9 51"/>

<path class="ac-bot__hand ac-bot__hand--hour" d="M36 65.5 L40.4 68"
      stroke="#f59e0b" stroke-width="2.4" stroke-linecap="round"/>
<path class="ac-bot__hand ac-bot__hand--min" d="M36 65.5 L36 58.8"
      stroke="url(#{{ $grad }})" stroke-width="1.8" stroke-linecap="round"/>
<circle class="ac-bot__core" cx="36" cy="65.5" r="2" fill="url(#{{ $grad }})"/>

<g class="ac-bot__think" fill="url(#{{ $grad }})">
    <circle cx="57" cy="9" r="2.2"/>
    <circle cx="62.5" cy="5" r="1.6"/>
    <circle cx="66.5" cy="2" r="1.1"/>
</g>

<g class="ac-bot__check">
    <circle cx="57" cy="70" r="8.5" fill="#10b981"/>
    <path d="M53 70.2 L55.8 73 L61.2 67.6" fill="none"
          stroke="#052e1b" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
</g>
