# System Autoryzacji - Dokumentacja Techniczna

## Przegląd

System autoryzacji w aplikacji wykorzystuje **Spatie Laravel Permission** do zarządzania uprawnieniami opartymi na rolach (RBAC). Zamiast tradycyjnych Policy, system używa **middleware** do dynamicznego sprawdzania uprawnień na podstawie nazwy route i metody HTTP.

## Architektura

### Komponenty Systemu

1. **Middleware `CheckResourcePermission`** - Główny mechanizm sprawdzania uprawnień
2. **Spatie Laravel Permission** - Pakiet do zarządzania rolami i uprawnieniami
3. **Model User** - Z metodą `hasPermission()` do sprawdzania uprawnień
4. **Tabela uprawnień w UI** - Jedno źródło prawdy dla zarządzania uprawnieniami

## Jak Działa Autoryzacja

### 1. Flow Requestu

```
HTTP Request → Middleware CheckResourcePermission
    ↓
Sprawdź czy użytkownik jest zalogowany
    ↓
Sprawdź czy użytkownik jest administratorem (bypass)
    ↓
Pobierz permission_type z route defaults
    ↓
Ekstraktuj resource z nazwy route (na podstawie permission_type)
    ↓
Mapuj HTTP method + route action → permission action (na podstawie permission_type)
    ↓
Zbuduj nazwę uprawnienia: {resource}.{action}
    ↓
Sprawdź czy użytkownik ma uprawnienie
    ↓
Jeśli TAK → pozwól na dostęp
Jeśli NIE → zwróć 403 Forbidden
```

### 2. Typy Route i Permission Types

System używa trzech typów route, określonych przez `permission_type` w route defaults:

#### Resource Routes (`permission_type: 'resource'`)
Pełny CRUD - wszystkie akcje (view, create, update, delete)

#### View Routes (`permission_type: 'view'`)
Tylko odczyt - zawsze sprawdza uprawnienie `{resource}.view`

#### Action Routes (`permission_type: 'action'`)
Pojedyncze akcje - zawsze sprawdza uprawnienie `{resource}.update`

### 3. Mapowanie Route → Uprawnienie

#### Ekstrakcja Resource z Route

Route `time-logs.index` → Resource: `time-logs`
Route `projects.assignments.index` → Resource: `assignments`
Route `return-trips.cancel` → Resource: `return-trips.cancel` (action route)

#### Mapowanie HTTP Method + Action → Permission Action (Resource Routes)

| HTTP Method | Route Action | Permission Action |
|-------------|--------------|-------------------|
| GET | `index` | `view` |
| GET | `show` | `view` |
| GET | `create` | `create` |
| GET | `edit` | `update` |
| POST | `store` | `create` |
| PUT/PATCH | `update` | `update` |
| DELETE | `destroy` | `delete` |

**WAŻNE:** `index` i `show` oba mapują się do `.view` (nie `viewAny` i `view`).

#### Przykłady Mapowania

- `GET /time-logs` → `time-logs.index` → `time-logs.view` (resource)
- `GET /time-logs/1` → `time-logs.show` → `time-logs.view` (resource)
- `POST /time-logs` → `time-logs.store` → `time-logs.create` (resource)
- `GET /profitability` → `profitability.index` → `profitability.view` (view)
- `POST /return-trips/{id}/cancel` → `return-trips.cancel` → `return-trips.cancel.update` (action)

### 4. Route Groups z Permission Type

Wszystkie route są organizowane w grupy z jawnie ustawionym `permission_type`:

```php
// Resource routes
Route::group(['defaults' => ['permission_type' => 'resource']], function () {
    Route::resource('projects', ProjectController::class);
    // ...
});

// View routes
Route::group(['defaults' => ['permission_type' => 'view']], function () {
    Route::get('/dashboard', ...)->name('dashboard');
    Route::get('/profitability', ...)->name('profitability.index');
    // ...
});

// Action routes
Route::group(['defaults' => ['permission_type' => 'action']], function () {
    Route::post('return-trips/{returnTrip}/cancel', ...)->name('return-trips.cancel');
    // ...
});
```

