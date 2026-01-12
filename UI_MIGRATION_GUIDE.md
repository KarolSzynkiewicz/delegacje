# Przewodnik migracji do systemu UI

## Zasady nadrzędne

1. **ZERO HTML buttonów, inputów, badge'ów w widokach biznesowych**
   - ❌ `<button class="btn btn-primary">Zapisz</button>`
   - ✅ `<x-ui.button variant="primary">Zapisz</x-ui.button>`

2. **ZERO CSS w widokach**
   - ❌ `<style>` tagi
   - ❌ `style=""` inline
   - ❌ Klasy utility typu `mt-3 text-red` (tylko klasy layout Bootstrap jak `row`, `col-md-*`)

3. **Single source of truth: komponenty UI**
   - Wszystkie komponenty w `resources/views/components/ui/`
   - Jeśli potrzebujesz innego wariantu → dodaj do komponentu, nie CSS w widoku

## Wzorce migracji

### Przyciski

**Przed:**
```blade
<button type="submit" class="btn btn-primary">Zapisz</button>
<a href="{{ route('index') }}" class="btn btn-secondary">Anuluj</a>
```

**Po:**
```blade
<x-ui.button variant="primary" type="submit">Zapisz</x-ui.button>
<x-ui.button variant="ghost" href="{{ route('index') }}">Anuluj</x-ui.button>
```

### Inputy

**Przed:**
```blade
<label for="name" class="form-label">Nazwa *</label>
<input type="text" class="form-control @error('name') is-invalid @enderror" 
       id="name" name="name" value="{{ old('name') }}" required>
@error('name') <span class="invalid-feedback">{{ $message }}</span> @enderror
```

**Po:**
```blade
<x-ui.input 
    type="text" 
    name="name" 
    label="Nazwa"
    value="{{ old('name') }}"
    required="true"
/>
```

### Select

**Przed:**
```blade
<select name="status" class="form-select @error('status') is-invalid @enderror" required>
    <option value="">Wybierz</option>
    <option value="active">Aktywny</option>
</select>
@error('status') <span class="invalid-feedback">{{ $message }}</span> @enderror
```

**Po:**
```blade
<x-ui.input type="select" name="status" label="Status" required="true">
    <option value="">Wybierz</option>
    <option value="active">Aktywny</option>
</x-ui.input>
```

### Textarea

**Przed:**
```blade
<textarea class="form-control @error('notes') is-invalid @enderror" 
          name="notes" rows="4">{{ old('notes') }}</textarea>
@error('notes') <span class="invalid-feedback">{{ $message }}</span> @enderror
```

**Po:**
```blade
<x-ui.input type="textarea" name="notes" label="Notatki" rows="4" value="{{ old('notes') }}" />
```

### Badge'y

**Przed:**
```blade
<span class="badge bg-success">Aktywny</span>
<span class="badge bg-danger">Wygasł</span>
```

**Po:**
```blade
<x-ui.badge variant="success">Aktywny</x-ui.badge>
<x-ui.badge variant="danger">Wygasł</x-ui.badge>
```

### Usuwanie style tagów

**Przed:**
```blade
<div style="max-width: 300px;">...</div>
<style>
    .custom-class { ... }
</style>
```

**Po:**
```blade
<div>...</div>
<!-- Style przeniesione do UI systemu -->
```

## Status migracji

### ✅ Zakończone

- ✅ Komponenty UI przeniesione z `ui-concept` do `ui`
- ✅ `utest.blade.php` - zaktualizowany, usunięte inline styles
- ✅ **Employees** - `index`, `create` zmigrowane
- ✅ **Projects** - `index`, `create`, `edit`, `show` zmigrowane
- ✅ **Vehicles** - `index`, `create`, `edit`, `show` zmigrowane
- ✅ **Accommodations** - `index`, `create`, `edit`, `show` zmigrowane
- ✅ **Assignments** - `index` zmigrowany (badge'y, buttony)
- ✅ `action-buttons.blade.php` - zmigrowany na x-ui.button
- ✅ `dashboard.blade.php` - usunięte style tagi
- ✅ Komponent `input` - rozszerzony o obsługę błędów walidacji

### ✅ Layouty

- ✅ `layouts/app.blade.php` - usunięte style tagi i inline styles, style przeniesione do `app.css`
- ✅ `layouts/navigation.blade.php` - usunięte style tagi i inline styles
- ✅ `layouts/guest.blade.php` - już czysty (bez style tagów)
- ✅ `resources/css/app.css` - dodane wszystkie style z layoutów

### ⏳ W trakcie / Do zrobienia

- ⏳ **Assignments** - `create`, `edit`, `show` (częściowo zmigrowane)
- ⏳ **Demands** - wszystkie widoki
- ⏳ **Documents** - wszystkie widoki
- ⏳ **Rotations** - wszystkie widoki
- ⏳ **Livewire components** - tabele i formularze
- ⏳ **Auth views** - login, register
- ⏳ **Pozostałe widoki** - vehicle-assignments, accommodation-assignments, etc.
- ⏳ Usunięcie starych komponentów (delete-button, edit-button, view-button)

## Komponenty UI dostępne

- `<x-ui.button>` - variant: primary, ghost, danger
- `<x-ui.input>` - type: text, email, number, date, select, textarea, file
- `<x-ui.badge>` - variant: success, danger, warning, info, accent
- `<x-ui.card>` - variant: default, hover, elevated
- `<x-ui.progress>` - variant: default, success, danger, warning
- `<x-ui.navbar>` - brand, brandUrl
