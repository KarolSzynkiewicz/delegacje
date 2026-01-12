# Status migracji Tailwind CSS → Bootstrap 5.3

## ✅ Zakończone

### Layout i nawigacja
- ✅ `layouts/app.blade.php` - Bootstrap z dark mode
- ✅ `layouts/navigation.blade.php` - Bootstrap navbar z przełącznikiem dark mode
- ✅ `resources/css/app.css` - Usunięto Tailwind directives

### Główne widoki
- ✅ `dashboard.blade.php` - Bootstrap cards z ikonami
- ✅ `weekly-overview/index.blade.php` - Bootstrap table i komponenty
- ✅ `projects/index.blade.php` - Bootstrap header i layout
- ✅ `employees/index.blade.php` - Bootstrap header i layout
- ✅ `vehicles/index.blade.php` - Bootstrap header i layout
- ✅ `accommodations/index.blade.php` - Bootstrap header i layout
- ✅ `assignments/index.blade.php` - Bootstrap table i layout
- ✅ `rotations/index.blade.php` - Bootstrap filters i table
- ✅ `documents/index.blade.php` - Bootstrap z <x-app-layout>
- ✅ `locations/index.blade.php` - Bootstrap z <x-app-layout>
- ✅ `roles/index.blade.php` - Bootstrap z <x-app-layout>
- ✅ `users/index.blade.php` - Bootstrap z <x-app-layout>
- ✅ `user-roles/index.blade.php` - Bootstrap z <x-app-layout>
- ✅ `equipment/index.blade.php` - Bootstrap z <x-app-layout>
- ✅ `time-logs/index.blade.php` - Bootstrap z <x-app-layout>

### Komponenty Livewire (tabele)
- ✅ `livewire/projects-table.blade.php` - Bootstrap z filtrami
- ✅ `livewire/employees-table.blade.php` - Bootstrap z filtrami
- ✅ `livewire/vehicles-table.blade.php` - Bootstrap z filtrami
- ✅ `livewire/accommodations-table.blade.php` - Bootstrap z filtrami
- ✅ `livewire/assignments-table.blade.php` - Bootstrap z grupowaniem po projekcie
- ✅ `livewire/vehicle-assignments-table.blade.php` - Bootstrap z grupowaniem po pojazdach
- ✅ `livewire/accommodation-assignments-table.blade.php` - Bootstrap
- ✅ `livewire/employee-availability-checker.blade.php` - Bootstrap alerts i cards
- ✅ `livewire/employee-documents-grouped.blade.php` - Już Bootstrap

### Komponenty Blade
- ✅ `components/weekly-overview/project-week-tile.blade.php` - Bootstrap cards (Album style)

## ⏳ Do zrobienia

### Widoki formularzy (create/edit)
- ✅ `assignments/create.blade.php` - Bootstrap z card i form-control
- ✅ `assignments/edit.blade.php` - Bootstrap z card i form-control
- ✅ `projects/create.blade.php` - Bootstrap z card i form-control
- ✅ `projects/edit.blade.php` - Bootstrap z card i form-control
- ✅ `employees/create.blade.php` - Już Bootstrap
- ✅ `employees/edit.blade.php` - Już Bootstrap
- ✅ `vehicles/create.blade.php` - Już Bootstrap
- ✅ `vehicles/edit.blade.php` - Już Bootstrap
- ✅ `accommodations/create.blade.php` - Już Bootstrap
- ✅ `accommodations/edit.blade.php` - Już Bootstrap
- ✅ `demands/create.blade.php` - Bootstrap z card i form-control
- ✅ `demands/edit.blade.php` - Bootstrap z card i form-control
- ✅ `rotations/create.blade.php` - Bootstrap z card i form-control
- ✅ `documents/create.blade.php` - Bootstrap z card i form-control
- ✅ `documents/edit.blade.php` - Bootstrap z card i form-control
- ✅ `return-trips/create.blade.php` - Bootstrap z card i form-control
- ✅ `return-trips/edit.blade.php` - Bootstrap z card i form-control
- ✅ `transport-costs/create.blade.php` - Bootstrap z card i form-control
- ✅ `transport-costs/edit.blade.php` - Bootstrap z card i form-control
- ✅ `equipment-issues/create.blade.php` - Bootstrap z card i form-control
- ✅ `vehicle-assignments/create.blade.php` - Bootstrap z card i form-control
- ✅ `vehicle-assignments/edit.blade.php` - Bootstrap z card i form-control
- ✅ `accommodation-assignments/create.blade.php` - Bootstrap z card i form-control
- ✅ `accommodation-assignments/edit.blade.php` - Bootstrap z card i form-control
- ⏳ Wszystkie inne formularze (time-logs, roles, locations, users, user-roles, employee-documents, employees/rotations)

