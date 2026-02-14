# Plan refaktoryzacji weekly-overview/index.blade.php

## Obecny stan: 1436 linii - ZA DUŻO!

## Komponenty do wyekstrahowania:

### 1. **WeeklyNavigation** (~20 linii)
- Nawigacja między tygodniami (prev/next)
- Wyświetlanie numeru tygodnia i dat
- Prosty, niezależny komponent

### 2. **ProjectStatsBadges** (~40 linii) 
- 3 badge z progress barami (Ludzie/Auta/Domy)
- Używane w nagłówku każdego projektu
- Reużywalny

### 3. **RequirementsTable** (~100 linii)
- Tabela zapotrzebowania (role/potrzebni/przypisani)
- Złożona logika, powtarzalna struktura
- Duży komponent ale wart ekstrakcji

### 4. **VehicleCard** (~80 linii)
- Karta pojazdu z progress, zdjęciem, listą osób
- Informacje o zjazdzie
- Sekcja "Obsługuje"
- Powtarza się dla każdego auta

### 5. **AccommodationCard** (~80 linii)
- Karta mieszkania (analogicznie do VehicleCard)
- Prawie identyczna struktura

### 6. **AlertsSection** (~90 linii)
- 4 kolumny alertów (Braki/Nadmiary/Zjazdy/Wyjazdy)
- Złożona ale wyodrębniona logika

### 7. **EmployeesTable** (~130 linii) 
- Tabela z pracownikami projektu
- Kolumny: Imię, Role, Projekty, Auto, Dom, Akcje
- Duża sekcja z formularzem przypisania

### 8. **ExpiringDocumentsCard** (~40 linii)
- Kończące się dokumenty
- Osobny widget

### 9. **ExpiringVehiclesCard** (~90 linii)
- Auta z wygasającymi dokumentami
- Widget z tabelą

### 10. **ExpiringAccommodationsCard** (~70 linii)
- Mieszkania z wygasającymi umowami najmu
- Widget z tabelą

### 11. **EmployeesWithoutProjectCard** (~90 linii)
- Pracownicy bez projektu ale z zasobami
- Widget z tabelą

### 12. **ArrivalsCard** (~100 linii)
- Przyjazdy w tym tygodniu
- Formularz bulk assignment
- Duża sekcja z własną logiką

## Priorytet refaktoryzacji:

### ⭐ PRIORYTET 1 (największe oszczędności):
1. **VehicleCard** + **AccommodationCard** → ~160 linii
2. **AlertsSection** → ~90 linii
3. **EmployeesTable** → ~130 linii
4. **ArrivalsCard** → ~100 linii

### ⭐ PRIORYTET 2:
5. **RequirementsTable** → ~100 linii
6. **ExpiringVehiclesCard** + **ExpiringAccommodationsCard** → ~160 linii

### ⭐ PRIORYTET 3:
7. Pozostałe małe komponenty → ~150 linii

## Szacowana redukcja:
- **Przed**: 1436 linii
- **Po refaktoryzacji**: ~500-600 linii głównego pliku
- **Oszczędność**: ~800-900 linii (60%)

## Dodatkowe korzyści:
- ✅ Reużywalność komponentów
- ✅ Łatwiejsze testowanie
- ✅ Lepsza czytelność
- ✅ Separacja odpowiedzialności
- ✅ Łatwiejsze utrzymanie

## Kolejność implementacji (rozpocznij od):
1. VehicleCard
2. AccommodationCard  
3. AlertsSection
4. ArrivalsCard
