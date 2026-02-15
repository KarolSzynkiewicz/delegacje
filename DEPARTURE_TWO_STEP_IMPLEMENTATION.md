# Implementacja dwuetapowego formularza wyjazdu

## 🎯 Cel

Zapobieganie sytuacji "niepowiązanych wyjazdów" poprzez wymuszenie przypisań (projekt + auto + dom) w momencie tworzenia wyjazdu.

## ✅ Co zostało zrobione

### 1. **Nowy workflow tworzenia wyjazdu**

#### Krok 1: Podstawowe informacje (`/departures/create`)
- Data wyjazdu i przybycia
- Lokalizacja docelowa
- Pojazd (opcjonalnie)
- **Wybór uczestników** (multi-select)

#### Krok 2: Przypisania (`/departures/bulk-assignment`)
- Dla każdego uczestnika:
  - **Projekt** + rola + daty
  - **Pojazd** + pozycja + daty
  - **Zakwaterowanie** + daty
- Wszystko zapisywane w **jednej transakcji atomowej**

### 2. **Zmiany w kodzie**

#### Routes (`routes/web.php`)
```php
// Nowe route'y
Route::post('departures/prepare-bulk-assignment', [...])
    ->name('departures.prepare-bulk-assignment');
Route::post('departures/store-with-assignments', [...])
    ->name('departures.store-with-assignments');
```

#### Controller (`DepartureController.php`)
- `prepareBulkAssignment()` - przygotowuje krok 2, zapisuje dane w sesji
- `storeWithAssignments()` - zapisuje wszystko w transakcji DB

#### Widoki
- `departures/create.blade.php` - zaktualizowany do kroku 1/2
- `departures/bulk-assignment.blade.php` - **NOWY** widok kroku 2

### 3. **Uproszczenie planera tygodniowego**

#### Usunięto:
- ❌ Duży formularz bulk assignment z `weekly-overview/index.blade.php`
- ❌ Zmienne `$roles`, `$vehicles`, `$accommodations` z `WeeklyOverviewController`

#### Zastąpiono:
- ✅ Prostą tabelą "Wyjazdy bez przypisań" (legacy)
- ✅ Link do ręcznego przypisania dla starych wyjazdów

## 📊 Logika biznesowa

### Nowe wyjazdy (utworzone po tej zmianie)
```
1. User wypełnia formularz wyjazdu (krok 1)
2. Dane zapisywane w sesji (NIE w bazie!)
3. User wypełnia przypisania (krok 2)
4. Transakcja DB:
   - Tworzy LogisticsEvent
   - Dodaje uczestników
   - Tworzy ProjectAssignment (z logistics_event_id!)
   - Tworzy VehicleAssignment
   - Tworzy AccommodationAssignment
5. Commit lub Rollback (atomowość)
```

### Stare wyjazdy (legacy)
- Wyświetlane w sekcji "Wyjazdy bez przypisań" (czerwony alert)
- Link "Przypisz ręcznie" → standardowy formularz przypisania
- Nadal obsługiwane dla kompatybilności wstecznej

## 🚀 Korzyści

1. **Zero "niepowiązanych" wyjazdów** od teraz
2. **Atomowość** - albo wszystko się zapisuje, albo nic
3. **Wymusza kompletność** - nie można zapomnieć o przypisaniach
4. **Prostszy kod** - mniej warunkowej logiki
5. **Lepsze UX** - jasny, dwuetapowy proces

## 🔄 Migracja starych danych

Stare wyjazdy (bez przypisań):
- Nadal działają
- Wyświetlają się w alertach
- Można je obsłużyć ręcznie przez formularz przypisania

## 🧪 Testowanie

1. **Nowy wyjazd:**
   ```
   http://localhost/departures/create
   → wybierz uczestników i lokalizację
   → krok 2: przypisz zasoby
   → sprawdź czy wszystko zapisało się w jednej transakcji
   ```

2. **Stary wyjazd bez przypisań:**
   ```
   http://localhost/weekly-overview?start_date=2026-02-16
   → sprawdź czy wyświetla się alert "Wyjazdy bez przypisań"
   → użyj "Przypisz ręcznie"
   ```

3. **Rollback przy błędzie:**
   ```
   → spróbuj przypisać do nieistniejącego pojazdu
   → sprawdź czy NICZEGO nie zapisało (ani wyjazdu, ani przypisań)
   ```

## 📝 Przyszłe usprawnienia

- [ ] Walidacja dostępności pojazdów/domów w kroku 2
- [ ] Podpowiadanie domyślnych wartości (ostatnio użyte auto/dom)
- [ ] Opcja "kopiuj dane z pierwszego uczestnika" dla pozostałych
- [ ] Migrator dla starych wyjazdów (opcjonalnie)

## ⚠️ Breaking Changes

**BRAK** - stary kod nadal działa, nowy workflow jest dodatkiem.

Stare route'y i kontrolery nie zostały zmienione, więc istniejące wyjazdy działają bez zmian.