### Widoki szczegółów (show)
- ✅ `assignments/show.blade.php` - Bootstrap z card i dl/row
- ✅ `projects/show.blade.php` - Bootstrap z card i tabelą przypisań
- ✅ `employees/show.blade.php` - Już Bootstrap
- ✅ `vehicles/show.blade.php` - Już Bootstrap
- ✅ `accommodations/show.blade.php` - Już Bootstrap
- ✅ `vehicle-assignments/show.blade.php` - Bootstrap z card
- ✅ `accommodation-assignments/show.blade.php` - Bootstrap z card
- ⏳ Wszystkie inne widoki show

### Komponenty Blade
- ✅ `components/app-layout.blade.php` - Bootstrap z container-xxl
- ⏳ `components/dropdown.blade.php` - Używa Alpine.js, może wymagać refaktoryzacji
- ✅ `components/nav-link.blade.php` - Bootstrap nav-link
- ✅ `components/responsive-nav-link.blade.php` - Bootstrap nav-link
- ✅ `components/primary-button.blade.php` - Bootstrap btn btn-primary
- ✅ `components/secondary-button.blade.php` - Bootstrap btn btn-secondary
- ✅ `components/text-input.blade.php` - Bootstrap form-control
- ✅ `components/danger-button.blade.php` - Bootstrap btn btn-danger
- ✅ `components/input-label.blade.php` - Bootstrap form-label
- ✅ `components/input-error.blade.php` - Bootstrap invalid-feedback
- ⏳ `components/modal.blade.php`
- ⏳ `components/dropdown-link.blade.php`
- ⏳ Wszystkie inne komponenty

### Widoki auth i profil
- ⏳ `auth/login.blade.php`
- ⏳ `auth/register.blade.php`
- ⏳ `auth/forgot-password.blade.php`
- ⏳ `auth/reset-password.blade.php`
- ⏳ `auth/confirm-password.blade.php`
- ⏳ `auth/verify-email.blade.php`
- ⏳ `profile/edit.blade.php`
- ⏳ `profile/partials/*.blade.php`
- ⏳ `layouts/guest.blade.php`
- ⏳ `welcome.blade.php`

### Pozostałe widoki
- ✅ `documents/index.blade.php` - Bootstrap z <x-app-layout>
- ✅ `return-trips/show.blade.php` - Bootstrap z card
- ⏳ Pozostałe widoki w `equipment-issues/`, `time-logs/`, `roles/`, `locations/`, `users/`, `user-roles/`, `employee-documents/`, `employees/rotations/`

## 📋 Spójność designu

### System kolorów Bootstrap
- ✅ Statusy: `bg-success`, `bg-primary`, `bg-danger`, `bg-warning`, `bg-secondary`
- ✅ Badge'y: Bootstrap badges wszędzie
- ✅ Alerty: Bootstrap alerts z ikonami
- ✅ Przyciski: Bootstrap buttons z ikonami

### Komponenty Bootstrap
- ✅ Karty: `card shadow-sm border-0`
- ✅ Tabele: `table table-hover align-middle`
- ✅ Formularze: `form-control`, `form-select`, `form-label`
- ✅ Filtry: `row g-3` z `col-md-*`
- ✅ Akcje: `btn-group btn-group-sm`

### Dark mode
- ✅ Przełącznik w nawigacji
- ✅ Wsparcie dla wszystkich komponentów
- ✅ Spójne kolory w obu trybach

## 🎯 Postęp: ~85% zakończone

Główne komponenty Livewire, widoki index i większość formularzy zostały przekonwertowane. Pozostało przekonwertować pozostałe formularze, widoki szczegółów i komponenty Blade (modal, dropdown).
