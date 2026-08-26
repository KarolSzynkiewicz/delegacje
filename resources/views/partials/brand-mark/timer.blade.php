{{-- „timer” — minutnik: pokrętło na górze i gradientowy łuk odliczania
     biegnący od 12 do 4:00, czyli dokładnie tam, gdzie stoi wskazówka.
     Tarcza jest przesunięta w dół (cy 21.5), żeby pokrętło nie rozjechało
     optycznego środka znaku. --}}
<rect class="cl-mark__knob" x="17.6" y="1.4" width="4.8" height="5.2" rx="2.2"
      fill="url(#{{ $grad }})"/>

<circle class="cl-mark__ring" cx="20" cy="21.5" r="15.5" fill="none"
        stroke="#334155" stroke-width="1.8"/>

<path class="cl-mark__countdown" d="M20 6 A15.5 15.5 0 0 1 33.42 29.25" fill="none"
      stroke="url(#{{ $grad }})" stroke-width="3.2" stroke-linecap="round"/>

<path class="cl-mark__hand cl-mark__hand--min" d="M20 21.5 L20 10.5"
      stroke="#e2e8f0" stroke-width="1.8" stroke-linecap="round"/>
<path class="cl-mark__hand cl-mark__hand--hour" d="M20 21.5 L27.8 26"
      stroke="#f59e0b" stroke-width="2.6" stroke-linecap="round"/>
<circle class="cl-mark__pivot" cx="20" cy="21.5" r="2.2" fill="url(#{{ $grad }})"/>
