{{-- „monogram” — litera C zbudowana z tarczy; wskazówka godzinowa celuje
     w wycięcie, więc znak czyta się jednocześnie jako C i jako zegar. --}}
<circle class="cl-mark__ring" cx="20" cy="20" r="15.5" fill="none"
        stroke="url(#{{ $grad }})" stroke-width="3.4" stroke-linecap="round"
        stroke-dasharray="72 25.4" transform="rotate(47 20 20)"/>
<path class="cl-mark__hand cl-mark__hand--min" d="M20 20 L20 9.8"
      stroke="#e2e8f0" stroke-width="1.8" stroke-linecap="round"/>
<path class="cl-mark__hand cl-mark__hand--hour" d="M20 20 L27.4 24.3"
      stroke="#f59e0b" stroke-width="2.6" stroke-linecap="round"/>
<circle class="cl-mark__pivot" cx="20" cy="20" r="2.2" fill="url(#{{ $grad }})"/>
