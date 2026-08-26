{{-- „dial” — obecny znak: pełny pierścień, wskazówki na 4:00. --}}
<circle class="cl-mark__ring" cx="20" cy="20" r="16.5" fill="none" stroke="#334155" stroke-width="1.8"/>
<path class="cl-mark__hand cl-mark__hand--min" d="M20 20 L20 7.5"
      stroke="url(#{{ $grad }})" stroke-width="1.9" stroke-linecap="round"/>
<path class="cl-mark__hand cl-mark__hand--hour" d="M20 20 L28 24.6"
      stroke="#f59e0b" stroke-width="2.7" stroke-linecap="round"/>
<circle class="cl-mark__pivot" cx="20" cy="20" r="2.3" fill="url(#{{ $grad }})"/>
