# Frontend Architecture - Modelowy Widok

## Wzorcowy Widok: `equipment/index.blade.php`

Ten widok jest **wzorcowym przykładem** implementacji zgodnej z architekturą UI systemu. Wszystkie nowe widoki powinny być tworzone według tego wzorca.

## Zasady Architektury UI

### 1. **Zero Hardcoded HTML dla Komponentów UI**

❌ **NIGDY:**
```blade
<div class="card shadow-sm border-0">
    <div class="card-body">...</div>
</div>
<button class="btn btn-primary">
    <i class="bi bi-plus-circle"></i> Dodaj
</button>
<span class="badge bg-danger">Status</span>
```

✅ **ZAWSZE:**
```blade
<x-ui.card>...</x-ui.card>
<x-ui.button variant="primary" action="create">Dodaj</x-ui.button>
<x-ui.badge variant="danger">Status</x-ui.badge>
```

### 2. **Tylko Bootstrap Layout Classes**

W widokach używamy **TYLKO** klas Bootstrap do layoutu:
- `row`, `col-*` - grid system
- `d-flex`, `justify-content-*`, `align-items-*` - flexbox
- `mb-*`, `mt-*`, `p-*`, `gap-*` - spacing
- `table-responsive` - responsive tables

**NIE używamy:**
- Hardcoded `card`, `btn`, `badge` - tylko komponenty `x-ui.*`
- Inline styles (poza koniecznymi przypadkami)
- Tailwind classes

### 3. **Brak Zbędnych Wrapperów**

Layout `app-layout` już zawiera:
- `<main class="container-xxl py-4">` - nie dodajemy dodatkowych `container-xxl` i `py-4`

❌ **NIGDY:**
```blade
<div class="py-4">
    <div class="container-xxl">
        <x-ui.card>...</x-ui.card>
    </div>
</div>
```

✅ **ZAWSZE:**
```blade
<x-ui.card>...</x-ui.card>
```

## Struktura Modelowego Widoku

### 1. Header z `x-ui.page-header`

```blade
<x-slot name="header">
    <x-ui.page-header title="Tytuł Strony">
        <x-slot name="left">
            <!-- Opcjonalnie: przyciski po lewej (np. Back) -->
            <x-ui.button variant="ghost" href="{{ route('index') }}" action="back">
                Powrót
            </x-ui.button>
        </x-slot>
        
        <x-slot name="right">
            <!-- Opcjonalnie: przyciski po prawej (np. Create) -->
            <x-ui.button 
                variant="primary"
                href="{{ route('equipment.create') }}"
                routeName="equipment.create"
                action="create"
            >
                Dodaj
            </x-ui.button>
        </x-slot>
    </x-ui.page-header>
</x-slot>
```

**Layout header:**
- **Lewa kolumna** - przyciski po lewej (np. Back)
- **Środek** - tytuł wyśrodkowany
- **Prawa kolumna** - przyciski po prawej (np. Create, Edit)

### 2. Główna Zawartość z `x-ui.card`

```blade
<x-ui.card>
    <!-- Zawartość -->
</x-ui.card>
```

**Uwaga:** Nie używamy `<div class="card">` - tylko komponent `x-ui.card`.

### 3. Tabele

```blade
<div class="table-responsive">
    <table class="table align-middle">
        <thead>
            <tr>
                <th>Kolumna 1</th>
                <th>Kolumna 2</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $item)
                <tr>
                    <td>{{ $item->name }}</td>
                    <td>
                        <x-ui.badge variant="success">Status</x-ui.badge>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
```

**Uwagi:**
- `th` nie potrzebują `class="text-start"` - jest to ustawione globalnie w `app.css`
- Używamy komponentów `x-ui.badge` zamiast `<span class="badge">`

### 4. Empty State

```blade
<x-ui.empty-state 
    icon="inbox" 
    message="Brak danych w systemie."
>
    <x-ui.button 
        variant="primary" 
        href="{{ route('create') }}"
        action="create"
    >
        Dodaj pierwszy element
    </x-ui.button>
</x-ui.empty-state>
```

