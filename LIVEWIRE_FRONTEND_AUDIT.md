# Raport Audytu Frontendu - Komponenty Livewire

## 📊 Statystyki

- **Komponenty Livewire:** 10 plików
- **Powtarzające się wzorce:** 6 głównych wzorców
- **Duplikacja kodu:** ~60-70% w sekcjach filtrów i tabel

---

## 🎯 Zidentyfikowane Wzorce do Wydzielenia

### 1. **`<x-livewire.filter-card />`** - ⭐⭐⭐⭐⭐
**Częstotliwość:** 10/10 komponentów tabel

**Problem:**
Powtarzający się wzorzec karty z filtrami i statystykami:
```blade
<x-ui.card class="mb-4">
    <div class="mb-4 pb-3 border-top border-bottom">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h3 class="fs-5 fw-semibold mb-1">Tytuł</h3>
                <p class="small text-muted mb-0">
                    @if($hasFilters)
                        Znaleziono: <span class="fw-semibold">{{ $items->total() }}</span> elementów
                    @else
                        Łącznie: <span class="fw-semibold">{{ $items->total() }}</span> elementów
                    @endif
                </p>
            </div>
            @if($hasFilters)
                <x-ui.button variant="ghost" wire:click="clearFilters" class="btn-sm">
                    <i class="bi bi-x-circle me-1"></i> Wyczyść filtry
                </x-ui.button>
            @endif
        </div>
    </div>
    <!-- Filtry -->
    <div class="row g-3">
        ...
    </div>
</x-ui.card>
```

**Rekomendacja:**
```blade
<x-livewire.filter-card 
    title="Pracownicy"
    :total="$employees->total()"
    :has-filters="$search || $roleFilter"
    wire:clear-filters="clearFilters"
>
    <div class="row g-3">
        <!-- Filtry -->
    </div>
</x-livewire.filter-card>
```

**Pliki do refaktoryzacji:**
- `livewire/employees-table.blade.php`
- `livewire/projects-table.blade.php`
- `livewire/vehicles-table.blade.php`
- `livewire/accommodations-table.blade.php`
- `livewire/rotations-table.blade.php`
- `livewire/assignments-table.blade.php`
- `livewire/vehicle-assignments-table.blade.php`
- `livewire/accommodation-assignments-table.blade.php`

---

### 2. **`<x-livewire.sortable-header />`** - ⭐⭐⭐⭐⭐
**Częstotliwość:** 8/10 komponentów (wszystkie tabele z sortowaniem)

**Problem:**
Powtarzający się kod sortowalnych nagłówków:
```blade
<th class="text-start">
    <button wire:click="sortBy('name')" class="btn-link text-decoration-none p-0 fw-semibold d-flex align-items-center gap-1" style="background: none; border: none; color: var(--text-main);">
        <span>Nazwa</span>
        @if($sortField === 'name')
            @if($sortDirection === 'asc')
                <i class="bi bi-chevron-up"></i>
            @else
                <i class="bi bi-chevron-down"></i>
            @endif
        @else
            <i class="bi bi-chevron-expand text-muted"></i>
        @endif
    </button>
</th>
```

**Rekomendacja:**
```blade
<x-livewire.sortable-header 
    field="name" 
    :sort-field="$sortField" 
    :sort-direction="$sortDirection"
    wire:click="sortBy('name')"
>
    Nazwa
</x-livewire.sortable-header>
```

**Pliki do refaktoryzacji:**
- `livewire/employees-table.blade.php` (2 wystąpienia)
- `livewire/projects-table.blade.php` (2 wystąpienia)
- `livewire/vehicles-table.blade.php` (1 wystąpienie)
- `livewire/accommodations-table.blade.php` (1 wystąpienie)
- `livewire/rotations-table.blade.php`
- `livewire/assignments-table.blade.php`
- ... i inne tabele z sortowaniem

---

### 3. **`<x-livewire.search-input />`** - ⭐⭐⭐⭐
**Częstotliwość:** 10/10 komponentów

**Problem:**
Powtarzający się wzorzec wyszukiwarki z ikoną:
```blade
<div class="col-md-6">
    <label class="form-label small">
        <i class="bi bi-search me-1"></i> Szukaj
    </label>
    <div class="position-relative">
        <input type="text" wire:model.live.debounce.300ms="search" 
            placeholder="Imię, nazwisko lub email..."
            class="form-control ps-5">
        <i class="bi bi-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
    </div>
</div>
```

**Rekomendacja:**
```blade
<x-livewire.search-input 
    wire:model.live.debounce.300ms="search"
    placeholder="Imię, nazwisko lub email..."
    :col-size="6"
/>
```

