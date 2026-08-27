{{-- Kandydat „ferry”: kurier — kapsuła z strzałkami in/out (domena: transfer danych). --}}
<circle class="ac-bot__aura" cx="36" cy="38" r="34" fill="url(#{{ $glow }})"/>

{{-- antena-beacon --}}
<path class="ac-bot__antenna" d="M36 10 L36 5.5" stroke="url(#{{ $grad }})" stroke-width="2" stroke-linecap="round"/>
<circle class="ac-bot__spark" cx="36" cy="3" r="2.5" fill="url(#{{ $grad }})"/>

{{-- głowa-kapsuła --}}
<ellipse class="ac-bot__head" cx="36" cy="30" rx="26" ry="20"
         fill="#0d1424" stroke="url(#{{ $grad }})" stroke-width="2"/>

<ellipse cx="36" cy="28" rx="18" ry="12"
         fill="#050912" stroke="rgba(148,163,184,.16)" stroke-width="1"/>

<g class="ac-bot__eyes">
    <ellipse class="ac-bot__eye" cx="28" cy="27" rx="3.2" ry="3.8" fill="#c4b5fd"/>
    <ellipse class="ac-bot__eye" cx="44" cy="27" rx="3.2" ry="3.8" fill="#c4b5fd"/>
</g>
<g class="ac-bot__eyes-happy" stroke="#c4b5fd" stroke-width="2.3" stroke-linecap="round" fill="none">
    <path d="M24.8 28.5 q3.2 -4.2 6.4 0"/>
    <path d="M40.8 28.5 q3.2 -4.2 6.4 0"/>
</g>

{{-- szyja --}}
<path d="M31 50 h10 v3.5 h-10 z" fill="#0d1424" stroke="url(#{{ $grad }})" stroke-width="1.2"/>

{{-- tułów-paczka --}}
<rect class="ac-bot__body" x="14" y="53" width="44" height="25" rx="9"
      fill="#0d1424" stroke="url(#{{ $grad }})" stroke-width="1.7"/>

{{-- strzałki import ↓ / export ↑ --}}
<g fill="none" stroke="url(#{{ $grad }})" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
    <path d="M26 60 v10"/>
    <path d="M23 67 l3 3.5 l3 -3.5"/>
    <path d="M46 70 v-10"/>
    <path d="M43 63 l3 -3.5 l3 3.5"/>
</g>

<ellipse class="ac-bot__scan" cx="36" cy="30" rx="26" ry="20" fill="none"
        stroke="url(#{{ $grad }})" stroke-width="1.6" stroke-linecap="round"
        stroke-dasharray="14 120"/>

{{-- wskazówki wokół „rdzenia” między strzałkami — brand clock DNA --}}
<path class="ac-bot__hand ac-bot__hand--hour" d="M36 65.5 L39.5 67.5"
      stroke="#f59e0b" stroke-width="1.8" stroke-linecap="round"/>
<path class="ac-bot__hand ac-bot__hand--min" d="M36 65.5 L36 61"
      stroke="url(#{{ $grad }})" stroke-width="1.5" stroke-linecap="round"/>
<circle class="ac-bot__core" cx="36" cy="65.5" r="2" fill="url(#{{ $grad }})"/>

<g class="ac-bot__think" fill="url(#{{ $grad }})">
    <circle cx="58" cy="10" r="2.2"/>
    <circle cx="63.5" cy="6" r="1.6"/>
    <circle cx="67" cy="3" r="1.1"/>
</g>

<g class="ac-bot__check">
    <circle cx="57" cy="70" r="8.5" fill="#10b981"/>
    <path d="M53 70.2 L55.8 73 L61.2 67.6" fill="none"
          stroke="#052e1b" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
</g>
