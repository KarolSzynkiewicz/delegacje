# Refaktoryzacja systemu śledzenia lokalizacji pracowników

**Data:** 2026-02-15  
**Status:** ✅ Zaimplementowane (wymagane uruchomienie migracji)

## 🎯 Problem

Obecny system nie rozróżniał stanów:
- Pracownik **nigdy nie wyjechał** (faktycznie w bazie)
- Pracownik **przyjechał na delegację i czeka** (poza bazą, bez przypisań)
- Pracownik **ma dom i projekt** (dwie lokalizacje równocześnie)

`forEmployeeOnDate()` pomijał AccommodationAssignment, co powodowało błędne wyniki.

## ✨ Nowy model

### Flaga `outside_base` w tabeli `employees`

```sql
outside_base BOOLEAN DEFAULT 0
last_departure_id INT NULLABLE  -- referencja do ostatniego wyjazdu
```

### Funkcja `getLocationStatus(employee, date)` zwraca:

```php
[
    'accommodation_location' => Location|null,  // gdzie mieszka (dom podczas delegacji)
    'project_location' => Location|null,        // gdzie pracuje
    'in_transit' => bool,                       // czy w podróży
    'outside_base' => bool                      // czy poza bazą
]
```

## 📊 Wszystkie możliwe stany

| accommodation | project | in_transit | outside_base | Znaczenie | UI |
|--------------|---------|------------|--------------|-----------|-----|
| ❌ | ❌ | ✅ | ✅ | W podróży | 🚗 W podróży |
| ❌ | ❌ | ❌ | ✅ | Przyjechał, czeka | ⏳ Poza bazą |
| ❌ | ❌ | ❌ | ❌ | W bazie | 🏠 Baza |
| Berlin | ❌ | ❌ | ✅ | Ma dom, nie pracuje | 🏡 Berlin |
| ❌ | Warszawa | ❌ | ✅ | Pracuje bez domu | 🏢 Warszawa |
| Berlin | Warszawa | ❌ | ✅ | Normalny stan | 🏡 Berlin / 🏢 Warszawa |

## 🔄 Kiedy aktualizuje się `outside_base`?

### Automatycznie:

1. **DEPARTURE rozpoczyna się** (`event_date`) → `outside_base = 1`
2. **RETURN kończy się** (`end_date`) → `outside_base = 0`
3. **ProjectAssignment utworzony** → `outside_base = 1`
4. **AccommodationAssignment utworzony** → `outside_base = 1`
5. **Wszystkie przypisania zakończone** → `outside_base = 0`

### Lazy Evaluation:

`syncOutsideBaseFlag()` w `LocationTrackingService` zawsze sprawdza przy odczycie czy flaga jest aktualna na podstawie:
- Ostatniego LogisticsEvent (DEPARTURE/RETURN)
- Aktywnych ProjectAssignment / AccommodationAssignment

## 📁 Zmodyfikowane pliki

### 1. Migracje

#### `/database/migrations/2026_02_15_130928_add_outside_base_to_employees_table.php` ✅
- Dodaje `outside_base` (boolean, default false)
- Dodaje `last_departure_id` (foreign key do logistics_events)
- Dodaje index na `outside_base`

#### `/database/migrations/2026_02_15_131058_seed_initial_outside_base_state.php` ✅
- Ustawia początkowy stan `outside_base` dla istniejących pracowników
- Bazuje na ostatnich LogisticsEvent i aktywnych przypisaniach

### 2. Model Employee ✅

**Plik:** `/app/Models/Employee.php`

Dodano:
```php
protected $fillable = [
    // ...
    'outside_base',
    'last_departure_id',
];

protected $casts = [
    'outside_base' => 'boolean',
];

public function lastDeparture(): BelongsTo
{
    return $this->belongsTo(LogisticsEvent::class, 'last_departure_id');
}
```

### 3. LocationTrackingService ✅

**Plik:** `/app/Services/LocationTrackingService.php`

#### Nowe metody:

```php
// Główna metoda - zwraca pełny status
public function getLocationStatus(Employee $employee, Carbon $date): array

// Synchronizuje flagę outside_base
protected function syncOutsideBaseFlag(Employee $employee, Carbon $date): void

// Pobiera lokalizację accommodation na datę
protected function getAccommodationLocationOnDate(Employee $employee, Carbon $date): ?Location

// Pobiera lokalizację projektu na datę
protected function getProjectLocationOnDate(Employee $employee, Carbon $date): ?Location
```

#### Zaktualizowane metody:

