# Stocznia - System Zarządzania Logistyką i Delegowaniem Pracowników

**Stocznia** to aplikacja webowa stworzona w oparciu o framework **Laravel**, zaprojektowana do zarządzania kluczowymi aspektami logistyki i zasobów ludzkich w firmie delegującej pracowników do projektów stoczniowych.

## 🚀 Funkcjonalności

Aplikacja oferuje następujące moduły:

| Moduł | Opis | Kluczowe Dane |
| :--- | :--- | :--- |
| **Autentykacja** | Logowanie, rejestracja, resetowanie hasła (Laravel Breeze). | Użytkownicy, hasła. |
| **Pracownicy** | Zarządzanie personelem delegowanym z rotacjami, dokumentami i rolami. | Imię, Nazwisko, Kontakt, Rola (Spawacz/Dekarz/Elektryk), Dokumenty, Rotacje. |
| **Rotacje** | Definiowanie dostępności pracowników w określonych okresach. | Pracownik, Data rozpoczęcia, Data zakończenia, Status (automatyczny). |
| **Dokumenty** | Zarządzanie dokumentami pracowników (okresowe i bezokresowe). | Typ dokumentu, Data ważności, Status. |
| **Akomodacje** | Zarządzanie dostępnymi mieszkaniami. | Nazwa, Adres, Pojemność (liczba osób). |
| **Pojazdy** | Zarządzanie flotą pojazdów. | Numer Rejestracyjny, Marka, Model, Pojemność, Stan Techniczny, Przegląd Ważny Do. |
| **Lokalizacje** | Zarządzanie miejscami pracy (stoczniami). | Nazwa, Adres. |
| **Projekty** | Tworzenie i zarządzanie projektami. | Nazwa, Opis, Lokalizacja. |
| **Zapotrzebowanie** | Definiowanie zapotrzebowania na role w projektach w określonych okresach. | Projekt, Rola, Ilość, Okres (od-do). |
| **Przypisania** | Przypisywanie pracowników do projektów z walidacją dostępności. | Pracownik, Projekt, Rola, Daty, Status. |
| **Planer Tygodniowy** | Wizualny przegląd projektów, zapotrzebowania i przypisań w ujęciu tygodniowym. | Tygodniowy widok wszystkich projektów z podsumowaniem. |
| **Zapisy Czasu Pracy** | Rejestrowanie czasu pracy. | Pracownik, Data, Godziny. |
| **Raporty** | Generowanie raportów z delegacji (w rozwoju). | Typy raportów, eksport PDF/Excel. |

---

## 📋 Proces Przypisywania Pracownika - Perspektywa End Usera

### 1. Przygotowanie Pracownika

#### 1.1. Dodanie Pracownika
- Przejdź do **Pracownicy** → **Dodaj Pracownika**
- Wypełnij podstawowe dane: imię, nazwisko, email, telefon
- Przypisz role (np. Spawacz, Dekarz, Elektryk) - pracownik może mieć wiele ról

#### 1.2. Definiowanie Rotacji (Dostępności)
- Przejdź do **Rotacje Pracowników** lub **Pracownicy** → [Pracownik] → **Rotacje**
- Kliknij **Dodaj Rotację**
- Ustaw datę rozpoczęcia i zakończenia - okres, w którym pracownik jest dostępny do pracy
- Status jest automatycznie obliczany na podstawie dat:
  - **Zaplanowana** - jeśli data rozpoczęcia jest w przyszłości
  - **Aktywna** - jeśli okres obejmuje dzisiejszą datę
  - **Zakończona** - jeśli data zakończenia jest w przeszłości
  - **Anulowana** - tylko ręcznie (można anulować rotację)

#### 1.3. Dodanie Dokumentów
- Przejdź do **Pracownicy** → [Pracownik] → **Dokumenty**
- Kliknij **Dodaj Dokument**
- Wybierz typ dokumentu (np. Uprawnienia A1, Prawo jazdy)
- Ustaw datę ważności:
  - **Okresowy** - dokument z datą ważności (valid_from, valid_to)
  - **Bezokresowy** - dokument bez daty wygaśnięcia (tylko valid_from)
- System automatycznie sprawdza ważność dokumentów przy przypisywaniu do projektów

### 2. Tworzenie Projektu i Zapotrzebowania

#### 2.1. Utworzenie Projektu
- Przejdź do **Projekty** → **Dodaj Projekt**
- Wypełnij: nazwa, opis, lokalizacja, klient
- Zapisz projekt

