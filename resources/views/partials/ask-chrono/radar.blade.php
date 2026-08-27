{{-- Kandydat „radar”: analityk alt — półkolisty radar / antena (domena: skanuję backlog). --}}
<circle class="ac-bot__aura" cx="36" cy="42" r="33" fill="url(#{{ $glow }})"/>

{{-- czasza radaru nad głową --}}
<path d="M18 22 A18 14 0 0 1 54 22" fill="none"
      stroke="url(#{{ $grad }})" stroke-width="2.2" stroke-linecap="round"/>
<path class="ac-bot__scan" d="M36 22 L36 8"
      stroke="url(#{{ $grad }})" stroke-width="1.8" stroke-linecap="round"/>
<circle class="ac-bot__spark" cx="36" cy="6.5" r="2.3" fill="url(#{{ $grad }})"/>
{{-- łuki zasięgu --}}
<path d="M24 18 A14 10 0 0 1 48 18" fill="none"
      stroke="rgba(125,211,252,.35)" stroke-width="1.2" stroke-linecap="round"/>

<rect class="ac-bot__head" x="12" y="22" width="48" height="30" rx="13"
      fill="#0d1424" stroke="url(#{{ $grad }})" stroke-width="2"/>

<rect x="18" y="27" width="36" height="18" rx="9"
      fill="#050912" stroke="rgba(148,163,184,.16)" stroke-width="1"/>

<g class="ac-bot__eyes">
    <ellipse class="ac-bot__eye" cx="27" cy="36" rx="3.3" ry="3.8" fill="#7dd3fc"/>
    <ellipse class="ac-bot__eye" cx="45" cy="36" rx="3.3" ry="3.8" fill="#7dd3fc"/>
</g>
<g class="ac-bot__eyes-happy" stroke="#7dd3fc" stroke-width="2.3" stroke-linecap="round" fill="none">
    <path d="M23.8 37.2 q3.2 -4.2 6.4 0"/>
    <path d="M41.8 37.2 q3.2 -4.2 6.4 0"/>
</g>

<path d="M31.5 52 h9 v3.5 h-9 z" fill="#0d1424" stroke="url(#{{ $grad }})" stroke-width="1.2"/>

<rect class="ac-bot__body" x="14" y="55" width="44" height="24" rx="11"
      fill="#0d1424" stroke="url(#{{ $grad }})" stroke-width="1.7"/>

{{-- blip-y na „ekranie” klatki --}}
<g fill="url(#{{ $grad }})">
    <circle cx="26" cy="64" r="2"/>
    <circle cx="36" cy="67" r="2.4"/>
    <circle cx="47" cy="62" r="1.7"/>
</g>
<circle cx="36" cy="67" r="7" fill="none" stroke="rgba(125,211,252,.25)" stroke-width="1"/>

<path class="ac-bot__hand ac-bot__hand--hour" d="M36 67 L39.5 69"
      stroke="#f59e0b" stroke-width="1.6" stroke-linecap="round" opacity=".4"/>
<path class="ac-bot__hand ac-bot__hand--min" d="M36 67 L36 62"
      stroke="url(#{{ $grad }})" stroke-width="1.3" stroke-linecap="round" opacity=".4"/>
<circle class="ac-bot__core" cx="36" cy="67" r="1.5" fill="url(#{{ $grad }})" opacity=".6"/>

<g class="ac-bot__think" fill="url(#{{ $grad }})">
    <circle cx="57" cy="14" r="2.2"/>
    <circle cx="62.5" cy="10" r="1.6"/>
    <circle cx="66.5" cy="7" r="1.1"/>
</g>

<g class="ac-bot__check">
    <circle cx="57" cy="70" r="8.5" fill="#10b981"/>
    <path d="M53 70.2 L55.8 73 L61.2 67.6" fill="none"
          stroke="#052e1b" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
</g>
