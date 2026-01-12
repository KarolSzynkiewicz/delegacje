# Raport Audytu Frontendu - Komponenty do Utworzenia

## 📊 Statystyki

- **Tabele:** 100 wystąpień w 43 plikach
- **Karty:** 226 wystąpień w 46 plikach  
- **Przyciski:** 125 wystąpień w 53 plikach
- **Badge'y:** 152 wystąpienia w 45 plikach
- **Empty states:** 119 wystąpień w 62 plikach
- **Tabele z akcjami:** 41 plików
- **Header z akcjami:** 71 plików
- **Formularze DELETE:** 7 wystąpień w 6 plikach

---

## 🎯 PRIORYTET 1: Komponenty o Najwyższym Priorytecie

### 1. **`<x-ui.empty-state />`** - ⭐⭐⭐⭐⭐
**Częstotliwość:** 119 wystąpień w 62 plikach

**Problem:**
Powtarzający się kod dla pustych stanów:
```blade
@empty
    <tr>
        <td colspan="X" class="text-center py-5">
            <div class="empty-state">
                <i class="bi bi-[icon] text-muted fs-1 d-block mb-2"></i>
                <p class="text-muted small fw-medium mb-2">Brak danych</p>
                @if($hasFilters)
                    <x-ui.button variant="ghost" wire:click="clearFilters">Wyczyść filtry</x-ui.button>
                @endif
            </div>
        </td>
    </tr>
@endempty
```

**Rekomendacja:**
```blade
<x-ui.empty-state 
    icon="people" 
    message="Brak pracowników"
    :has-filters="$search || $roleFilter"
    wire:clear-filters="clearFilters"
/>
```

**Pliki do refaktoryzacji:**
- `livewire/employees-table.blade.php`
- `livewire/projects-table.blade.php`
- `livewire/vehicles-table.blade.php`
- `livewire/accommodations-table.blade.php`
- `employees/show.blade.php`
- `projects/show.blade.php`
- `vehicles/show.blade.php`
- `accommodations/show.blade.php`
- ... i 54 inne pliki

---

### 2. **`<x-ui.table-header />`** - ⭐⭐⭐⭐⭐
**Częstotliwość:** 71 plików z `d-flex justify-content-between align-items-center`

**Problem:**
Powtarzający się wzorzec nagłówka z tytułem i przyciskami:
```blade
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Tytuł</h5>
    <x-ui.button variant="primary" href="...">Dodaj</x-ui.button>
</div>
```

**Rekomendacja:**
```blade
<x-ui.table-header title="Dokumenty">
    <x-slot name="actions">
        <x-ui.button variant="primary" href="...">Dodaj Dokument</x-ui.button>
    </x-slot>
</x-ui.table-header>
```

**Pliki do refaktoryzacji:**
- `employees/show.blade.php` (5 wystąpień)
- `projects/show.blade.php`
- `vehicles/show.blade.php`
- `accommodations/show.blade.php`
- `livewire/employees-table.blade.php`
- `livewire/projects-table.blade.php`
- ... i 65 innych plików

---

### 3. **`<x-ui.action-buttons />`** - ⭐⭐⭐⭐
**Częstotliwość:** 29 wystąpień w 27 plikach z `d-flex gap-1` lub `d-flex gap-2`

**Problem:**
Powtarzający się wzorzec przycisków akcji w tabelach:
```blade
<div class="d-flex gap-1">
    <x-ui.button variant="warning" href="..." class="btn-sm">Edytuj</x-ui.button>
    <form action="..." method="POST" class="d-inline">
        @csrf
        @method('DELETE')
        <x-ui.button variant="danger" type="submit" class="btn-sm" onclick="return confirm('...')">Usuń</x-ui.button>
    </form>
</div>
```

**Rekomendacja:**
```blade
<x-ui.action-buttons>
    <x-ui.button variant="warning" href="..." class="btn-sm">Edytuj</x-ui.button>
    <x-ui.action-buttons.delete 
        :url="route('...')" 
        message="Czy na pewno chcesz usunąć?"
        class="btn-sm"
    />
</x-ui.action-buttons>
```

**Pliki do refaktoryzacji:**
- `employees/show.blade.php` (2 wystąpienia)
- `livewire/employee-documents-grouped.blade.php`
- `employees/rotations/index.blade.php`
- `demands/all.blade.php`
- ... i 23 inne pliki

---

### 4. **`<x-ui.detail-list />`** - ⭐⭐⭐⭐
**Częstotliwość:** Powtarza się w widokach `show.blade.php`

**Problem:**
Powtarzający się wzorzec listy szczegółów:
```blade
<dl class="row mb-0">
    <div class="col-md-6 mb-3">
        <dt class="fw-semibold mb-1">Nazwa:</dt>
        <dd>{{ $project->name }}</dd>
    </div>
    <div class="col-md-6 mb-3">
        <dt class="fw-semibold mb-1">Status:</dt>
        <dd>
            <x-ui.badge variant="...">...</x-ui.badge>
        </dd>
    </div>
</dl>
```

