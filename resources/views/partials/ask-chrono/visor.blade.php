{{-- visor / Edi: redaktor — kartka z tekstem w lewej rece, wykres w prawej --}}
<circle class="ac-bot__aura" cx="36" cy="40" r="34" fill="url(#{{ $glow }})"/>

<path class="ac-bot__antenna" d="M36 10 L36 5.5" stroke="url(#{{ $grad }})" stroke-width="2" stroke-linecap="round"/>
<circle class="ac-bot__spark" cx="36" cy="3" r="2.6" fill="url(#{{ $grad }})"/>

<rect class="ac-bot__head" x="9" y="10" width="54" height="38" rx="15"
      fill="#0d1424" stroke="url(#{{ $grad }})" stroke-width="2"/>

<rect x="15" y="17" width="42" height="21" rx="10.5"
      fill="#050912" stroke="rgba(148,163,184,.18)" stroke-width="1"/>

<g stroke="rgba(125,211,252,.4)" stroke-width="1.2" fill="none">
    <path d="M18 20 h5 M18 20 v4"/>
    <path d="M54 20 h-5 M54 20 v4"/>
    <path d="M18 35 h5 M18 35 v-4"/>
    <path d="M54 35 h-5 M54 35 v-4"/>
</g>

<g class="ac-bot__eyes">
    <ellipse class="ac-bot__eye" cx="28" cy="27.5" rx="3.4" ry="3.9" fill="#7dd3fc"/>
    <ellipse class="ac-bot__eye" cx="44" cy="27.5" rx="3.4" ry="3.9" fill="#7dd3fc"/>
</g>
<g class="ac-bot__eyes-happy" stroke="#7dd3fc" stroke-width="2.4" stroke-linecap="round" fill="none">
    <path d="M24.6 29 q3.4 -4.4 6.8 0"/>
    <path d="M40.6 29 q3.4 -4.4 6.8 0"/>
</g>

<path d="M31.5 48 h9 v4.5 h-9 z" fill="#0d1424" stroke="url(#{{ $grad }})" stroke-width="1.3"/>

<rect class="ac-bot__body" x="14" y="52" width="44" height="26" rx="12"
      fill="#0d1424" stroke="url(#{{ $grad }})" stroke-width="1.7"/>

{{-- maly dial brand (pivot) --}}
<circle class="ac-bot__scan" cx="36" cy="65" r="7" fill="none"
        stroke="url(#{{ $grad }})" stroke-width="1.3" stroke-linecap="round"
        stroke-dasharray="6 38" opacity=".5"/>
<path class="ac-bot__hand ac-bot__hand--hour" d="M36 65 L39.2 67"
      stroke="#f59e0b" stroke-width="1.3" stroke-linecap="round" opacity=".3"/>
<path class="ac-bot__hand ac-bot__hand--min" d="M36 65 L36 60.5"
      stroke="url(#{{ $grad }})" stroke-width="1.1" stroke-linecap="round" opacity=".3"/>
<circle class="ac-bot__core" cx="36" cy="65" r="1.4" fill="url(#{{ $grad }})" opacity=".45"/>

{{-- lewa reka: kartka z tekstem — to, co redaktor przyjmuje --}}
<g class="ac-bot__artifact-left">
    <path d="M20 58 q-5 1 -10 6" fill="none" stroke="url(#{{ $grad }})" stroke-width="2.3" stroke-linecap="round"/>
    <circle cx="19" cy="58.5" r="2.1" fill="#0d1424" stroke="url(#{{ $grad }})" stroke-width="1.2"/>
    <rect x="3" y="55" width="14" height="18" rx="2.5"
          fill="#050912" stroke="url(#{{ $grad }})" stroke-width="1.5"/>
    <g stroke="rgba(148,163,184,.55)" stroke-width="1.6" stroke-linecap="round">
        <path d="M6.5 60 h8"/>
        <path d="M6.5 64.5 h8"/>
        <path d="M6.5 69 h5"/>
    </g>
</g>

{{-- prawa reka: kartka z wykresem — to, co redaktor oddaje --}}
<g class="ac-bot__artifact">
    <path d="M52 58 q5 1 10 6" fill="none" stroke="url(#{{ $grad }})" stroke-width="2.3" stroke-linecap="round"/>
    <circle cx="53" cy="58.5" r="2.1" fill="#0d1424" stroke="url(#{{ $grad }})" stroke-width="1.2"/>
    <rect x="55" y="55" width="14" height="18" rx="2.5"
          fill="#050912" stroke="url(#{{ $grad }})" stroke-width="1.5"/>
    <g fill="url(#{{ $grad }})" opacity=".9">
        <rect x="57.5" y="66" width="2.2" height="4.5" rx=".5"/>
        <rect x="60.5" y="63" width="2.2" height="7.5" rx=".5"/>
        <rect x="63.5" y="60.5" width="2.2" height="10" rx=".5"/>
    </g>
</g>

<g class="ac-bot__think" fill="url(#{{ $grad }})">
    <circle cx="57" cy="9" r="2.2"/>
    <circle cx="62.5" cy="5" r="1.6"/>
    <circle cx="66.5" cy="2" r="1.1"/>
</g>

<g class="ac-bot__check">
    <circle cx="14" cy="68" r="8" fill="#10b981"/>
    <path d="M10.2 68.2 L12.8 70.8 L18 65.4" fill="none"
          stroke="#052e1b" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round"/>
</g>