#### 2.2. Definiowanie Zapotrzebowania
- Przejdź do **Projekty** → [Projekt] → **Zapotrzebowanie** → **Dodaj Zapotrzebowanie**
- Ustaw okres: data od i data do (może być otwarty - bez daty zakończenia)
- Dla każdej roli określ ilość potrzebnych pracowników:
  - Np. 5 Spawaczy, 3 Dekarzy, 2 Elektryków
- System zapisze zapotrzebowanie dla każdej roli osobno

**Alternatywnie z Planera Tygodniowego:**
- Przejdź do **Planer Tygodniowy**
- Wybierz tydzień
- W kafle projektu kliknij **Edytuj** w sekcji "Zapotrzebowanie"
- Ustaw zapotrzebowanie dla wybranego tygodnia

### 3. Przypisywanie Pracownika do Projektu

#### 3.1. Z Widoku Projektu
- Przejdź do **Projekty** → [Projekt] → **Przypisania** → **Dodaj Przypisanie**
- Wybierz pracownika z listy
- Wybierz rolę (tylko role, które pracownik posiada)
- Ustaw datę rozpoczęcia i zakończenia przypisania
- Wybierz status (Oczekujące/Aktywne/Zakończone/Anulowane)

**System automatycznie sprawdza:**
- ✅ Czy pracownik ma aktywną rotację pokrywającą **cały okres** przypisania
- ✅ Czy pracownik ma wszystkie wymagane dokumenty ważne w tym okresie
- ✅ Czy pracownik nie jest już przypisany do innego projektu w tym samym czasie
- ✅ Czy istnieje zapotrzebowanie dla danej roli w tym okresie

Jeśli którykolwiek warunek nie jest spełniony, przypisanie zostanie zablokowane z odpowiednim komunikatem błędu.

#### 3.2. Z Planera Tygodniowego
- Przejdź do **Planer Tygodniowy**
- Wybierz tydzień
- W kafle projektu kliknij **Dodaj** w sekcji "Osoby w projekcie"
- System automatycznie wypełni daty z wybranego tygodnia
- Wybierz pracownika i rolę
- System pokazuje dostępnych pracowników (niedostępni są wyszarzeni z opisem przyczyny)

### 4. Przypisywanie Zasobów (Pojazdy, Mieszkania)

#### 4.1. Przypisanie Pojazdu
- Z widoku pracownika: **Pracownicy** → [Pracownik] → **Pojazdy** → **Przypisz Auto**
- Z planera tygodniowego: W sekcji "Auta w projekcie" kliknij **Auto** przy pracowniku bez pojazdu
- Wybierz pojazd z listy dostępnych
- Ustaw daty przypisania (domyślnie wypełnione z planera)
- System sprawdza dostępność pojazdu w danym okresie

#### 4.2. Przypisanie Mieszkania
- Z widoku pracownika: **Pracownicy** → [Pracownik] → **Mieszkania** → **Przypisz Dom**
- Z planera tygodniowego: W sekcji "Domy w projekcie" kliknij **Dom** przy pracowniku bez mieszkania
- Wybierz mieszkanie z listy dostępnych
- System sprawdza pojemność mieszkania (czy nie przekroczono limitu osób)

### 5. Planer Tygodniowy - Przegląd i Zarządzanie

#### 5.1. Nawigacja
- Przejdź do **Planer Tygodniowy**
- Użyj przycisków **Poprzedni Tydzień** / **Następny Tydzień** do nawigacji
- Widok pokazuje jeden tydzień na raz

#### 5.2. Widok Projektu w Planerze
Każdy projekt wyświetla się jako kafelek z następującymi sekcjami:

**Zapotrzebowanie:**
- Tabela z rolami, ilością potrzebnych i przypisanych pracowników
- Wskaźnik braków (które role i ile osób brakuje)
- Przycisk **Edytuj** do modyfikacji zapotrzebowania

**Osoby w projekcie:**
- Lista przypisanych pracowników z ich rolami
- Zdjęcia pracowników (lub inicjały)
- Przycisk **Dodaj** do przypisania nowych pracowników

**Auta w projekcie:**
- Lista przypisanych pojazdów z kierowcami
- Sekcja "Bez auta" - lista pracowników bez pojazdu z przyciskami do przypisania
- Status "Wszyscy mają przypisane auto" gdy wszystkie osoby mają pojazdy

**Domy w projekcie:**
- Lista przypisanych mieszkań z informacją o wykorzystaniu pojemności
- Sekcja "Bez domu" - lista pracowników bez mieszkania z przyciskami do przypisania
- Status "Wszyscy mają przypisany dom" gdy wszystkie osoby mają mieszkania

---

