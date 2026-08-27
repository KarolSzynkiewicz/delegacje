{{-- forge / Chrono: twórca — klocki + plus, rzuca iskrami-pomysłami gdy myśli. --}}
<circle class="ac-bot__aura" cx="36" cy="40" r="34" fill="url(#{{ $glow }})"/>

{{-- plus na antenie --}}
<path class="ac-bot__antenna" d="M36 12 L36 7" stroke="url(#{{ $grad }})" stroke-width="2" stroke-linecap="round"/>
<g class="ac-bot__spark" stroke="url(#{{ $grad }})" stroke-width="2.2" stroke-linecap="round">
    <path d="M36 1.5 v5"/>
    <path d="M33.5 4 h5"/>
</g>

{{-- kwadratowa głowa-„moduł” --}}
<rect class="ac-bot__head" x="12" y="12" width="48" height="34" rx="12"
      fill="#0d1424" stroke="url(#{{ $grad }})" stroke-width="2"/>

{{-- panel blueprint --}}
<rect x="18" y="18" width="36" height="22" rx="7"
      fill="#050912" stroke="rgba(148,163,184,.14)" stroke-width="1"/>

{{-- siatka blueprint (subtelna) --}}
<g stroke="rgba(59,130,246,.22)" stroke-width="1">
    <path d="M30 18 v22"/>
    <path d="M42 18 v22"/>
    <path d="M18 29 h36"/>
</g>

<g class="ac-bot__eyes">
    <rect class="ac-bot__eye" x="23.5" y="24" width="7" height="8" rx="2.2" fill="#93c5fd"/>
    <rect class="ac-bot__eye" x="41.5" y="24" width="7" height="8" rx="2.2" fill="#93c5fd"/>
</g>
<g class="ac-bot__eyes-happy" stroke="#93c5fd" stroke-width="2.2" stroke-linecap="round" fill="none">
    <path d="M24.5 29 q3.5 -4.5 7 0"/>
    <path d="M42.5 29 q3.5 -4.5 7 0"/>
</g>

<path d="M31.5 46 h9 v4.5 h-9 z" fill="#0d1424" stroke="url(#{{ $grad }})" stroke-width="1.2"/>

{{-- tułów z „klockami” --}}
<rect class="ac-bot__body" x="13" y="50.5" width="46" height="27" rx="10"
      fill="#0d1424" stroke="url(#{{ $grad }})" stroke-width="1.7"/>

<g fill="none" stroke="url(#{{ $grad }})" stroke-width="1.6">
    <rect x="20" y="56" width="12" height="10" rx="2.5"/>
    <rect x="34" y="56" width="12" height="10" rx="2.5"/>
    <rect x="27" y="64" width="12" height="8" rx="2.5" opacity=".7"/>
</g>

<circle class="ac-bot__scan" cx="36" cy="61" r="12" fill="none"
        stroke="url(#{{ $grad }})" stroke-width="1.4" stroke-linecap="round"
        stroke-dasharray="11 64" opacity=".5"/>

<path class="ac-bot__hand ac-bot__hand--hour" d="M36 61 L40 63.5"
      stroke="#f59e0b" stroke-width="1.7" stroke-linecap="round" opacity=".35"/>
<path class="ac-bot__hand ac-bot__hand--min" d="M36 61 L36 55"
      stroke="url(#{{ $grad }})" stroke-width="1.4" stroke-linecap="round" opacity=".35"/>
<circle class="ac-bot__core" cx="36" cy="61" r="1.7" fill="url(#{{ $grad }})" opacity=".55"/>

{{-- tworca rzuca pomyslami: iskry-gwiazdki wylatujace znad kuzni --}}
<g class="ac-bot__ideas" fill="url(#{{ $grad }})">
    <path class="ac-bot__idea" style="--dx:11px; --dy:-15px"
          d="M55 12 l1.3 2.6 l2.6 1.3 l-2.6 1.3 l-1.3 2.6 l-1.3 -2.6 l-2.6 -1.3 l2.6 -1.3 Z"/>
    <path class="ac-bot__idea" style="--dx:18px; --dy:-4px"
          d="M64 8 l1.1 2.2 l2.2 1.1 l-2.2 1.1 l-1.1 2.2 l-1.1 -2.2 l-2.2 -1.1 l2.2 -1.1 Z"/>
    <path class="ac-bot__idea" style="--dx:14px; --dy:8px"
          d="M60 18 l.9 1.8 l1.8 .9 l-1.8 .9 l-.9 1.8 l-.9 -1.8 l-1.8 -.9 l1.8 -.9 Z"/>
</g>

<g class="ac-bot__check">
    <circle cx="57" cy="70" r="8.5" fill="#10b981"/>
    <path d="M53 70.2 L55.8 73 L61.2 67.6" fill="none"
          stroke="#052e1b" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
</g>
