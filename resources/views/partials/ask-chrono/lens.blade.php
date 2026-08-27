{{-- lens / Argus: analityk — jedno duże oko-optyka + HUD, wiązka radaru w dół gdy myśli. --}}
<circle class="ac-bot__aura" cx="36" cy="40" r="34" fill="url(#{{ $glow }})"/>

<path class="ac-bot__antenna" d="M36 11 L36 6" stroke="url(#{{ $grad }})" stroke-width="2" stroke-linecap="round"/>
<circle class="ac-bot__spark" cx="36" cy="3.5" r="2.4" fill="url(#{{ $grad }})"/>

{{-- hełm / wizjer z jedną soczewką --}}
<rect class="ac-bot__head" x="10" y="11" width="52" height="36" rx="16"
      fill="#0d1424" stroke="url(#{{ $grad }})" stroke-width="2"/>

<rect x="16" y="17" width="40" height="22" rx="11"
      fill="#050912" stroke="rgba(148,163,184,.16)" stroke-width="1"/>

{{-- ramka HUD wokół oka --}}
<g stroke="rgba(125,211,252,.35)" stroke-width="1.2" fill="none">
    <path d="M22 22 h6"/>
    <path d="M44 22 h6"/>
    <path d="M22 34 h6"/>
    <path d="M44 34 h6"/>
</g>

<g class="ac-bot__eyes">
    <circle class="ac-bot__eye" cx="36" cy="28" r="7.2" fill="#0a1628" stroke="url(#{{ $grad }})" stroke-width="1.6"/>
    <circle class="ac-bot__eye" cx="36" cy="28" r="4.2" fill="#7dd3fc"/>
    <circle cx="37.6" cy="26.4" r="1.3" fill="#e0f2fe" opacity=".85"/>
</g>
<g class="ac-bot__eyes-happy" stroke="#7dd3fc" stroke-width="2.2" stroke-linecap="round" fill="none">
    <path d="M29.5 29.5 q6.5 -6 13 0"/>
</g>

<path d="M31.5 47 h9 v4 h-9 z" fill="#0d1424" stroke="url(#{{ $grad }})" stroke-width="1.2"/>

<rect class="ac-bot__body" x="13" y="51" width="46" height="26" rx="12"
      fill="#0d1424" stroke="url(#{{ $grad }})" stroke-width="1.7"/>

{{-- mini-raport: 3 paski danych na klatce --}}
<g stroke="url(#{{ $grad }})" stroke-width="2" stroke-linecap="round" opacity=".75">
    <path d="M24 60 h10"/>
    <path d="M24 65 h16"/>
    <path d="M24 70 h7"/>
</g>

{{-- wiazka radaru: swieci w dol z oka, przy myśleniu zamiata w lewo/prawo --}}
<g class="ac-bot__beam">
    <path d="M36 28 L18 78 L54 78 Z" fill="url(#{{ $grad }})" opacity=".16"/>
    <path d="M36 28 L25 78" stroke="url(#{{ $grad }})" stroke-width="1" opacity=".4"/>
    <path d="M36 28 L47 78" stroke="url(#{{ $grad }})" stroke-width="1" opacity=".4"/>
</g>

{{-- ukryte wskazowki (pivot przy oku) - animacja thinking --}}
<path class="ac-bot__hand ac-bot__hand--hour" d="M36 28 L39.2 30.2"
      stroke="#f59e0b" stroke-width="1.6" stroke-linecap="round" opacity=".35"/>
<path class="ac-bot__hand ac-bot__hand--min" d="M36 28 L36 23.5"
      stroke="url(#{{ $grad }})" stroke-width="1.3" stroke-linecap="round" opacity=".35"/>
<circle class="ac-bot__core" cx="36" cy="28" r="1.4" fill="url(#{{ $grad }})" opacity=".5"/>

<g class="ac-bot__think" fill="url(#{{ $grad }})">
    <circle cx="57" cy="10" r="2.2"/>
    <circle cx="62.5" cy="6" r="1.6"/>
    <circle cx="66.5" cy="3" r="1.1"/>
</g>

<g class="ac-bot__check">
    <circle cx="57" cy="70" r="8.5" fill="#10b981"/>
    <path d="M53 70.2 L55.8 73 L61.2 67.6" fill="none"
          stroke="#052e1b" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
</g>