## Komponenty UI

### `x-ui.button`

**Automatyczne ikony:**
- Użyj prop `action` zamiast ręcznego dodawania `<i class="bi...">`
- Komponent automatycznie dodaje odpowiednią ikonę na podstawie `ButtonAction` enum

**Automatyczne generowanie uprawnień:**
- Jeśli podasz `routeName`, komponent automatycznie generuje uprawnienie z route name
- Nie musisz ręcznie sprawdzać uprawnień w widoku - komponent robi to za Ciebie
- Jeśli użytkownik nie ma dostępu, przycisk nie jest renderowany

**Logika generowania uprawnień:**
- `equipment.create` → `equipment.create`
- `equipment.edit` → `equipment.update` (mapowanie edit → update)
- `equipment.destroy` → `equipment.delete` (mapowanie destroy → delete)
- `equipment.index` → `equipment.view` (mapowanie index → view)
- `equipment.show` → `equipment.view` (mapowanie show → view)
- `projects.assignments.create` → `assignments.create` (nested routes)

**Przykłady:**

```blade
<!-- Z automatyczną ikoną i automatycznym uprawnieniem -->
<x-ui.button 
    variant="primary" 
    href="{{ route('equipment.create') }}" 
    routeName="equipment.create"
    action="create"
>
    Dodaj Sprzęt
</x-ui.button>
<!-- Komponent automatycznie sprawdza uprawnienie: equipment.create -->

<!-- Z ręcznym uprawnieniem (gdy route name nie wystarcza) -->
<x-ui.button 
    variant="danger" 
    href="{{ route('destroy', $item) }}" 
    action="delete"
    permission="resource.delete"
>
    Usuń
</x-ui.button>

<!-- Bez uprawnień (zawsze widoczny) -->
<x-ui.button variant="ghost" href="{{ route('index') }}" action="back">
    Powrót
</x-ui.button>
```

**Dostępne akcje (ButtonAction enum):**
- `create`, `add` → `bi-plus-circle`
- `edit` → `bi-pencil`
- `save` → `bi-save`
- `delete` → `bi-trash`
- `back` → `bi-arrow-left`
- `view` → `bi-eye`
- `search` → `bi-search`
- `filter` → `bi-funnel`
- `export` → `bi-download`
- `import` → `bi-upload`
- `print` → `bi-printer`
- `refresh` → `bi-arrow-clockwise`
- `cancel` → `bi-x-circle`
- `confirm` → `bi-check-circle`

### `x-ui.page-header`

**Struktura:**
- `title` - tytuł strony (prop)
- `left` slot - przyciski po lewej (named slot)
- `right` slot - przyciski po prawej (named slot)
- default slot - dodatkowe elementy

**Layout:**
- Tytuł jest zawsze wyśrodkowany
- Przyciski po lewej i prawej stronie zachowują równowagę

### `x-ui.card`

**Użycie:**
```blade
<x-ui.card>
    <!-- Zawartość karty -->
</x-ui.card>
```

**Opcjonalne propsy:**
- `label` - etykieta karty (wyświetlana nad zawartością)
- `variant` - `default`, `hover`, `elevated`

### `x-ui.badge`

**Użycie:**
```blade
<x-ui.badge variant="success">OK</x-ui.badge>
<x-ui.badge variant="danger">Błąd</x-ui.badge>
<x-ui.badge variant="warning">Ostrzeżenie</x-ui.badge>
<x-ui.badge variant="info">Info</x-ui.badge>
```

**Dostępne warianty:**
- `success` - zielony
- `danger` - czerwony
- `warning` - żółty
- `info` - niebieski
- `accent` - fioletowy

### `x-ui.empty-state`

**Użycie:**
```blade
<x-ui.empty-state 
    icon="inbox" 
    message="Brak danych w systemie."
>
    <!-- Opcjonalnie: przycisk w slocie -->
    <x-ui.button variant="primary" href="{{ route('create') }}" action="create">
        Dodaj pierwszy element
    </x-ui.button>
</x-ui.empty-state>
```

## System Uprawnień w Komponentach