## 🏗️ Struktura Projektu - Sekcja dla Developera

### Architektura Aplikacji

```
delegacje/
├── app/
│   ├── Http/
│   │   └── Controllers/          # Kontrolery obsługujące requesty HTTP
│   │       ├── ProjectAssignmentController.php    # Logika przypisań
│   │       ├── ProjectDemandController.php        # Logika zapotrzebowania
│   │       ├── RotationController.php             # Logika rotacji
│   │       ├── WeeklyOverviewController.php       # Planer tygodniowy
│   │       └── ...
│   ├── Models/                   # Modele Eloquent (ORM)
│   │   ├── Employee.php          # Główna logika dostępności pracownika
│   │   ├── Project.php           # Logika projektów i zapotrzebowania
│   │   ├── Rotation.php          # Logika rotacji (status automatyczny)
│   │   ├── ProjectAssignment.php # Przypisania pracownik-projekt-rola
│   │   ├── ProjectDemand.php     # Zapotrzebowanie na role w okresie
│   │   └── ...
│   ├── Rules/                    # Niestandardowe reguły walidacji
│   │   ├── EmployeeHasRole.php   # Sprawdza czy pracownik ma daną rolę
│   │   └── RotationDoesNotOverlap.php  # Sprawdza nakładanie rotacji
│   ├── Services/                 # Logika biznesowa (warstwa serwisowa)
│   │   ├── WeeklyOverviewService.php  # Agregacja danych dla planera
│   │   ├── EmployeeService.php
│   │   └── ProjectService.php
│   └── Livewire/                 # Komponenty Livewire (reaktywne UI)
│       ├── VehiclesTable.php     # Tabela pojazdów z filtrowaniem
│       └── ...
├── database/
│   ├── migrations/               # Migracje bazy danych
│   └── seeders/                  # Seedery (dane testowe)
├── resources/
│   ├── views/                    # Widoki Blade
│   │   ├── components/          # Komponenty Blade (reusable)
│   │   │   └── weekly-overview/ # Komponenty planera tygodniowego
│   │   ├── assignments/         # Widoki przypisań
│   │   ├── projects/            # Widoki projektów
│   │   └── ...
│   └── js/                       # JavaScript (Alpine.js)
└── routes/
    └── web.php                    # Definicje tras
```

### Gdzie Jest Trzymana Logika?

#### 1. **Logika Dostępności Pracownika** (`app/Models/Employee.php`)

**Kluczowe metody:**
- `hasActiveRotationInDateRange($startDate, $endDate)` - Sprawdza czy pracownik ma rotację pokrywającą cały okres
  - Sprawdza pojedynczą rotację lub ciąg rotacji bez przerw
  - Implementacja: `hasContinuousRotationsCoveringRange()`
- `hasAllDocumentsActiveInDateRange($startDate, $endDate)` - Sprawdza ważność dokumentów
  - Dla dokumentów okresowych: `valid_from <= startDate && valid_to >= endDate`
  - Dla bezokresowych: `valid_from <= endDate`
- `isAvailableInDateRange($startDate, $endDate)` - Główna metoda sprawdzająca dostępność
  - Sprawdza dokumenty, rotację i konfliktujące przypisania
- `getAvailabilityStatus($startDate, $endDate)` - Zwraca szczegółowy status z przyczynami
  - Zwraca: `['available' => bool, 'reasons' => []]`

**Lokalizacja:** `app/Models/Employee.php` (linie ~150-350)

#### 2. **Logika Rotacji** (`app/Models/Rotation.php`)

**Automatyczne obliczanie statusu:**
- `getStatusAttribute()` - Accessor obliczający status na podstawie dat
  - `scheduled` - jeśli `start_date > today`
  - `active` - jeśli `start_date <= today <= end_date`
  - `completed` - jeśli `end_date < today`
  - `cancelled` - tylko ręcznie (zapisane w bazie)

**Scopes:**
- `scopeActive()` - Filtruje aktywne rotacje (na podstawie dat)
- `scopeScheduled()` - Filtruje zaplanowane rotacje
- `scopeCompleted()` - Filtruje zakończone rotacje

**Lokalizacja:** `app/Models/Rotation.php`

#### 3. **Logika Walidacji Przypisań** (`app/Http/Controllers/ProjectAssignmentController.php`)

**Metoda `store()` i `update()`:**
1. Walidacja podstawowa (Form Request)
2. Sprawdzenie rotacji: `$employee->hasActiveRotationInDateRange()`
3. Sprawdzenie dostępności: `$employee->isAvailableInDateRange()`
4. Sprawdzenie zapotrzebowania: `$project->hasDemandForRoleInDateRange()`
5. Utworzenie przypisania

