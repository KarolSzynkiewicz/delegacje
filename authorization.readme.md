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
Ekstraktuj resource z nazwy route
    ↓
Mapuj HTTP method + route action → permission action
    ↓
Zbuduj nazwę uprawnienia: {resource}.{action}
    ↓
Sprawdź czy użytkownik ma uprawnienie
    ↓
Jeśli TAK → pozwól na dostęp
Jeśli NIE → zwróć 403 Forbidden
```

### 2. Mapowanie Route → Uprawnienie

#### Ekstrakcja Resource z Route

Route `time-logs.index` → Resource: `time-logs`
Route `projects.assignments.index` → Resource: `assignments`
Route `dashboard.profitability` → Resource: `profitability` (przez custom mapping)

#### Mapowanie HTTP Method + Action → Permission Action

| HTTP Method | Route Action | Permission Action |
|-------------|--------------|-------------------|
| GET | `index` | `viewAny` |
| GET | `show` | `view` |
| GET | `create` | `create` |
| GET | `edit` | `update` |
| POST | `store` | `create` |
| PUT/PATCH | `update` | `update` |
| DELETE | `destroy` | `delete` |
| GET | (brak) | `viewAny` (fallback) |

#### Przykłady Mapowania

- `GET /time-logs` → `time-logs.index` → `time-logs.viewAny`
- `GET /time-logs/1` → `time-logs.show` → `time-logs.view`
- `POST /time-logs` → `time-logs.store` → `time-logs.create`
- `PUT /time-logs/1` → `time-logs.update` → `time-logs.update`
- `DELETE /time-logs/1` → `time-logs.destroy` → `time-logs.delete`

### 3. Custom Mappings

Niektóre route wymagają specjalnego mapowania:

```php
'project-demands' => 'demands',
'project-assignments' => 'assignments',
'return-trips' => 'logistics-events',
'dashboard' => 'profitability', // dashboard.profitability → profitability
```

### 4. Specjalne Przypadki

#### weekly-overview
Wszystkie route `weekly-overview.*` sprawdzają uprawnienie `weekly-overview.view` (nie `viewAny`), ponieważ w bazie istnieje tylko to jedno uprawnienie.

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

## Struktura Bazy Danych

### Tabele Spatie Permission

```
user_roles (role)
├── id
├── name (np. 'kierownik', 'administrator')
└── guard_name ('web')

permissions
├── id
├── name (np. 'time-logs.viewAny', 'projects.create')
├── guard_name ('web')
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

- `projects.viewAny` - Przeglądanie listy projektów
- `projects.view` - Szczegóły projektu
- `projects.create` - Tworzenie projektu
- `projects.update` - Edycja projektu
- `projects.delete` - Usuwanie projektu
- `time-logs.viewAny` - Przeglądanie listy ewidencji godzin
- `profitability.viewAny` - Dostęp do dashboardu rentowności
- `weekly-overview.view` - Dostęp do planera tygodniowego

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

W widoku edycji roli (`/user-roles/{role}/edit`) znajduje się tabelka z wszystkimi zasobami i akcjami:

| Zasób | Czytaj | Tworzenie | Edycja | Usuwanie |
|-------|--------|-----------|--------|----------|
| Projekty | ☑ | ☑ | ☑ | ☐ |
| Pracownicy | ☑ | ☑ | ☑ | ☐ |
| ... | ... | ... | ... | ... |

### Jak Działa

1. **Zmiana checkboxa** → zapis do bazy (`role_has_permissions`)
2. **Spatie automatycznie czyści cache** uprawnień
3. **Następny request** → nowe uprawnienia są aktywne

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
- Tabelka uprawnień w UI
- Seeder uprawnień (`PermissionSeeder`)

## Rozwiązywanie Problemów

### Problem: "There is no permission named X for guard web"

**Przyczyna:** Uprawnienie nie istnieje w bazie danych.

**Rozwiązanie:**
1. Uruchom seeder: `php artisan db:seed --class=PermissionSeeder`
2. Sprawdź czy uprawnienie jest w `PermissionSeeder.php`

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
2. **Dodawaj nowe uprawnienia do PermissionSeeder** - Zapewnia spójność
3. **Dodawaj nowe zasoby do tabelki w UI** - Użytkownicy muszą móc zarządzać uprawnieniami
4. **Używaj `hasPermission()` zamiast `@can`** - Spójność z nowym systemem
5. **Nie używaj `$this->authorize()` w kontrolerach** - Middleware obsługuje to automatycznie

## Podsumowanie

System autoryzacji jest:
- ✅ **Dynamiczny** - Nie wymaga pisania Policy dla każdego modelu
- ✅ **Wydajny** - Cache'uje uprawnienia na 24h
- ✅ **Elastyczny** - Zarządzanie uprawnieniami przez UI
- ✅ **Bezpieczny** - Administratorzy mają pełny dostęp, reszta przez uprawnienia
- ✅ **Prosty** - Jedna tabelka w UI jako źródło prawdy
