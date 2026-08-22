<svg xmlns="http://www.w3.org/2000/svg"
     viewBox="0 0 40 40"
     width="40"
     height="40"
     aria-label="ChronoLogic logo">
  {{-- Znak marki ChronoLogic: tarcza zegara ze wskazówkami w tym samym
       niebiesko-fioletowym przejściu kolorów co gradient tytułów stron
       (--primary #3b82f6 → --accent #a855f7 w app.css), żeby całość
       (logo, tytuł, akcenty w tabelach) trzymała się jednej palety. --}}
  <defs>
    <linearGradient id="chronoLogoGrad" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#3b82f6"/>
      <stop offset="100%" stop-color="#a855f7"/>
    </linearGradient>
  </defs>
  <circle cx="20" cy="20" r="17" fill="none" stroke="#1e2740" stroke-width="2"/>
  <path d="M20 20 L20 6" stroke="url(#chronoLogoGrad)" stroke-width="2.2" stroke-linecap="round"/>
  <path d="M20 20 L29 25" stroke="url(#chronoLogoGrad)" stroke-width="2.2" stroke-linecap="round"/>
  <circle cx="20" cy="20" r="2.4" fill="#a855f7"/>
</svg>
