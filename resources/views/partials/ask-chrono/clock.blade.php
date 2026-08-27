{{-- clock / Chrono: tworca — trzyma klocek z plusem w prawej rece --}}
<circle class="ac-bot__aura" cx="36" cy="38" r="34" fill="url(#{{ $glow }})"/>

<path class="ac-bot__antenna" d="M36 12 L36 7" stroke="url(#{{ $grad }})" stroke-width="2" stroke-linecap="round"/>
<circle class="ac-bot__spark" cx="36" cy="4.5" r="2.8" fill="url(#{{ $grad }})"/>

{{-- tulow --}}
<path class="ac-bot__body"
      d="M22 78 q-3.5 0 -3.5 -3.5 v-5.5 q0 -7 7 -7 h17 q7 0 7 7 v5.5 q0 3.5 -3.5 3.5 z"
      fill="#0d1424" stroke="url(#{{ $grad }})" stroke-width="1.6"/>
<circle class="ac-bot__core" cx="36" cy="70" r="2.2" fill="url(#{{ $grad }})"/>

{{-- glowa / tarcza --}}
<rect class="ac-bot__head" x="10" y="12" width="52" height="46" rx="16"
      fill="#0d1424" stroke="url(#{{ $grad }})" stroke-width="2"/>

<circle class="ac-bot__dial" cx="36" cy="35" r="16" fill="none"
        stroke="rgba(148,163,184,.22)" stroke-width="1.2"/>
<g class="ac-bot__ticks" stroke="rgba(148,163,184,.4)" stroke-width="1.5" stroke-linecap="round">
    <path d="M36 20.5 L36 23"/>
    <path d="M50.5 35 L48 35"/>
    <path d="M36 49.5 L36 47"/>
    <path d="M21.5 35 L24 35"/>
</g>
<circle class="ac-bot__scan" cx="36" cy="35" r="16" fill="none"
        stroke="url(#{{ $grad }})" stroke-width="1.7" stroke-linecap="round"
        stroke-dasharray="14 88"/>
<path class="ac-bot__hand ac-bot__hand--hour" d="M36 35 L43 39"
      stroke="#f59e0b" stroke-width="2.6" stroke-linecap="round"/>
<path class="ac-bot__hand ac-bot__hand--min" d="M36 35 L36 23.5"
      stroke="url(#{{ $grad }})" stroke-width="1.8" stroke-linecap="round"/>
<circle class="ac-bot__pivot" cx="36" cy="35" r="2.2" fill="url(#{{ $grad }})"/>

<g class="ac-bot__eyes">
    <ellipse class="ac-bot__eye" cx="28" cy="31" rx="3.2" ry="3.8" fill="#e0f2fe"/>
    <ellipse class="ac-bot__eye" cx="44" cy="31" rx="3.2" ry="3.8" fill="#e0f2fe"/>
</g>
<g class="ac-bot__eyes-happy" stroke="#e0f2fe" stroke-width="2.2" stroke-linecap="round" fill="none">
    <path d="M25 32.2 q3.2 -4.2 6.4 0"/>
    <path d="M41 32.2 q3.2 -4.2 6.4 0"/>
</g>
<path class="ac-bot__mouth" d="M31 44 q5 3.5 10 0" fill="none"
      stroke="rgba(148,163,184,.5)" stroke-width="1.6" stroke-linecap="round"/>

{{-- robocza reka: trzyma klocek z plusem --}}
<g class="ac-bot__artifact">
    <path d="M48 62 q6 -2 12 2" fill="none" stroke="url(#{{ $grad }})" stroke-width="2.4" stroke-linecap="round"/>
    <circle cx="49.5" cy="61.5" r="2.2" fill="#0d1424" stroke="url(#{{ $grad }})" stroke-width="1.3"/>
    <rect x="56" y="58" width="12" height="12" rx="2.5"
          fill="#0d1424" stroke="url(#{{ $grad }})" stroke-width="1.6"/>
    <g stroke="url(#{{ $grad }})" stroke-width="1.8" stroke-linecap="round">
        <path d="M62 61 v6"/>
        <path d="M59 64 h6"/>
    </g>
</g>

<g class="ac-bot__think" fill="url(#{{ $grad }})">
    <circle cx="56" cy="11" r="2.2"/>
    <circle cx="62" cy="6.5" r="1.6"/>
    <circle cx="66.5" cy="3" r="1.1"/>
</g>

<g class="ac-bot__check">
    <circle cx="14" cy="66" r="8" fill="#10b981"/>
    <path d="M10.2 66.2 L12.8 68.8 L18 63.4" fill="none"
          stroke="#052e1b" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"/>
</g>