**Pliki do refaktoryzacji:**
- Wszystkie 10 komponentów Livewire

---

### 4. **`<x-livewire.pagination-wrapper />`** - ⭐⭐⭐
**Częstotliwość:** 10/10 komponentów

**Problem:**
Powtarzający się wrapper dla paginacji:
```blade
@if($items->hasPages())
    <div class="mt-3 pt-3 border-top">
        {{ $items->links() }}
    </div>
@endif
```

**Rekomendacja:**
```blade
<x-livewire.pagination-wrapper :items="$employees" />
```

**Pliki do refaktoryzacji:**
- Wszystkie 10 komponentów Livewire

---

### 5. **`<x-ui.avatar />`** - ⭐⭐⭐
**Częstotliwość:** 3/10 komponentów (employees, vehicles, accommodations)

**Problem:**
Powtarzający się kod avatara z obrazem lub inicjałami:
```blade
@if($employee->image_path)
    <img src="{{ $employee->image_url }}" alt="{{ $employee->full_name }}" 
        class="rounded-circle border border-2" 
        style="width: 50px; height: 50px; object-fit: cover;">
@else
    <div class="avatar-ui" style="width: 50px; height: 50px;">
        <span>{{ substr($employee->first_name, 0, 1) }}{{ substr($employee->last_name, 0, 1) }}</span>
    </div>
@endif
```

**Rekomendacja:**
```blade
<x-ui.avatar 
    :image-url="$employee->image_url"
    :alt="$employee->full_name"
    :initials="substr($employee->first_name, 0, 1) . substr($employee->last_name, 0, 1)"
    size="50px"
    shape="circle"
/>
```

**Pliki do refaktoryzacji:**
- `livewire/employees-table.blade.php`
- `livewire/vehicles-table.blade.php`
- `livewire/accommodations-table.blade.php`

---

### 6. **`<x-livewire.table-actions />`** - ⭐⭐
**Częstotliwość:** 10/10 komponentów

**Problem:**
Powtarzający się wzorzec przycisków akcji w tabelach:
```blade
<td class="text-end">
    <div class="d-flex gap-2 justify-content-end">
        <x-ui.button variant="ghost" href="{{ route('employees.show', $employee) }}" class="btn-sm">
            <i class="bi bi-eye"></i>
            <span class="d-none d-sm-inline ms-1">Zobacz</span>
        </x-ui.button>
        <x-ui.button variant="ghost" href="{{ route('employees.edit', $employee) }}" class="btn-sm">
            <i class="bi bi-pencil"></i>
            <span class="d-none d-sm-inline ms-1">Edytuj</span>
        </x-ui.button>
    </div>
</td>
```

**Rekomendacja:**
```blade
<x-livewire.table-actions>
    <x-livewire.table-actions.view :url="route('employees.show', $employee)" />
    <x-livewire.table-actions.edit :url="route('employees.edit', $employee)" />
    <!-- Opcjonalnie: delete, custom actions -->
</x-livewire.table-actions>
```

**Pliki do refaktoryzacji:**
- Wszystkie 10 komponentów Livewire

---

## 📋 Plan Implementacji

### Faza 1: Komponenty o Najwyższym Priorytecie
1. ✅ `<x-livewire.filter-card />`
2. ✅ `<x-livewire.sortable-header />`
3. ✅ `<x-livewire.search-input />`

### Faza 2: Komponenty o Średnim Priorytecie
4. ✅ `<x-livewire.pagination-wrapper />`
5. ✅ `<x-ui.avatar />`

### Faza 3: Komponenty o Niskim Priorytecie
6. ✅ `<x-livewire.table-actions />`

---

## 💡 Dodatkowe Rekomendacje

### 1. **Bazowy komponent tabeli Livewire**
Rozważyć utworzenie `<x-livewire.data-table />` który łączy:
- Filter card
- Tabelę z sortowaniem
- Paginację
- Empty state

### 2. **Unifikacja stylów filtrów**
Upewnić się, że wszystkie filtry mają spójny wygląd i zachowanie.

### 3. **Komponenty dla selectów z filtrami**
Utworzyć `<x-livewire.filter-select />` dla powtarzających się selectów.

---

## 📊 Oszacowany Wpływ

Po implementacji wszystkich komponentów:
- **Redukcja duplikacji kodu:** ~60-70% w komponentach Livewire
- **Łatwiejsza konserwacja:** Zmiany w jednym miejscu
- **Spójność UI:** Wszystkie tabele wyglądają tak samo
- **Szybszy development:** Mniej kodu do pisania przy tworzeniu nowych tabel

---

**Data utworzenia:** {{ date('Y-m-d') }}
**Wersja:** 1.0