### 5. Fail-Fast Mechanism

Jeśli route nie ma ustawionego `permission_type`:
- **Dev/Testing:** Rzuca wyjątek z jasnym komunikatem
- **Production:** Abort(500) z logowaniem błędu

Zapewnia to, że każdy nowy route musi mieć jawnie określony typ uprawnienia.

## Cache i Wydajność

### Jak Spatie Cache'uje Uprawnienia

1. **Cache Globalny (24h)**
   - Klucz: `spatie.permission.cache`
   - Zawartość: Wszystkie uprawnienia z bazy jako Collection
   - Automatycznie czyszczony przy zmianach (save/delete na Role/Permission)

2. **Cache Per Request**
   - Role i uprawnienia użytkownika cache'owane w pamięci podczas requestu
   - Przy pierwszym wywołaniu `hasPermissionTo()` w requestcie:
     - Sprawdza cache globalny
     - Pobiera role użytkownika z `model_has_roles`
     - Łączy uprawnienia z ról (`role_has_permissions`)
     - Cache'uje wynik w pamięci na czas requestu

### Czy Przy Każdym Requestcie Sprawdzana Jest Baza?

**NIE!** System jest bardzo wydajny:

- **Pierwszy request po zmianie uprawnień:**
  - Cache miss → zapytanie do bazy
  - Wynik zapisany w cache na 24h

- **Kolejne requesty (w ciągu 24h):**
  - Cache hit → dane z cache
  - **Brak zapytań do bazy** dla uprawnień

- **Po zmianie uprawnień w UI:**
  - Spatie automatycznie czyści cache
  - Następny request pobierze dane z bazy i zaktualizuje cache

## Generowanie Uprawnień z Route

### RoutePermissionService

System używa `RoutePermissionService` do dynamicznego generowania uprawnień z route. **Route są jedynym źródłem prawdy** - każdy route z `permission_type` automatycznie generuje odpowiednie uprawnienia.

### Jak Działa Generowanie

1. **Pobieranie Route:** Serwis iteruje przez wszystkie zarejestrowane route
2. **Filtrowanie:** Tylko route z `permission_type` w defaults są przetwarzane
3. **Mapowanie:** Używa tej samej logiki co middleware do mapowania route → permission name
4. **Filtrowanie viewAny:** Widok tabelki ignoruje `viewAny` - pokazuje tylko `view`

### Automatyczne Tworzenie Uprawnień

Przy zapisie formularza edycji roli:
1. System pobiera wszystkie uprawnienia z route (używając `RoutePermissionService`)
2. Dla każdego zaznaczonego checkboxa sprawdza czy uprawnienie istnieje w bazie
3. Jeśli nie istnieje, automatycznie tworzy je z odpowiednim `type`
4. Następnie przypisuje uprawnienia do roli

### viewAny vs view

**WAŻNE:** System zachowuje pełną semantykę Spatie:
- `viewAny` może istnieć w bazie (zachowane z seederów)
- Widok tabelki **ignoruje `viewAny`** - pokazuje tylko `view` w kolumnie "Czytaj"
- Middleware generuje tylko `view` dla index/show (nie `viewAny`)
- Różna granularność: Blade upraszcza do jednej kolumny "Czytaj", Spatie ma pełną semantykę

## Struktura Bazy Danych

### Tabele Spatie Permission

```
user_roles (role)
├── id
├── name (np. 'kierownik', 'administrator')
└── guard_name ('web')

permissions
├── id
├── name (np. 'time-logs.view', 'projects.create')
├── guard_name ('web')
├── type ('resource', 'view', 'action') - typ uprawnienia
└── ...

role_has_permissions (many-to-many)
├── role_id
└── permission_id

model_has_roles (user → role)
├── model_id (user_id)
├── role_id
└── guard_name
```

### Przykładowe Uprawnienia

Format: `{resource}.{action}`

