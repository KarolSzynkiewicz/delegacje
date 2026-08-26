{{-- „aperture” — pierścień pocięty na cztery segmenty, jak przysłona. --}}
<circle class="cl-mark__ring cl-mark__ring--seg" cx="20" cy="20" r="16.5" fill="none"
        stroke="url(#{{ $grad }})" stroke-width="2.4" stroke-linecap="round"
        stroke-dasharray="19.9 6.02" transform="rotate(-58 20 20)"/>
<circle cx="20" cy="20" r="11" fill="none" stroke="rgba(148,163,184,.22)" stroke-width="1"/>
<path class="cl-mark__hand cl-mark__hand--min" d="M20 20 L20 10.2"
      stroke="url(#{{ $grad }})" stroke-width="1.9" stroke-linecap="round"/>
<path class="cl-mark__hand cl-mark__hand--hour" d="M20 20 L26.5 23.7"
      stroke="#f59e0b" stroke-width="2.6" stroke-linecap="round"/>
<circle class="cl-mark__pivot" cx="20" cy="20" r="2.1" fill="url(#{{ $grad }})"/>
