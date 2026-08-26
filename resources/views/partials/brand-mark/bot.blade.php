{{-- „bot” — Chrono wpisany w znak: dolna połowa tarczy pełni rolę uśmiechu,
     wskazówka minutowa czyta się jak nos, oczy nad cyferblatem. --}}
<rect class="cl-mark__head" x="1.6" y="1.6" width="36.8" height="36.8" rx="11"
      fill="#070a13" stroke="url(#{{ $grad }})" stroke-width="2"/>

<path class="cl-mark__ring" d="M10.5 24.5 a9.5 9.5 0 0 0 19 0" fill="none"
      stroke="rgba(148,163,184,.45)" stroke-width="1.5" stroke-linecap="round"/>

<path class="cl-mark__hand cl-mark__hand--hour" d="M20 24.5 L25.8 27.8"
      stroke="#f59e0b" stroke-width="2.4" stroke-linecap="round"/>
<path class="cl-mark__hand cl-mark__hand--min" d="M20 24.5 L20 17"
      stroke="url(#{{ $grad }})" stroke-width="1.8" stroke-linecap="round"/>
<circle class="cl-mark__pivot" cx="20" cy="24.5" r="1.9" fill="url(#{{ $grad }})"/>

<g class="cl-mark__eyes">
    <circle class="cl-mark__eye" cx="13.6" cy="13.8" r="2.5" fill="#e0f2fe"/>
    <circle class="cl-mark__eye" cx="26.4" cy="13.8" r="2.5" fill="#e0f2fe"/>
</g>