- `projects.view` - Przeglądanie listy i szczegółów projektów (resource)
- `projects.create` - Tworzenie projektu (resource)
- `projects.update` - Edycja projektu (resource)
- `projects.delete` - Usuwanie projektu (resource)
- `profitability.view` - Dostęp do dashboardu rentowności (view)
- `weekly-overview.view` - Dostęp do planera tygodniowego (view)
- `return-trips.cancel.update` - Anulowanie zjazdu (action)

## Metoda hasPermission() w User Model

```php
public function hasPermission(string $permissionName): bool
{
    // Admin zawsze ma wszystkie uprawnienia
    if ($this->isAdmin()) {
        return true;
    }

    // Użyj checkPermissionTo() zamiast hasPermissionTo()
    // - zwraca false zamiast rzucać wyjątek gdy uprawnienie nie istnieje
    return $this->checkPermissionTo($permissionName);
}
```

**Dlaczego `checkPermissionTo()` zamiast `hasPermissionTo()`?**
- `hasPermissionTo()` rzuca wyjątek `PermissionDoesNotExist` gdy uprawnienie nie istnieje
- `checkPermissionTo()` zwraca `false` - lepsze dla middleware (zwraca 403 zamiast błędu)

## Wykluczone Route

Następujące route są wykluczone z sprawdzania uprawnień:

- `dashboard` - Główny dashboard (bez profitability)
- `profile.*` - Wszystkie route profilu użytkownika
- `no-role` - Strona dla użytkowników bez roli
- `logout` - Wylogowanie
- `home` - Strona główna

**Uwaga:** `dashboard.profitability` jest **chroniony** uprawnieniem `profitability.viewAny` (nie jest wykluczony).

## Zarządzanie Uprawnieniami w UI

### Tabelka Uprawnień

W widoku edycji roli (`/user-roles/{role}/edit`) znajduje się tabelka z wszystkimi zasobami i akcjami. **Uprawnienia są generowane dynamicznie z route** - nie ma potrzeby aktualizowania seederów.

| Zasób | Twórz | Czytaj | Aktualizuj | Usuwaj |
|-------|-------|--------|------------|--------|
| Projekty | ☑ | ☑ | ☑ | ☐ |
| Dashboard rentowności | - | ☑ | - | - |
| Anulowanie zjazdu | - | - | ☑ | - |
| ... | ... | ... | ... | ... |

### Jak Działa

1. **Renderowanie:** System pobiera wszystkie route z `permission_type` i generuje uprawnienia
2. **Wyświetlanie:** Tabelka pokazuje checkboxy wg typu:
   - **VIEW** → tylko kolumna "Czytaj" (`.view`)
   - **ACTION** → tylko kolumna "Aktualizuj" (`.update`)
   - **RESOURCE** → pełny CRUD (`.view`, `.create`, `.update`, `.delete`)
3. **Zapis:** Przy zapisie brakujące uprawnienia są automatycznie tworzone w bazie
4. **Cache:** Spatie automatycznie czyści cache uprawnień
5. **Następny request** → nowe uprawnienia są aktywne

### Lista Zasobów w Tabelce

Wszystkie zasoby dostępne w tabelce:

- Projekty (`projects`)
- Pracownicy (`employees`)
- Pojazdy (`vehicles`)
- Mieszkania (`accommodations`)
- Lokalizacje (`locations`)
- Role (`roles`)
- Przypisania projektów (`assignments`)
- Przypisania pojazdów (`vehicle-assignments`)
- Przypisania mieszkań (`accommodation-assignments`)
- Zapotrzebowania (`demands`)
- Raporty (`reports`)
- Planer tygodniowy (`weekly-overview`)
- Dashboard rentowności (`profitability`)
- Role użytkowników (`user-roles`)
- Użytkownicy (`users`)
- Zdarzenia logistyczne (`logistics-events`)
- Sprzęt (`equipment`)
- Wydania sprzętu (`equipment-issues`)
- Koszty transportu (`transport-costs`)
- Ewidencje godzin (`time-logs`)
- Kary i nagrody (`adjustments`)
- Zaliczki (`advances`)
- Dokumenty (`documents`)
- Dokumenty pracowników (`employee-documents`)
- Stawki pracowników (`employee-rates`)
- Koszty stałe (`fixed-costs`)
- Payroll (`payrolls`)
- Koszty zmienne projektów (`project-variable-costs`)
- Rotacje (`rotations`)

