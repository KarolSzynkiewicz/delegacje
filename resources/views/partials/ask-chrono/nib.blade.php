{{-- Kandydat „nib”: redaktor — antena-stalówka + linie tekstu (domena: edycja). --}}
<circle class="ac-bot__aura" cx="36" cy="40" r="34" fill="url(#{{ $glow }})"/>

{{-- stalówka zamiast kulki na antenie --}}
<path class="ac-bot__antenna" d="M36 14 L36 8" stroke="url(#{{ $grad }})" stroke-width="2" stroke-linecap="round"/>
<path class="ac-bot__spark" d="M36 2.5 L38.4 7.5 L36 6.4 L33.6 7.5 Z"
      fill="url(#{{ $grad }})"/>

<rect class="ac-bot__head" x="11" y="14" width="50" height="32" rx="14"
      fill="#0d1424" stroke="url(#{{ $grad }})" stroke-width="2"/>

{{-- „kartka” na twarzy --}}
<rect x="18" y="20" width="36" height="20" rx="6"
      fill="#050912" stroke="rgba(148,163,184,.14)" stroke-width="1"/>

<g class="ac-bot__eyes">
    <ellipse class="ac-bot__eye" cx="27" cy="28" rx="3.1" ry="3.6" fill="#e0f2fe"/>
    <ellipse class="ac-bot__eye" cx="45" cy="28" rx="3.1" ry="3.6" fill="#e0f2fe"/>
</g>
<g class="ac-bot__eyes-happy" stroke="#e0f2fe" stroke-width="2.2" stroke-linecap="round" fill="none">
    <path d="M24 29.2 q3 -4 6 0"/>
    <path d="M42 29.2 q3 -4 6 0"/>
</g>

{{-- uśmiech jak podkreślenie --}}
<path class="ac-bot__mouth" d="M31 35.5 h10" fill="none"
      stroke="rgba(148,163,184,.45)" stroke-width="1.6" stroke-linecap="round"/>

<path d="M31.5 46 h9 v4 h-9 z" fill="#0d1424" stroke="url(#{{ $grad }})" stroke-width="1.2"/>

<rect class="ac-bot__body" x="13" y="50" width="46" height="28" rx="11"
      fill="#0d1424" stroke="url(#{{ $grad }})" stroke-width="1.7"/>

{{-- linie tekstu / mutacji --}}
<g stroke="rgba(148,163,184,.45)" stroke-width="1.7" stroke-linecap="round">
    <path d="M22 58 h28"/>
    <path d="M22 63.5 h22"/>
    <path d="M22 69 h16"/>
</g>
{{-- kursor edycji --}}
<path d="M52 57.5 v4.5" stroke="url(#{{ $grad }})" stroke-width="1.8" stroke-linecap="round"/>

<circle class="ac-bot__scan" cx="36" cy="64" r="11" fill="none"
        stroke="url(#{{ $grad }})" stroke-width="1.4" stroke-linecap="round"
        stroke-dasharray="10 59" opacity=".55"/>

<path class="ac-bot__hand ac-bot__hand--hour" d="M36 64 L39.8 66.2"
      stroke="#f59e0b" stroke-width="1.7" stroke-linecap="round" opacity=".4"/>
<path class="ac-bot__hand ac-bot__hand--min" d="M36 64 L36 58.5"
      stroke="url(#{{ $grad }})" stroke-width="1.4" stroke-linecap="round" opacity=".4"/>
<circle class="ac-bot__core" cx="36" cy="64" r="1.6" fill="url(#{{ $grad }})" opacity=".55"/>

<g class="ac-bot__think" fill="url(#{{ $grad }})">
    <circle cx="57" cy="11" r="2.2"/>
    <circle cx="62.5" cy="7" r="1.6"/>
    <circle cx="66.5" cy="4" r="1.1"/>
</g>

<g class="ac-bot__check">
    <circle cx="57" cy="70" r="8.5" fill="#10b981"/>
    <path d="M53 70.2 L55.8 73 L61.2 67.6" fill="none"
          stroke="#052e1b" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
</g>
