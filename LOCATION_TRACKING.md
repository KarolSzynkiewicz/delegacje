# 📍 System Śledzenia Lokalizacji Pracowników

## Spis treści
1. [Wprowadzenie](#wprowadzenie)
2. [Architektura systemu](#architektura-systemu)
3. [Wyjazdy i powroty](#wyjazdy-i-powroty)
4. [Flaga `outside_base`](#flaga-outside_base)
5. [Logika śledzenia lokalizacji](#logika-śledzenia-lokalizacji)
6. [Wykorzystanie w systemie](#wykorzystanie-w-systemie)
7. [Dwuetapowy proces tworzenia wyjazdu](#dwuetapowy-proces-tworzenia-wyjazdu)
8. [Przykłady stanów](#przykłady-stanów)

---

## Wprowadzenie

System śledzenia lokalizacji pracowników w aplikacji Delegacje został zaprojektowany z myślą o kompleksowym monitorowaniu, gdzie aktualnie znajdują się pracownicy - czy są w bazie firmy, w podróży, czy poza bazą na projekcie.

### Kluczowe założenia:
- ✅ **Historyczność** - możliwość sprawdzenia statusu pracownika na dowolną datę
- ✅ **Dwie lokalizacje równocześnie** - pracownik może mieć lokalizację domu i projektu
- ✅ **Automatyzacja** - flagi i statusy aktualizują się automatycznie
- ✅ **Spójność danych** - wyjazdy są zawsze powiązane z przypisaniami

---

## Architektura systemu

### 1. Struktura bazy danych

#### Tabela `employees`
```sql
- outside_base (boolean, default: false)
  Flaga określająca, czy pracownik jest poza bazą
  
- last_departure_id (foreign key, nullable)
  ID ostatniego wyjazdu (dla audytu)
```

#### Tabela `logistics_events`
```sql
- type (enum: DEPARTURE, RETURN)
  Typ zdarzenia logistycznego
  
- status (enum: PLANNED, COMPLETED, CANCELLED)
  Status wydarzenia
  
- event_date (datetime)
  Data rozpoczęcia (dla DEPARTURE: data wyjazdu, dla RETURN: data rozpoczęcia powrotu)
  
- end_date (datetime, nullable)
  Data zakończenia (dla DEPARTURE: data przyjazdu, dla RETURN: data zakończenia powrotu)
  
- from_location_id, to_location_id
  Lokalizacje początkowa i docelowa
```

#### Tabela `project_assignments`
```sql
- logistics_event_id (foreign key, nullable)
  Powiązanie z wyjazdem, który utworzył to przypisanie
  
- start_date, end_date
  Okres trwania przypisania
  
- is_cancelled (boolean)
  Czy przypisanie zostało anulowane
```

#### Tabela `accommodation_assignments`
```sql
- logistics_event_id (foreign key, nullable)
  Powiązanie z wyjazdem
  
- start_date, end_date
  Okres zakwaterowania
```

#### Tabela `vehicle_assignments`
```sql
- logistics_event_id (foreign key, nullable)
  Powiązanie z wyjazdem
  
- start_date, end_date
  Okres przypisania pojazdu
  
- position (enum: DRIVER, PASSENGER)
  Pozycja w pojeździe
```

---

## Wyjazdy i powroty

### Wyjazd (DEPARTURE)

**Reprezentuje:** Transport pracowników z bazy do lokalizacji projektu

#### Daty:
- `event_date` - data wyjazdu z bazy
- `end_date` - data przyjazdu na miejsce docelowe

#### Zakres dat:
```
[event_date] -----> [end_date]
   wyjazd          przyjazd
```

#### Co się dzieje podczas wyjazdu:
1. **Tworzenie wydarzenia** `LogisticsEvent` typu `DEPARTURE`
2. **Dodawanie uczestników** do tabeli `logistics_event_participants`
3. **Tworzenie przypisań** (w jednej transakcji):
   - Przypisanie do projektu (`ProjectAssignment`)
   - Przypisanie pojazdu (`VehicleAssignment`)
   - Przypisanie zakwaterowania (`AccommodationAssignment`)
4. **Aktualizacja flagi** `outside_base = true` dla każdego uczestnika

#### Statusy wyjazdu:
- `PLANNED` - zaplanowany (domyślny przy tworzeniu)
- `COMPLETED` - zakończony
- `CANCELLED` - anulowany

#### Anulowanie wyjazdu:
```php
// DepartureController@cancel
1. Usuń wszystkie przypisania (projekt, pojazd, dom)
2. Zmień status wyjazdu na CANCELLED
3. Przelicz flagę outside_base dla każdego uczestnika
4. Zapisz w historii audytu
```

---

### Powrót (RETURN)

**Reprezentuje:** Transport pracowników z projektu do bazy

#### Daty:
- `event_date` - data rozpoczęcia powrotu
- `end_date` - data przyjazdu do bazy

#### Zakres dat:
```
[event_date] -----> [end_date]
   wyjazd          przyjazd do bazy
```

#### Co się dzieje podczas powrotu:
1. **Tworzenie wydarzenia** `LogisticsEvent` typu `RETURN`
2. **Dodawanie uczestników**
3. **System automatycznie**:
   - Usuwa przeterminowane przypisania do projektów (których `end_date` minął)
   - Usuwa przeterminowane przypisania do domów
   - Aktualizuje `outside_base = false` po dacie `end_date`

#### Automatyczne tworzenie powrotów:
System posiada mechanizm (`ReturnTripService`) do automatycznego tworzenia powrotów na podstawie:
- Zakończonych przypisań do projektów
- Wspólnej lokalizacji wyjściowej uczestników

---

## Flaga `outside_base`

### Definicja
Boolean wskazujący, czy pracownik jest **fizycznie poza bazą** firmy.

### Kiedy `outside_base = true`?

#### Priorytet 1: Ostatnie zdarzenie logistyczne
```php
// Pobierz ostatni DEPARTURE lub RETURN (tylko PLANNED/COMPLETED)
$lastEvent = LogisticsEvent::whereIn('type', [DEPARTURE, RETURN])
    ->whereIn('status', [PLANNED, COMPLETED]) // ❗ CANCELLED są IGNOROWANE
    ->where('event_date', '<=', $date)
    ->orderBy('event_date', 'desc')
    ->first();

if ($lastEvent->type === DEPARTURE && $lastEvent->event_date <= $date) {
    outside_base = true; // Wyjechał i nie wrócił
}

if ($lastEvent->type === RETURN && $lastEvent->end_date <= $date) {
    outside_base = false; // Wrócił do bazy
}
```

#### Priorytet 2: Aktywne przypisania
```php
// Jeśli brak wydarzenia lub był return, sprawdź przypisania
$hasActiveProject = ProjectAssignment::where('employee_id', $id)
    ->where('is_cancelled', false)
    ->where('start_date', '<=', $date)
    ->where(function($q) use ($date) {
        $q->whereNull('end_date')
          ->orWhere('end_date', '>=', $date);
    })
    ->exists();

$hasActiveAccommodation = AccommodationAssignment::where('employee_id', $id)
    ->where('start_date', '<=', $date)
    ->where(function($q) use ($date) {
        $q->whereNull('end_date')
          ->orWhere('end_date', '>=', $date);
    })
    ->exists();

if ($hasActiveProject || $hasActiveAccommodation) {
    outside_base = true;
}
```

### Kiedy `outside_base = false`?
- Ostatnim zdarzeniem był `RETURN` z `end_date <= dzisiaj`
- Brak aktywnych przypisań do projektów i domów
- Pracownik nigdy nie wyjeżdżał

### Aktualizacja flagi

#### Lazy Evaluation (jedyny mechanizm):
```php
// LocationTrackingService::syncOutsideBaseFlag()
// Wywoływane za każdym razem przy getLocationStatus()
// Przelicza flagę on-demand na podstawie aktualnego stanu
// Bez cache - zawsze aktualne dane
```

**Dlaczego bez cache?**
- ✅ **Zawsze aktualne dane** - brak ryzyka nieświeżego cache
- ✅ **Prostszy kod** - brak zarządzania invalidacją
- ✅ **Mniej bugów** - eliminacja całej kategorii problemów
- ✅ **Wystarczająca wydajność** - ~3-7ms na zapytanie
- ✅ **KISS principle** - prostsze = lepsze

#### Manualny trigger:
```php
// Np. po anulowaniu wyjazdu (opcjonalnie)
$locationTracker = app(LocationTrackingService::class);
$locationTracker->syncOutsideBaseFlag($employee, now());
```

---

## Logika śledzenia lokalizacji

### Serwis: `LocationTrackingService`

#### Główna metoda: `getLocationStatus()`

```php
public function getLocationStatus(Employee $employee, Carbon $date): array
{
    // 1. Zaktualizuj flagę outside_base
    $this->syncOutsideBaseFlag($employee, $date);
    
    // 2. Sprawdź czy w podróży
    $inTransit = LogisticsEvent::isEmployeeInTransit($employee, $date);
    
    // 3. Pobierz lokalizację zakwaterowania
    $accommodationLocation = $this->getAccommodationLocationOnDate($employee, $date);
    
    // 4. Pobierz lokalizację projektu
    $projectLocation = $this->getProjectLocationOnDate($employee, $date);
    
    return [
        'accommodation_location' => $accommodationLocation, // Location | null
        'project_location' => $projectLocation,             // Location | null
        'in_transit' => $inTransit,                         // bool
        'outside_base' => $employee->outside_base,          // bool
    ];
}
```

### Priorytety statusów

#### 1️⃣ **W PODRÓŻY** (najwyższy priorytet)
```php
LogisticsEvent::where('employee_id', $id)
    ->whereIn('type', [DEPARTURE, RETURN])
    ->where('event_date', '<=', $date)
    ->where('end_date', '>=', $date)
    ->exists();
```
- Pracownik jest między `event_date` a `end_date` jakiegokolwiek wyjazdu/powrotu
- **Wyświetlanie:** 🚗 W podróży

#### 2️⃣ **LOKALIZACJA ZAKWATEROWANIA**
```php
AccommodationAssignment::where('employee_id', $id)
    ->where('start_date', '<=', $date)
    ->where(function($q) use ($date) {
        $q->whereNull('end_date')
          ->orWhere('end_date', '>=', $date);
    })
    ->first()
    ->accommodation
    ->location;
```
- Jeśli ma aktywne zakwaterowanie na dany dzień
- **Wyświetlanie:** 🏡 [Nazwa lokalizacji]

#### 3️⃣ **LOKALIZACJA PROJEKTU**
```php
ProjectAssignment::where('employee_id', $id)
    ->where('is_cancelled', false)
    ->where('start_date', '<=', $date)
    ->where(function($q) use ($date) {
        $q->whereNull('end_date')
          ->orWhere('end_date', '>=', $date);
    })
    ->first()
    ->project
    ->location;
```
- Jeśli ma aktywne przypisanie do projektu na dany dzień
- **Wyświetlanie:** 🏢 [Nazwa lokalizacji]

#### 4️⃣ **W BAZIE** (domyślny stan)
```php
if (!$outside_base) {
    // Pracownik jest w bazie
}
```
- Brak aktywnych wyjazdów i przypisań
- **Wyświetlanie:** 🏠 Baza

---

## Wykorzystanie w systemie

### 1. Widok pracowników (`/employees`)

#### Filtr daty:
```html
<input type="date" wire:model.live="statusDate">
```
- Użytkownik może sprawdzić status pracowników na dowolną datę
- Domyślnie: dzisiaj

#### Tabela z 4 kolumnami statusu:

| Kolumna | Warunek | Wyświetlanie |
|---------|---------|--------------|
| **Status** | `in_transit = true` | 🚗 W podróży |
|  | `outside_base = false` | 🏠 Baza |
|  | `outside_base = true` | 📍 Poza bazą |
| **Dom** | `outside_base = false` | `─` (dash) |
|  | `in_transit = true` | `─` (dash) |
|  | Ma `accommodation_location` | 🏡 [Lokalizacja] |
|  | Brak | ❌ Brak (czerwony) |
| **Projekt** | `outside_base = false` | `─` (dash) |
|  | `in_transit = true` | `─` (dash) |
|  | Ma `project_location` | 🏢 [Lokalizacja] |
|  | Brak | ❌ Brak (czerwony) |
| **Rotacja** | Ma aktywną rotację | ✅ [Nazwa] |
|  | Brak rotacji | - |

#### Tooltips (po najechaniu na badge statusu):
- Szczegółowe wyjaśnienie logiki obliczania statusu
- Informacja o priorytetach
- Uwagi o ignorowaniu anulowanych wyjazdów

---

### 2. Planer tygodniowy (`/weekly-overview`)

#### Sekcja "Przyjazdy w tym tygodniu" (na górze strony)
```php
// Pobierz wyjazdy z datą przyjazdu w tym tygodniu
$allDepartures = LogisticsEvent::where('type', DEPARTURE)
    ->whereBetween('end_date', [$weekStart, $weekEnd])
    ->whereNotNull('end_date')
    ->orderBy('end_date')
    ->get();

// Dla każdego uczestnika sprawdź lokalizację projektu
foreach ($departure->participants as $participant) {
    $locationStatus = $locationTracker->getLocationStatus(
        $participant->employee, 
        $departure->end_date
    );
    
    // Jeśli brak lokalizacji projektu → pokaż formularz przypisania
    if (!$locationStatus['project_location']) {
        // [REMOVED] - formularz bulk assignment został usunięty
        // Teraz wszystkie przypisania tworzone są razem z wyjazdem
    }
}
```

**Uwaga:** Ta sekcja jest głównie informacyjna. Nowe wyjazdy tworzone przez dwuetapowy proces automatycznie mają wszystkie przypisania.

#### Wyświetlanie dat powrotów
```php
// RETURN trips wyświetlane są po end_date (data przyjazdu do bazy)
// NIE po event_date (data rozpoczęcia powrotu)

$returnTrips = LogisticsEvent::where('type', RETURN)
    ->whereBetween('end_date', [$weekStart, $weekEnd])
    ->whereNotNull('end_date')
    ->get();

// W widoku blade:
{{ $returnTrip->end_date->format('d.m.Y') }}
```

---

### 3. Karta pracownika (`/employees/{id}`)

#### Sekcja "Aktualna lokalizacja":
```php
$locationStatus = $locationTracker->getLocationStatus($employee, now());

if ($locationStatus['in_transit']) {
    echo '✈️ W podróży';
}
elseif (!$locationStatus['outside_base']) {
    echo '🏠 Baza';
}
else {
    echo '📍 Poza bazą';
    
    if ($locationStatus['accommodation_location']) {
        echo '🏡 Dom: ' . $locationStatus['accommodation_location']->name;
    }
    
    if ($locationStatus['project_location']) {
        echo '🏢 Projekt: ' . $locationStatus['project_location']->name;
    }
    
    if (!$locationStatus['accommodation_location'] && 
        !$locationStatus['project_location']) {
        echo 'Czeka na przypisania';
    }
}
```

---

### 4. Walidacja przypisań

#### `EmployeeLocationValidator`
```php
public function validateForAssignment(
    Employee $employee,
    Project $project,
    Carbon $startDate
): void {
    $employeeLocation = $this->locationTracker->forEmployeeOnDate(
        $employee, 
        $startDate
    );
    
    $projectLocation = $project->location;
    
    // Sprawdź czy pracownik jest w odpowiedniej lokalizacji
    if ($employeeLocation && 
        $employeeLocation->id !== $projectLocation->id) {
        throw ValidationException::withMessages([
            'employee_id' => "Pracownik {$employee->full_name} jest w {$employeeLocation->name}, a projekt w {$projectLocation->name}"
        ]);
    }
}
```

Używane w:
- Tworzeniu przypisań do projektów
- Walidacji w formularzu bulk assignment
- Automatycznym planowaniu rotacji

---

### 5. Automatyczne tworzenie powrotów

#### `ReturnTripService`
```php
protected function getCurrentLocationForEmployees(array $employeeIds): ?Location
{
    $firstEmployee = Employee::find($employeeIds[0]);
    
    return app(LocationTrackingService::class)
        ->forEmployee($firstEmployee);
}

// Jeśli wszyscy pracownicy są w tej samej lokalizacji
// → utwórz powrót z tej lokalizacji do bazy
```

---

## Dwuetapowy proces tworzenia wyjazdu

### Problem który rozwiązuje:
- ❌ Poprzednio: Ręczne przypisania nie były powiązane z wyjazdami
- ❌ "Unlinked departures" - wyjazdy bez przypisań
- ❌ Niespójność danych między wyjazdami a przypisaniami

### Rozwiązanie: Two-step form

#### **KROK 1:** Wybór uczestników i szczegółów wyjazdu
```
Route: /departures/create
Form action: POST /departures/prepare-bulk-assignment

Pola:
- departure_date (data wyjazdu)
- end_date (data przyjazdu)
- to_location_id (lokalizacja docelowa)
- vehicle_id (pojazd - opcjonalny)
- employee_ids[] (wybrani uczestnicy)
- notes (notatki)
```

**Walidacja:**
```php
$validated = $request->validate([
    'departure_date' => 'required|date',
    'end_date' => 'required|date|after_or_equal:departure_date',
    'to_location_id' => 'required|exists:locations,id',
    'employee_ids' => 'required|array|min:1',
    'employee_ids.*' => 'exists:employees,id',
]);
```

**Co się dzieje:**
```php
// 1. Zapisz dane w sesji
session(['pending_departure' => $validated]);

// 2. Przekieruj do kroku 2
return view('departures.bulk-assignment', [
    'employees' => $employees,
    'departureData' => $validated,
    // ... inne dane pomocnicze
]);
```

---

#### **KROK 2:** Przypisanie projektów, pojazdów i zakwaterowania

```
Route: /departures/bulk-assignment (widok)
Component: BulkDepartureAssignments (Livewire)
```

**Formularz (transpozycja):**
```
┌─────────────┬──────────────┬──────────────┬──────────────┐
│             │ Jan Kowalski │ Anna Nowak   │ Piotr Lis    │
├─────────────┼──────────────┼──────────────┼──────────────┤
│ 🏢 PROJEKT  │              │              │              │
├─────────────┼──────────────┼──────────────┼──────────────┤
│ Projekt     │ [select]     │ [select]     │ [select]     │
│ Rola        │ [select]     │ [select]     │ [select]     │
│ Od          │ [date]       │ [date]       │ [date]       │
│ Do          │ [date]       │ [date]       │ [date]       │
├─────────────┼──────────────┼──────────────┼──────────────┤
│ 🚗 AUTO     │              │              │              │
├─────────────┼──────────────┼──────────────┼──────────────┤
│ Pojazd      │ [select]     │ [select]     │ [select]     │
│ Pozycja     │ [select]     │ [select]     │ [select]     │
│ Od          │ [date]       │ [date]       │ [date]       │
│ Do          │ [date]       │ [date]       │ [date]       │
├─────────────┼──────────────┼──────────────┼──────────────┤
│ 🏡 DOM      │              │              │              │
├─────────────┼──────────────┼──────────────┼──────────────┤
│ Dom         │ [select]     │ [select]     │ [select]     │
│ Od          │ [date]       │ [date]       │ [date]       │
│ Do          │ [date]       │ [date]       │ [date]       │
└─────────────┴──────────────┴──────────────┴──────────────┘
```

**Funkcje pomocnicze:**
- ➡️ **"Kopiuj z pierwszego"** - dla każdej sekcji (Projekt, Auto, Dom)
- ⚠️ **Walidacja inline** - błędy wyświetlane w czasie rzeczywistym
- 💾 **Zapis atomiczny** - wszystko w jednej transakcji

---

#### **ZAPIS:** Atomic transaction

```php
// BulkDepartureAssignments::submitAssignments()

DB::beginTransaction();
try {
    // 1. Utwórz wyjazd
    $departure = LogisticsEvent::create([
        'type' => DEPARTURE,
        'event_date' => $departureData['event_date'],
        'end_date' => $departureData['end_date'],
        'to_location_id' => $departureData['to_location_id'],
        'status' => PLANNED,
        'created_by' => auth()->id(),
    ]);
    
    // 2. Dodaj uczestników
    foreach ($departureData['participants'] as $employeeId) {
        $departure->participants()->create([
            'employee_id' => $employeeId,
        ]);
    }
    
    // 3. Utwórz wszystkie przypisania
    foreach ($this->assignments as $employeeId => $data) {
        // Projekt
        ProjectAssignment::create([
            'project_id' => $data['project_id'],
            'employee_id' => $employeeId,
            'role_id' => $data['role_id'],
            'start_date' => $data['project_start_date'],
            'end_date' => $data['project_end_date'],
            'logistics_event_id' => $departure->id, // ✅ POWIĄZANIE
        ]);
        
        // Pojazd
        VehicleAssignment::create([
            'vehicle_id' => $data['vehicle_id'],
            'employee_id' => $employeeId,
            'position' => $data['position'],
            'start_date' => $data['vehicle_start_date'],
            'end_date' => $data['vehicle_end_date'],
            'logistics_event_id' => $departure->id, // ✅ POWIĄZANIE
        ]);
        
        // Dom
        AccommodationAssignment::create([
            'accommodation_id' => $data['accommodation_id'],
            'employee_id' => $employeeId,
            'start_date' => $data['accommodation_start_date'],
            'end_date' => $data['accommodation_end_date'],
            'logistics_event_id' => $departure->id, // ✅ POWIĄZANIE
        ]);
    }
    
    DB::commit();
    
    return redirect()
        ->route('weekly-overview.index')
        ->with('success', 'Wyjazd oraz wszystkie przypisania zostały utworzone!');
        
} catch (Exception $e) {
    DB::rollback();
    throw $e;
}
```

**Kluczowe:** Wszystko albo się powiedzie, albo nic nie zostanie zapisane!

---

#### **WALIDACJA w kroku 2:**

System używa istniejących serwisów walidacji:

1️⃣ **Pokrycie rotacją na cały okres**
```php
$rotationService->validateRotationCoverage(
    $employee,
    $startDate,
    $endDate
);
```

2️⃣ **Ważność dokumentów wymaganych**
```php
$rotationService->validateRequiredDocuments(
    $employee,
    $startDate,
    $endDate
);
```

3️⃣ **Daty projektu**
```php
$projectAssignmentService->validateAssignment(
    $project,
    $employee,
    $role,
    $startDate,
    $endDate
);
```

4️⃣ **Koniec najmu zakwaterowania**
```php
$accommodationAssignmentService->validateAssignment(
    $employee,
    $accommodation,
    $startDate,
    $endDate
);
```

5️⃣ **Pojemność pojazdu i konflikty kierowców**
```php
$vehicleAssignmentService->validateAssignment(
    $employee,
    $vehicle,
    $position,
    $startDate,
    $endDate
);
```

6️⃣ **Data rozpoczęcia nie wcześniejsza niż data przyjazdu**
```php
if (Carbon::parse($assignmentData['project_start_date'])
    ->lt(Carbon::parse($departureData['end_date']))) {
    throw ValidationException::withMessages([
        "assignments.{$employeeId}.project_start_date" => 
            'Data rozpoczęcia nie może być wcześniejsza niż data przyjazdu'
    ]);
}
```

**Wyświetlanie błędów:**
```blade
@if(isset($validationErrors[$employee->id]))
    <div class="alert alert-danger">
        <strong>{{ $validationErrors[$employee->id]['name'] }}</strong>
        <ul>
            @foreach($validationErrors[$employee->id]['errors'] as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
```

---

## Przykłady stanów

### 📊 Stan 1: Pracownik w bazie
```
outside_base: false
in_transit: false
accommodation_location: null
project_location: null

Znaczenie: Pracownik jest w siedzibie firmy
```

**Wyświetlanie:**
- Status: 🏠 Baza
- Dom: `─`
- Projekt: `─`

---

### 📊 Stan 2: Pracownik w podróży (wyjazd)
```
outside_base: true
in_transit: true
accommodation_location: null
project_location: null

Znaczenie: Pracownik jedzie na projekt
Okres: departure.event_date → departure.end_date
```

**Wyświetlanie:**
- Status: 🚗 W podróży
- Dom: `─`
- Projekt: `─`

---

### 📊 Stan 3: Przyjechał, czeka na przypisania
```
outside_base: true
in_transit: false
accommodation_location: null
project_location: null

Znaczenie: Wyjazd zakończony (end_date minął), 
          ale brak aktywnych przypisań
```

**Wyświetlanie:**
- Status: 📍 Poza bazą
- Dom: ❌ Brak (czerwony)
- Projekt: ❌ Brak (czerwony)

**Uwaga:** Ten stan **nie powinien występować** w nowym systemie, ponieważ przypisania są tworzone razem z wyjazdem!

---

### 📊 Stan 4: Pracownik na projekcie
```
outside_base: true
in_transit: false
accommodation_location: Location {id: 3, name: "Berlin - Apartament A"}
project_location: Location {id: 2, name: "Berlin"}

Znaczenie: Pracownik ma aktywne przypisanie do projektu i domu
```

**Wyświetlanie:**
- Status: 📍 Poza bazą
- Dom: 🏡 Berlin - Apartament A
- Projekt: 🏢 Berlin

---

### 📊 Stan 5: Pracownik w podróży (powrót)
```
outside_base: true
in_transit: true
accommodation_location: null (przypisania zakończone)
project_location: null

Znaczenie: Pracownik wraca do bazy
Okres: return.event_date → return.end_date
```

**Wyświetlanie:**
- Status: 🚗 W podróży
- Dom: `─`
- Projekt: `─`

---

### 📊 Stan 6: Wyjazd anulowany
```
Wyjazd: status = CANCELLED
Przypisania: usunięte
outside_base: false (przeliczone automatycznie)
in_transit: false

Znaczenie: Wyjazd został anulowany, pracownik zostaje w bazie
```

**Wyświetlanie:**
- Status: 🏠 Baza
- Dom: `─`
- Projekt: `─`

---

## Podsumowanie

### ✅ Kluczowe zasady:

1. **Wyjazdy zawsze z przypisaniami** - dwuetapowy proces wymusza spójność
2. **Anulowane wyjazdy są ignorowane** - tylko `PLANNED` i `COMPLETED` liczą się w logice
3. **Flaga `outside_base` aktualizowana automatycznie** - lazy evaluation bez cache
4. **Historyczność** - można sprawdzić stan na dowolną datę
5. **Powroty wyświetlane po dacie przyjazdu** - `end_date`, nie `event_date`
6. **Brak cache = zawsze aktualne dane** - performance ~3-7ms (wystarczające)

### 🔧 Główne komponenty:

- **`LocationTrackingService`** - serwis centralny do obliczania lokalizacji
- **`LogisticsEvent`** - model reprezentujący wyjazdy i powroty
- **`Employee.outside_base`** - flaga stanu fizycznego
- **`BulkDepartureAssignments`** - Livewire component do tworzenia wyjazdów

### 📚 Dalsze rozszerzenia:

- [ ] Dashboard z mapą pokazującą lokalizacje pracowników
- [ ] Powiadomienia o nadchodzących wyjazdach/powrotach
- [ ] Raportowanie czasu spędzonego poza bazą
- [ ] Integracja z systemem GPS (opcjonalnie)

---

**Autor:** System Delegacje  
**Data:** 2026-02-15  
**Wersja:** 1.0