### Automatyczne Sprawdzanie Uprawnień

**Przed (z warunkami w widokach):**
```blade
@if(auth()->user()->hasPermission('equipment.create'))
    <x-ui.button href="{{ route('equipment.create') }}">
        <i class="bi bi-plus-circle"></i> Dodaj
    </x-ui.button>
@endif
```

**Po (automatyczne generowanie z routeName):**
```blade
<x-ui.button 
    href="{{ route('equipment.create') }}"
    routeName="equipment.create"
    action="create"
>
    Dodaj
</x-ui.button>
```

**Jak to działa:**
1. Komponent automatycznie generuje uprawnienie z `routeName`: `equipment.create` → `equipment.create`
2. Sprawdza czy użytkownik ma uprawnienie
3. Jeśli nie ma - przycisk nie jest renderowany
4. Ikona jest dodawana automatycznie na podstawie `action="create"`

**Korzyści:**
- **Zero warunków w widokach** - komponent sam sprawdza uprawnienia
- **Automatyczne ikony** - nie trzeba ręcznie dodawać `<i class="bi...">`
- **Automatyczne generowanie uprawnień** - nie trzeba ręcznie podawać `permission`
- **Centralne zarządzanie** - logika w komponencie, nie w widokach
- **Czytelność** - widok deklaruje intencję (`routeName`, `action`), komponent obsługuje resztę

## Przykład: Pełny Modelowy Widok

```blade
<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Sprzęt">
            <x-slot name="right">
                <x-ui.button 
                    variant="primary"
                    href="{{ route('equipment.create') }}"
                    routeName="equipment.create"
                    action="create"
                >
                    Dodaj Sprzęt
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <x-ui.card>
        @if($equipment->count() > 0)
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Nazwa</th>
                            <th>Status</th>
                            <th>Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($equipment as $item)
                            <tr>
                                <td>{{ $item->name }}</td>
                                <td>
                                    <x-ui.badge variant="success">OK</x-ui.badge>
                                </td>
                                <td>
                                    <x-action-buttons
                                        viewRoute="{{ route('equipment.show', $item) }}"
                                        editRoute="{{ route('equipment.edit', $item) }}"
                                        deleteRoute="{{ route('equipment.destroy', $item) }}"
                                    />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($equipment->hasPages())
                <div class="mt-3">
                    {{ $equipment->links() }}
                </div>
            @endif
        @else
            <x-ui.empty-state 
                icon="inbox" 
                message="Brak sprzętu w systemie."
            >
                <x-ui.button 
                    variant="primary" 
                    href="{{ route('equipment.create') }}"
                    action="create"
                >
                    Dodaj pierwszy sprzęt
                </x-ui.button>
            </x-ui.empty-state>
        @endif
    </x-ui.card>
</x-app-layout>
```

## Checklist dla Nowych Widoków

- [ ] Używa `x-ui.page-header` zamiast hardcoded header
- [ ] Używa `x-ui.card` zamiast `<div class="card">`
- [ ] Używa `x-ui.button` z prop `action` (bez ręcznych ikon)
- [ ] Używa `x-ui.badge` zamiast `<span class="badge">`
- [ ] Używa `x-ui.empty-state` dla pustych stanów
- [ ] Nie ma zbędnych wrapperów (`container-xxl`, `py-4`)
- [ ] `th` nie mają `class="text-start"` (ustawione globalnie)
- [ ] Używa prop `routeName` dla automatycznego generowania uprawnień (lub `permission` jeśli potrzebne ręczne)
- [ ] Brak warunków `@if(auth()->user()->hasPermission(...))` w widokach
- [ ] Tylko Bootstrap layout classes (row, col, d-flex, etc.)
- [ ] Brak inline styles (poza koniecznymi przypadkami)

## Pliki Referencyjne

- **Modelowy widok:** `resources/views/equipment/index.blade.php`
- **Komponenty UI:** `resources/views/components/ui/`
- **Enum akcji:** `app/Enums/ButtonAction.php`
- **Class-based komponenty:** `app/View/Components/Ui/`
- **Style globalne:** `resources/css/app.css`