## Przykłady Użycia

### W Middleware (Automatyczne)

Middleware automatycznie sprawdza uprawnienia dla wszystkich route w grupie `permission.check`:

```php
Route::middleware(['auth', 'verified', 'role.required', 'permission.check'])->group(function () {
    Route::resource('time-logs', TimeLogController::class);
    // Automatycznie sprawdza: time-logs.viewAny, time-logs.view, time-logs.create, etc.
});
```

### W Widokach Blade

Zamiast `@can` (które wymaga Policy), używamy bezpośredniego sprawdzenia:

```blade
@if(auth()->user()->hasPermission('time-logs.delete'))
    <x-action-buttons deleteRoute="..." />
@endif
```

### W Kontrolerach

**NIE używamy** `$this->authorize()` - middleware obsługuje to automatycznie.

## Migracja z Policy

### Co Zostało Usunięte

- Wszystkie pliki Policy (`app/Policies/*.php`)
- Rejestracja Policy w `AuthServiceProvider`
- Wszystkie wywołania `$this->authorize()` w kontrolerach
- Wszystkie dyrektywy `@can` w widokach Blade

### Co Zostało Dodane

- Middleware `CheckResourcePermission`
- Metoda `hasPermission()` w modelu User
- Tabelka uprawnień w UI (generowana dynamicznie z route)
- `RoutePermissionService` - generowanie uprawnień z route
- Automatyczne tworzenie uprawnień przy zapisie formularza

## Rozwiązywanie Problemów

### Problem: "There is no permission named X for guard web"

**Przyczyna:** Uprawnienie nie istnieje w bazie danych.

**Rozwiązanie:**
1. Sprawdź czy route ma ustawiony `permission_type` w defaults
2. Uprawnienie zostanie automatycznie utworzone przy zapisie formularza edycji roli
3. Jeśli problem występuje, sprawdź czy route jest w odpowiedniej grupie w `routes/web.php`

### Problem: Route zwraca 403 mimo że użytkownik ma uprawnienie

**Przyczyna:** Cache uprawnień nie został odświeżony.

**Rozwiązanie:**
```bash
php artisan permission:cache-reset
# lub
php artisan optimize:clear
```

### Problem: Zmiany w tabelce uprawnień nie działają

**Przyczyna:** Cache uprawnień.

**Rozwiązanie:** Spatie automatycznie czyści cache przy zmianach, ale jeśli problem występuje:
```bash
php artisan permission:cache-reset
```

## Best Practices

1. **Zawsze używaj nazw route** - Middleware wymaga nazwanych route
2. **Ustawiaj `permission_type` dla każdego route** - Używaj grup route z `defaults`
3. **Route są jedynym źródłem prawdy** - Nie ma potrzeby aktualizowania seederów
4. **Nowe route automatycznie pojawiają się w tabelce** - System generuje uprawnienia z route
5. **Używaj `hasPermission()` zamiast `@can`** - Spójność z nowym systemem
6. **Nie używaj `$this->authorize()` w kontrolerach** - Middleware obsługuje to automatycznie
7. **Organizuj route w grupy** - Resource, View, Action - dla czytelności i spójności

## Podsumowanie

System autoryzacji jest:
- ✅ **Dynamiczny** - Nie wymaga pisania Policy dla każdego modelu
- ✅ **Automatyczny** - Uprawnienia generowane z route, brak potrzeby seederów
- ✅ **Wydajny** - Cache'uje uprawnienia na 24h
- ✅ **Elastyczny** - Zarządzanie uprawnieniami przez UI
- ✅ **Bezpieczny** - Administratorzy mają pełny dostęp, reszta przez uprawnienia
- ✅ **Prosty** - Jedna tabelka w UI jako źródło prawdy
- ✅ **Skalowalny** - Nowy route = automatycznie w tabelce uprawnień
- ✅ **Fail-fast** - Route bez `permission_type` rzucają błąd w dev/testing