**Lokalizacja:** `app/Http/Controllers/ProjectAssignmentController.php` (linie ~74-114)

#### 4. **Logika Zapotrzebowania** (`app/Models/Project.php`)

**Metoda `hasDemandForRoleInDateRange($roleId, $startDate, $endDate)`:**
- Sprawdza czy istnieje `ProjectDemand` dla danej roli
- Sprawdza nakładanie się okresów (demand overlaps with assignment period)
- Uwzględnia demands bez daty zakończenia (`date_to = null`)

**Lokalizacja:** `app/Models/Project.php` (dodana metoda)

#### 5. **Logika Planera Tygodniowego** (`app/Services/WeeklyOverviewService.php`)

**Główne metody:**
- `getWeeks()` - Generuje dane tygodnia (jeden tydzień)
- `getProjectsWithWeeklyData()` - Agreguje dane dla wszystkich projektów
- `getProjectWeekData()` - Agreguje dane dla jednego projektu w tygodniu
  - Pobiera zapotrzebowanie (`getDemandsForWeek()`)
  - Pobiera przypisania (`getAssignmentsForWeek()`)
  - Oblicza podsumowanie (`calculateRequirementsSummary()`)
  - Pobiera pojazdy i mieszkania (`getVehiclesForWeek()`, `getAccommodationsForWeek()`)
  - Pobiera szczegóły pracowników (`getAssignedEmployeesDetails()`)

**Lokalizacja:** `app/Services/WeeklyOverviewService.php`

#### 6. **Walidacja Nakładania Rotacji** (`app/Rules/RotationDoesNotOverlap.php`)

**Logika:**
- Sprawdza czy nowa rotacja nie nakłada się z istniejącymi
- Wyklucza rotacje anulowane (`status != 'cancelled'`)
- Sprawdza nakładanie się okresów (overlap detection)

**Lokalizacja:** `app/Rules/RotationDoesNotOverlap.php`

### Relacje Bazy Danych

```
Employee (Pracownik)
  ├── belongsToMany Role (role pracownika)
  ├── hasMany Rotation (rotacje dostępności)
  ├── hasMany EmployeeDocument (dokumenty pracownika)
  ├── hasMany ProjectAssignment (przypisania do projektów)
  ├── hasMany VehicleAssignment (przypisania pojazdów)
  └── hasMany AccommodationAssignment (przypisania mieszkań)

Project (Projekt)
  ├── hasMany ProjectDemand (zapotrzebowanie na role)
  ├── hasMany ProjectAssignment (przypisania pracowników)
  └── belongsTo Location (lokalizacja)

ProjectDemand (Zapotrzebowanie)
  ├── belongsTo Project
  └── belongsTo Role (wymagana rola)

ProjectAssignment (Przypisanie)
  ├── belongsTo Project
  ├── belongsTo Employee
  └── belongsTo Role

Rotation (Rotacja)
  └── belongsTo Employee

VehicleAssignment (Przypisanie Pojazdu)
  ├── belongsTo Employee
  └── belongsTo Vehicle

AccommodationAssignment (Przypisanie Mieszkania)
  ├── belongsTo Employee
  └── belongsTo Accommodation
```

### Kluczowe Zależności i Technologie

- **Laravel 11** - Framework PHP
- **Laravel Breeze** - Autentykacja
- **Livewire 3** - Reaktywne komponenty UI
- **Alpine.js** - Lekki JavaScript framework
- **Tailwind CSS** - Framework CSS
- **MySQL** - Baza danych
- **Laravel Sail** - Docker development environment
- **Laravel Boost** - AI-assisted development tools

---

## 🛠️ Wymagania

### Dla Docker (Zalecane)
*   Docker Desktop (Windows/Mac) lub Docker Engine (Linux)
*   Docker Compose

### Dla Lokalnego Uruchomienia
*   PHP >= 8.1
*   Composer
*   Node.js & npm
*   MySQL lub SQLite

---

## 🐳 Uruchomienie z Docker (Zalecane)

**Laravel Sail** zapewnia proste i spójne środowisko Docker dla aplikacji Laravel.

### Szybki Start

1. **Sklonuj repozytorium:**
   ```bash
   git clone https://github.com/KarolSzynkiewicz/delegacje.git
   cd delegacje
   ```

2. **Skopiuj plik środowiskowy:**
   ```bash
   cp .env.example .env
   ```

