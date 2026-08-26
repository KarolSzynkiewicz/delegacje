{{-- Znak marki aplikacji — jedno miejsce, z którego jedzie navbar, nawigacja
     landingu i stopka. Komponent celowo nie przepuszcza atrybutów: wołający
     podają .navbar-logo (height: 5rem), co rozdęłoby znak w pasku do 80 px. --}}
<x-brand-mark variant="monogram" :size="40" />