**Rekomendacja:**
```blade
<x-ui.detail-list>
    <x-ui.detail-item label="Nazwa">{{ $project->name }}</x-ui.detail-item>
    <x-ui.detail-item label="Status">
        <x-ui.badge variant="success">Aktywny</x-ui.badge>
    </x-ui.detail-item>
    <x-ui.detail-item label="Opis" :full-width="true">{{ $project->description }}</x-ui.detail-item>
</x-ui.detail-list>
```

**Pliki do refaktoryzacji:**
- `projects/show.blade.php`
- `vehicles/show.blade.php`
- `accommodations/show.blade.php`
- `employees/show.blade.php`
- `locations/show.blade.php`
- `roles/show.blade.php`
- `equipment/show.blade.php`
- ... i inne widoki `show.blade.php`

---

### 5. **`<x-ui.delete-form />`** - ⭐⭐⭐
**Częstotliwość:** 7 wystąpień w 6 plikach

**Problem:**
Powtarzający się formularz DELETE:
```blade
<form action="{{ route('...') }}" method="POST" class="d-inline">
    @csrf
    @method('DELETE')
    <x-ui.button variant="danger" type="submit" class="btn-sm" onclick="return confirm('...')">Usuń</x-ui.button>
</form>
```

**Rekomendacja:**
```blade
<x-ui.delete-form 
    :url="route('employees.employee-documents.destroy', [$employee, $employeeDocument])"
    message="Czy na pewno chcesz usunąć ten dokument?"
    class="btn-sm"
/>
```

**Pliki do refaktoryzacji:**
- `employees/show.blade.php` (2 wystąpienia)
- `livewire/employee-documents-grouped.blade.php`
- `employees/rotations/index.blade.php`
- `demands/all.blade.php`
- `components/delete-button.blade.php` (można zastąpić)
- `components/action-buttons.blade.php` (można zastąpić)

---

## 🎯 PRIORYTET 2: Komponenty o Średnim Priorytecie

### 6. **`<x-ui.sortable-header />`** - ⭐⭐⭐
**Częstotliwość:** Powtarza się w Livewire tabelach

**Problem:**
Powtarzający się kod sortowalnych nagłówków:
```blade
<th>
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
<x-ui.sortable-header 
    field="name" 
    :sort-field="$sortField" 
    :sort-direction="$sortDirection"
    wire:click="sortBy('name')"
>
    Nazwa
</x-ui.sortable-header>
```

**Pliki do refaktoryzacji:**
- `livewire/employees-table.blade.php`
- `livewire/projects-table.blade.php`
- `livewire/vehicles-table.blade.php`
- `livewire/accommodations-table.blade.php`
- `livewire/assignments-table.blade.php`
- ... i inne Livewire tabele

---

### 7. **`<x-ui.filter-card />`** - ⭐⭐⭐
**Częstotliwość:** Powtarza się w Livewire tabelach

**Problem:**
Powtarzający się wzorzec karty z filtrami:
```blade
<x-ui.card class="mb-4">
    <div class="mb-4 pb-3 border-top border-bottom">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h3 class="fs-5 fw-semibold mb-1">Tytuł</h3>
                <p class="small text-muted mb-0">Łącznie: <span class="fw-semibold">{{ $total }}</span> elementów</p>
            </div>
            @if($hasFilters)
                <x-ui.button variant="ghost" wire:click="clearFilters" class="btn-sm">Wyczyść filtry</x-ui.button>
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
<x-ui.filter-card 
    title="Pracownicy"
    :total="$employees->total()"
    :has-filters="$search || $roleFilter"
    wire:clear-filters="clearFilters"
>
    <div class="row g-3">
        <!-- Filtry -->
    </div>
</x-ui.filter-card>
```

**Pliki do refaktoryzacji:**
- `livewire/employees-table.blade.php`
- `livewire/projects-table.blade.php`
- `livewire/vehicles-table.blade.php`
- `livewire/accommodations-table.blade.php`
- ... i inne Livewire tabele

---

### 8. **`<x-ui.pagination-wrapper />`** - ⭐⭐
**Częstotliwość:** Powtarza się w wielu tabelach

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
<x-ui.pagination-wrapper :items="$employees" />
```

**Pliki do refaktoryzacji:**
- `livewire/employees-table.blade.php`
- `livewire/projects-table.blade.php`
- `livewire/vehicles-table.blade.php`
- ... i inne tabele z paginacją

---

### 9. **`<x-ui.status-badge />`** - ⭐⭐
**Częstotliwość:** Powtarza się w wielu miejscach

**Problem:**
Powtarzający się kod mapowania statusów na badge'y:
```blade
@php
    $badgeVariant = match($status) {
        'active' => 'success',
        'completed' => 'info',
        'cancelled' => 'danger',
        default => 'info'
    };