3. **Uruchom kontenery Docker:**
   ```bash
   ./sail up -d
   ```
   
   Lub jeśli `sail` nie działa:
   ```bash
   ./vendor/bin/sail up -d
   ```

4. **Zainstaluj zależności (tylko przy pierwszym uruchomieniu):**
   ```bash
   ./sail composer install
   ./sail npm install
   ./sail npm run build
   ```

5. **Wygeneruj klucz aplikacji:**
   ```bash
   ./sail artisan key:generate
   ```

6. **Uruchom migracje i seedery:**
   ```bash
   ./sail artisan migrate --seed
   ```

7. **Otwórz aplikację w przeglądarce:**
   ```
   http://localhost
   ```

### Przydatne Komendy Sail

```bash
./sail up -d              # Uruchom kontenery w tle
./sail down               # Zatrzymaj kontenery
./sail artisan ...        # Uruchom komendy Artisan
./sail composer ...       # Uruchom komendy Composer
./sail npm ...            # Uruchom komendy NPM
./sail mysql              # Dostęp do MySQL CLI
./sail shell              # Dostęp do bash w kontenerze
./sail logs               # Zobacz logi kontenerów
```

**📖 Pełna dokumentacja Docker:** Zobacz [DOCKER_SETUP.md](DOCKER_SETUP.md)

---

## 💻 Uruchomienie Lokalne (Bez Docker)

### 1. Instalacja

1. **Sklonuj repozytorium:**
   ```bash
   git clone https://github.com/KarolSzynkiewicz/delegacje.git
   cd delegacje
   ```

2. **Zainstaluj zależności PHP:**
   ```bash
   composer install
   ```

3. **Skonfiguruj środowisko:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Skonfiguruj bazę danych w `.env`:**
   
   **Dla SQLite (prostsze):**
   ```env
   DB_CONNECTION=sqlite
   ```
   Następnie utwórz plik bazy:
   ```bash
   touch database/database.sqlite
   ```

   **Dla MySQL:**
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=laravel
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. **Zainstaluj zależności front-end:**
   ```bash
   npm install
   npm run build
   ```

6. **Uruchom migracje i seedery:**
   ```bash
   php artisan migrate --seed
   ```

7. **Uruchom serwer deweloperski:**
   ```bash
   php artisan serve
   ```

8. **Aplikacja dostępna pod adresem:**
   ```
   http://127.0.0.1:8000
   ```

---

## 🔑 Dane Logowania (Testowe)

Po uruchomieniu migracji z seederami (`migrate --seed`), możesz zalogować się:

| Pole | Wartość |
| :--- | :--- |
| **Email** | `test@example.com` |
| **Hasło** | `password123` |

---

## 📊 Moduł Raportów (Feature Branch)

Gałąź `feature/raporty` zawiera nowy moduł raportowania, który jest obecnie w fazie rozwoju.

**Planowane funkcjonalności:**
- Podsumowanie delegacji
- Godziny pracy pracowników
- Status projektów
- Eksport do PDF/Excel

**Więcej informacji:** Zobacz [REPORTS_FEATURE.md](REPORTS_FEATURE.md)

---

## 🧪 Testowanie

```bash
# Z Docker
./sail artisan test

# Lokalnie
php artisan test
```

---

## 📝 Struktura Projektu

```
delegacje/
├── app/                    # Logika aplikacji (Controllers, Models, Services, Rules)
├── database/               # Migracje, seedery, factory
├── resources/              # Widoki Blade, CSS, JS
├── routes/                 # Definicje tras
├── public/                 # Publiczne pliki (index.php, assets)
├── vendor/                 # Zależności Composer
├── docker-compose.yml      # Konfiguracja Docker Sail
├── .env.example            # Przykładowy plik środowiskowy
├── sail                    # Skrypt pomocniczy Sail
└── README.md               # Ten plik
```

---

## 🤝 Wkład w Projekt

1. Fork projektu
2. Utwórz branch dla nowej funkcjonalności (`git checkout -b feature/AmazingFeature`)
3. Commit zmian (`git commit -m 'Add some AmazingFeature'`)
4. Push do brancha (`git push origin feature/AmazingFeature`)
5. Otwórz Pull Request

--

## 🆘 Wsparcie

Jeśli napotkasz problemy:
1. Sprawdź [DOCKER_SETUP.md](DOCKER_SETUP.md) dla problemów z Docker
2. Sprawdź [REPORTS_FEATURE.md](REPORTS_FEATURE.md) dla informacji o module raportów
3. Otwórz Issue na GitHub

---

**Rekomendowane:** Użyj Docker z Laravel Sail dla najlepszego doświadczenia deweloperskiego! 🚢