```php
// Teraz używają getLocationStatus() jako fasada dla backwards compatibility
public function forEmployee(Employee $employee): ?Location
public function forEmployeeOnDate(Employee $employee, Carbon $date): Location|string|null
```

### 4. UI - Lista pracowników ✅

**Plik:** `/resources/views/livewire/employees-table.blade.php`

Nowa logika wyświetlania statusu:
- 🚗 W podróży (in_transit)
- 🏠 Baza (outside_base = false)
- 🏡🏢 Dom + Praca (beide lokalizacje)
- 🏡 Dom (tylko accommodation)
- 🏢 Praca (tylko project)
- ⏳ Poza bazą (outside_base = true, brak przypisań)

### 5. UI - Widok pracownika (tabs) ✅

**Plik:** `/resources/views/livewire/employee-tabs.blade.php`

Zaktualizowano sekcję "Aktualna lokalizacja" używając `getLocationStatus()`:
- Pokazuje dwie lokalizacje jeśli pracownik ma dom i pracę
- Rozróżnia wszystkie stany (w podróży, poza bazą, w bazie, etc.)

### 6. Filtrowanie - Livewire Component ✅

**Plik:** `/app/Livewire/EmployeesTable.php`

Zaktualizowane filtrowanie lokalizacji:
- `base` → `!outside_base`
- `transit` → `in_transit`
- `field` → `outside_base && !in_transit`

### 7. Cache invalidation ✅

**Plik:** `/app/Providers/AppServiceProvider.php`

Dodano event listeners:
- `ProjectAssignment::saved` → czyści cache
- `AccommodationAssignment::saved` → czyści cache
- `LogisticsEvent::saved` → czyści cache dla wszystkich uczestników

## 🚀 Deployment

### Kroki do uruchomienia:

```bash
# 1. Uruchom migracje
php artisan migrate

# 2. Migracja danych nastąpi automatycznie
# (2026_02_15_131058_seed_initial_outside_base_state.php)

# 3. Wyczyść cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### Weryfikacja:

```php
// W tinker lub kontrolerze:
$employee = Employee::find(1);
$status = app(\App\Services\LocationTrackingService::class)
    ->getLocationStatus($employee, now());

dd($status);
// Oczekiwany output:
// [
//     'accommodation_location' => Location {...},
//     'project_location' => Location {...},
//     'in_transit' => false,
//     'outside_base' => true,
// ]
```

## 💡 Kluczowe decyzje

### 1. Lazy Evaluation > Eager Update

`syncOutsideBaseFlag()` zawsze sprawdza przy odczycie. Zalety:
- Zawsze poprawny stan
- Brak race conditions
- Historyczność działa automatycznie

### 2. Cache z invalidacją

Cache na 5 minut + invalidacja przy zmianach = optymalna wydajność

### 3. Backwards Compatibility

Stare metody `forEmployee()` i `forEmployeeOnDate()` działają dalej jako fasada

### 4. Separacja stanów

- `outside_base` = status fizyczny
- `accommodation_location` = gdzie mieszka
- `project_location` = gdzie pracuje
- `in_transit` = czy się przemieszcza

## 🔍 Scenariusze testowe

### 1. Pracownik w bazie
```
outside_base: false
accommodation: null
project: null
in_transit: false
→ UI: 🏠 Baza
```

### 2. Pracownik w podróży (wyjazd)
```
outside_base: true
accommodation: null
project: null
in_transit: true
→ UI: 🚗 W podróży
```

### 3. Pracownik przyjechał, czeka
```
outside_base: true (bo departure był)
accommodation: null (jeszcze nie przypisany)
project: null (jeszcze nie przypisany)
in_transit: false (już dojechał)
→ UI: ⏳ Poza bazą
```

### 4. Pracownik ma dom i projekt
```
outside_base: true
accommodation: Berlin
project: Berlin
in_transit: false
→ UI: 🏡🏢 Berlin
```

### 5. Pracownik wrócił do bazy
```
outside_base: false (return zakończony)
accommodation: null
project: null
in_transit: false
→ UI: 🏠 Baza
```

## 🎉 Rezultat

System teraz:
- ✅ Rozróżnia wszystkie stany pracownika
- ✅ Uwzględnia accommodation (dom podczas delegacji)
- ✅ Pokazuje dwie lokalizacje równocześnie
- ✅ Obsługuje stan "przyjechał, czeka"
- ✅ Jest deterministyczny i idempotentny
- ✅ Ma historyczność (można sprawdzić stan na dowolną datę)
- ✅ Jest wydajny (cache + lazy evaluation)
