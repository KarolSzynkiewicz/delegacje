# Plan Refaktoryzacji Frontendu - Tailwind → Bootstrap

## Status: W TRAKCIE

## Zasady

1. **Spójność ponad wszystko** - jeden system UI, jedna paleta, jeden język wizualny
2. **Kolory** - tylko semantyczne Bootstrap (danger, warning, success, primary, secondary, info)
3. **Dark mode** - pełne wsparcie przez `data-bs-theme="dark"`
4. **Komponenty** - wszystkie powtarzalne elementy jako komponenty Blade
5. **Linki vs Przyciski** - link = nawigacja, button = akcja

## Komponenty Utworzone ✅

- `x-badge` - badge'y z semantycznymi kolorami
- `x-alert` - alerty z ikonami i dismissible
- `x-card` - karty z header/footer
- `x-section-header` - nagłówki sekcji z akcjami
- `x-button` - przyciski z wariantami
- `x-link-button` - linki wyglądające jak przyciski

## Serwisy Utworzone ✅

- `StatusColorService` - mapowanie statusów na kolory Bootstrap

## Do Zrobienia

### 1. Migracja Komponentów Livewire (W TRAKCIE)
- [x] projects-table - badge'y
- [ ] assignments-table - badge'y
- [ ] vehicles-table - badge'y
- [ ] employees-table - badge'y
- [ ] accommodations-table - badge'y
- [ ] vehicle-assignments-table - badge'y
- [ ] accommodation-assignments-table - badge'y

### 2. Migracja Widoków Index
- [ ] projects/index
- [ ] assignments/index
- [ ] vehicles/index
- [ ] employees/index
- [ ] accommodations/index
- [ ] rotations/index
- [ ] vehicle-assignments/index
- [ ] accommodation-assignments/index

### 3. Migracja Widoków Formularzy
- [ ] Wszystkie create.blade.php
- [ ] Wszystkie edit.blade.php

### 4. Migracja Widoków Szczegółów
- [ ] Wszystkie show.blade.php

### 5. Usunięcie Tailwind
- [ ] Usunąć tailwind.config.js
- [ ] Usunąć postcss.config.js
- [ ] Usunąć @tailwind z app.css
- [ ] Usunąć wszystkie klasy Tailwind z widoków

### 6. Dark Mode
- [ ] Sprawdzić wszystkie widoki w dark mode
- [ ] Upewnić się, że tekst jest czytelny
- [ ] Sprawdzić linki, alerty, buttony

### 7. Spójność
- [ ] Porównać widoki między sobą
- [ ] Upewnić się, że te same elementy wyglądają identycznie
- [ ] Refaktoryzować niespójności do komponentów