@endphp
<x-ui.badge variant="{{ $badgeVariant }}">{{ ucfirst($status) }}</x-ui.badge>
```

**Rekomendacja:**
```blade
<x-ui.status-badge 
    :status="$project->status" 
    type="project"
/>
```

**Pliki do refaktoryzacji:**
- `projects/show.blade.php`
- `projects/index.blade.php`
- `livewire/projects-table.blade.php`
- `assignments/index.blade.php`
- ... i inne miejsca z statusami

---

### 10. **`<x-ui.date-range />`** - ⭐⭐
**Częstotliwość:** Powtarza się w wielu miejscach

**Problem:**
Powtarzający się kod wyświetlania zakresu dat:
```blade
<small class="text-muted">
    {{ $assignment->start_date->format('Y-m-d') }}
    @if($assignment->end_date)
        - {{ $assignment->end_date->format('Y-m-d') }}
    @else
        - ...
    @endif
</small>
```

**Rekomendacja:**
```blade
<x-ui.date-range 
    :start-date="$assignment->start_date"
    :end-date="$assignment->end_date"
    format="Y-m-d"
    empty-text="..."
/>
```

**Pliki do refaktoryzacji:**
- `vehicles/show.blade.php`
- `accommodations/show.blade.php`
- `projects/show.blade.php`
- `assignments/index.blade.php`
- `vehicle-assignments/index.blade.php`
- ... i inne miejsca z zakresami dat

---

## 🎯 PRIORYTET 3: Komponenty o Niskim Priorytecie

### 11. **`<x-ui.tabs />`** - ⭐
**Częstotliwość:** 2 pliki (`employees/show.blade.php`, `ui-concept/utest.blade.php`)

**Problem:**
Powtarzający się kod zakładek Bootstrap:
```blade
<ul class="nav nav-tabs mb-4" id="employeeTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button" role="tab">
            Informacje
        </button>
    </li>
    ...
</ul>
<div class="tab-content" id="employeeTabsContent">
    <div class="tab-pane fade show active" id="info" role="tabpanel">
        ...
    </div>
</div>
```

**Rekomendacja:**
```blade
<x-ui.tabs id="employeeTabs">
    <x-ui.tab id="info" label="Informacje" :active="true">
        ...
    </x-ui.tab>
    <x-ui.tab id="documents" label="Dokumenty">
        ...
    </x-ui.tab>
</x-ui.tabs>
```

---

### 12. **`<x-ui.image-display />`** - ⭐
**Częstotliwość:** Powtarza się w widokach `show.blade.php`

**Problem:**
Powtarzający się kod wyświetlania obrazów:
```blade
@if($vehicle->image_path)
    <div class="mb-4 text-center">
        <img src="{{ $vehicle->image_url }}" alt="{{ $vehicle->registration_number }}" class="img-fluid rounded">
    </div>
@endif
```

**Rekomendacja:**
```blade
<x-ui.image-display 
    :image-url="$vehicle->image_url"
    :alt="$vehicle->registration_number"
    :centered="true"
    class="mb-4"
/>
```

---

## 📋 Plan Implementacji

### Faza 1: Komponenty o Najwyższym Priorytecie (1-2 tygodnie)
1. ✅ `<x-ui.empty-state />`
2. ✅ `<x-ui.table-header />`
3. ✅ `<x-ui.action-buttons />`
4. ✅ `<x-ui.detail-list />`
5. ✅ `<x-ui.delete-form />`

### Faza 2: Komponenty o Średnim Priorytecie (2-3 tygodnie)
6. ✅ `<x-ui.sortable-header />`
7. ✅ `<x-ui.filter-card />`
8. ✅ `<x-ui.pagination-wrapper />`
9. ✅ `<x-ui.status-badge />`
10. ✅ `<x-ui.date-range />`

### Faza 3: Komponenty o Niskim Priorytecie (opcjonalnie)
11. ✅ `<x-ui.tabs />`
12. ✅ `<x-ui.image-display />`

---

## 💡 Dodatkowe Rekomendacje

### 1. **Częściowe widoki dla formularzy**
Rozważyć utworzenie `_form.blade.php` dla formularzy create/edit:
- `vehicles/_form.blade.php`
- `accommodations/_form.blade.php`
- `employees/_form.blade.php`
- `projects/_form.blade.php`

### 2. **Komponenty dla Livewire tabel**
Utworzyć bazowy komponent dla Livewire tabel z:
- Filtrami
- Sortowaniem
- Paginacją
- Empty state

### 3. **Unifikacja stylów empty state**
Upewnić się, że wszystkie empty states mają spójny wygląd.

---

## 📊 Oszacowany Wpływ

Po implementacji wszystkich komponentów:
- **Redukcja duplikacji kodu:** ~40-50%
- **Łatwiejsza konserwacja:** Zmiany w jednym miejscu
- **Spójność UI:** Wszystkie elementy wyglądają tak samo
- **Szybszy development:** Mniej kodu do pisania

---

**Data utworzenia:** {{ date('Y-m-d') }}
**Wersja:** 1.0
