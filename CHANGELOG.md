# Dziennik zmian

Format oparty o [Keep a Changelog](https://keepachangelog.com/pl/1.0.0/).

## [2026-04-16]

### Planer wyjazdu — krok 2 (zakwaterowanie)

- Kalendarz w modalu wyboru dat: dostępność dni jest liczona tak jak przy zapisie (`getAvailableCapacity` z bazy minus przypisania pozostałych pracowników w formularzu), z pominięciem bieżącego pracownika i z wykluczeniem jego istniejącego rekordu w bazie przy edycji — dni bez miejsc są poprawnie blokowane zamiast komunikatu dopiero przy zapisie.
- Podsumowanie na karcie mieszkania (zajęte / pojemność w dniu przyjazdu): liczba unikalnych pracowników z formularza i z bazy, bez podwójnego liczenia tej samej osoby.

### Planer zwrotu

- Rozbudowa planera zwrotu: komponent Livewire, widok Blade, logika w `ReturnTripService` oraz powiązane trasy (m.in. zapis V2).
