# Nowa tabela pracowników z filtrem daty i 4 badge'ami

## ✅ Zaimplementowane (2026-02-15)

### 🎯 Cel
Dodano możliwość sprawdzenia stanu pracowników na konkretną datę oraz zmieniono tabelę na 4 badge'y pokazujące pełen status.

### 📋 Nowa struktura tabeli

| Kolumna | Opis | Wyświetlanie |
|---------|------|--------------|
| **Pracownik** | Imię + nazwisko + email | Zawsze |
| **Status** | Baza / W podróży / Poza bazą | Badge: success/warning/info |
| **Dom** | Lokalizacja accommodation | ─ (w bazie) / 🏡 Nazwa / ❌ Brak |
| **Projekt** | Lokalizacja projektu | ─ (w bazie) / 🏢 Nazwa / ❌ Brak |
| **Rotacja** | Czy ma aktywną rotację | ✓ Tak (success) / ✗ Nie (danger) |
| **Akcje** | Podgląd / Edycja | Ikony |

### 🎨 Logika kolorów

#### Status (kolumna 1):
- 🏠 **Baza** (success/zielony) - `outside_base = false`
- 🚗 **W podróży** (warning/żółty) - `in_transit = true`
- 📍 **Poza bazą** (info/niebieski) - `outside_base = true && !in_transit`

#### Dom i Projekt (kolumny 2-3):
- **─** (szary) - pracownik w bazie, nie dotyczy
- **🏡/🏢 Nazwa** (info/niebieski) - ma przypisanie
- **❌ Brak** (danger/czerwony) - poza bazą ale bez przypisania

#### Rotacja (kolumna 4):
- **✓ Tak** (success/zielony) - ma aktywną rotację na wybraną datę
- **✗ Nie** (danger/czerwony) - brak rotacji

### 📅 Nowy filtr daty

**Pole:** "Stan na dzień"
- Typ: `input type="date"`
- Domyślnie: dzisiaj (now())
- Efekt: Przelicza statusy wszystkich pracowników na wybraną datę
- Wykorzystuje: `getLocationStatus($employee, $checkDate)`

### 🔧 Zmodyfikowane pliki

#### 1. `app/Livewire/EmployeesTable.php`
**Dodano:**
```php
public $statusDate = ''; // Nowy filtr daty

protected $queryString = [
    // ...
    'statusDate' => ['except' => ''],
];

public function updatingStatusDate() {
    $this->resetPage();
}

// W render():
$checkDate = $this->statusDate ? \Carbon\Carbon::parse($this->statusDate) : now();
```

**Zmieniono:**
- Filtrowanie używa `$checkDate` zamiast `now()`
- Rotacje sprawdzane na `$checkDate`
- Przekazywanie `$checkDate` do widoku

#### 2. `resources/views/livewire/employees-table.blade.php`

**Dodano filtr daty:**
```blade
<div class="col-md-2">
    <label class="form-label small">
        <i class="bi bi-calendar me-1"></i> Stan na dzień
    </label>
    <input type="date" wire:model.live="statusDate" class="form-control">
</div>
```

**Zmieniono tabelę:**
- Usunięto kolumnę "Rola"
- Zmieniono "Lokalizacja" na 3 kolumny: Status, Dom, Projekt
- Dodano kolumnę "Rotacja"

**Logika wyświetlania:**
```php
$locationStatus = $locationTracker->getLocationStatus($employee, $checkDate);

// Status
if ($locationStatus['in_transit']) → 🚗 W podróży
elseif (!$locationStatus['outside_base']) → 🏠 Baza
else → 📍 Poza bazą

// Dom / Projekt
if (!$locationStatus['outside_base']) → ─
elseif ($locationStatus['accommodation_location']) → 🏡 Nazwa
else → ❌ Brak
```

### 📊 Scenariusze

#### 1. Pracownik w bazie
```
Status: 🏠 Baza (zielony)
Dom: ─ (szary)
Projekt: ─ (szary)
Rotacja: ✗ Nie (czerwony - brak rotacji)
```

#### 2. Pracownik w podróży
```
Status: 🚗 W podróży (żółty)
Dom: ❌ Brak (czerwony - nie ma jeszcze przypisania)
Projekt: ❌ Brak (czerwony)
Rotacja: ✓ Tak (zielony)
```

#### 3. Pracownik poza bazą z pełnym setupem
```
Status: 📍 Poza bazą (niebieski)
Dom: 🏡 Berlin (niebieski)
Projekt: 🏢 Berlin (niebieski)
Rotacja: ✓ Tak (zielony)
```

#### 4. Pracownik poza bazą bez przypisań (przyjechał, czeka)
```
Status: 📍 Poza bazą (niebieski)
Dom: ❌ Brak (czerwony - PROBLEM!)
Projekt: ❌ Brak (czerwony - PROBLEM!)
Rotacja: ✓ Tak (zielony)
```

### 🎯 Korzyści

1. **Lepszy przegląd** - 4 badge'y pokazują kompletny status na pierwszy rzut oka
2. **Identyfikacja problemów** - czerwone ❌ od razu pokazują braki
3. **Historyczność** - można sprawdzić stan na dowolną datę w przeszłości lub przyszłości
4. **Filtry działają** - można filtrować po lokalizacji na wybraną datę
5. **Czytelność** - ─ w bazie, ❌ gdy brakuje (czerwony), ✓ gdy OK (zielony)

### 🚀 Użycie

1. Wejdź na `http://localhost/employees`
2. Wybierz datę w filtrze "Stan na dzień"
3. Tabela automatycznie przeliczy statusy na wybraną datę
4. Czerwone ❌ wskazują braki w przypisaniach (dom/projekt)
5. Użyj filtrów lokalizacji/rotacji aby zawęzić wyniki

## 🎉 Gotowe!

Tabela pracowników teraz pokazuje pełen status z możliwością sprawdzenia na dowolną datę.
