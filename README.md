# Stocznia - System Zarządzania Logistyką i Delegowaniem Pracowników

**Stocznia** to aplikacja webowa stworzona w oparciu o framework **Laravel**, zaprojektowana do zarządzania kluczowymi aspektami logistyki i zasobów ludzkich w firmie delegującej pracowników.

## 🔐 System Autoryzacji

Aplikacja wykorzystuje **dynamiczny system autoryzacji** oparty na **Spatie Laravel Permission**. Zamiast tradycyjnych Policy, system używa middleware do automatycznego sprawdzania uprawnień na podstawie route i metody HTTP.

**Kluczowe cechy:**
- ✅ Dynamiczne sprawdzanie uprawnień przez middleware
- ✅ Zarządzanie uprawnieniami przez UI (tabelka w edycji roli)
- ✅ Cache uprawnień (24h) dla wydajności
- ✅ Cache mapowań route → permission (1h)
- ✅ Cache menu per user (1h)
- ✅ Centralizacja logiki w `RoutePermissionService`
- ✅ Administratorzy mają pełny dostęp
- ✅ Brak potrzeby pisania Policy dla każdego modelu
- ✅ Route są jedynym źródłem prawdy dla uprawnień

**Szczegółowa dokumentacja:** Zobacz [authorization.readme.md](authorization.readme.md) dla pełnego opisu działania systemu autoryzacji.

---

## 🚀 Funkcjonalności

Aplikacja oferuje następujące moduły:

| Moduł | Opis | Kluczowe Dane |
| :--- | :--- | :--- |
| **Autentykacja** | Logowanie, rejestracja, resetowanie hasła (Laravel Breeze). | Użytkownicy, hasła. |
| **Pracownicy** | Zarządzanie personelem delegowanym. | Imię, Nazwisko, Kontakt, Rola (Spawacz/Dekarz), Dokumenty. |
| **Rotacje** | Definiowanie okresów dostępności pracowników. | Data rozpoczęcia, Data zakończenia, Status (automatyczny). |
| **Akomodacje** | Zarządzanie dostępnymi mieszkaniami. | Nazwa, Adres, Pojemność (liczba osób). |
| **Pojazdy** | Zarządzanie flotą pojazdów. | Numer Rejestracyjny, Marka, Model, Stan Techniczny, Przegląd. |
| **Lokalizacje** | Zarządzanie miejscami pracy (stoczniami). | Nazwa, Adres, Dane kontaktowe. |
| **Projekty** | Tworzenie i zarządzanie projektami. | Nazwa, Opis, Zapotrzebowanie na role. |
| **Przypisania** | Przypisywanie pracowników do projektów z walidacją dostępności. | Pracownik, Projekt, Rola, Daty, Status. |
| **Widok Tygodniowy** | Tygodniowy przegląd wszystkich projektów, pracowników i zasobów. | Projekty, Pracownicy, Pojazdy, Mieszkania, Zapotrzebowanie. |
| **Raporty** | Generowanie raportów z delegacji (w rozwoju). | Typy raportów, eksport PDF/Excel. |

---

## 👤 Instrukcje dla Użytkownika

### Logowanie

1. Otwórz aplikację w przeglądarce (domyślnie: `http://localhost`)
2. Kliknij **"Logowanie"** w prawym górnym rogu
3. Wprowadź dane logowania:
   - **Email:** `test@example.com`
   - **Hasło:** `password123`
4. Kliknij **"Zaloguj się"**

### Dashboard

Po zalogowaniu zobaczysz **Dashboard** z dostępem do wszystkich modułów systemu:

- **Przegląd Tygodniowy** - główny widok do zarządzania tygodniowymi przydziałami
- **Projekty** - zarządzanie projektami i zapotrzebowaniem
- **Pracownicy** - baza pracowników
- **Rotacje Pracowników** - zarządzanie dostępnością pracowników
- **Pojazdy** - flota pojazdów
- **Mieszkania** - akomodacje
- I inne...

---

## 📋 Podstawowy Workflow - Jak Przypisać Pracownika do Projektu

### Krok 1: Utwórz Projekt

1. Z Dashboard kliknij **"Projekty"**
2. Kliknij **"Dodaj Projekt"** (przycisk w prawym górnym rogu)
3. Wypełnij formularz:
   - **Nazwa projektu** (np. "Remont Stoczni Gdańskiej")
   - **Opis** (opcjonalnie)
4. Kliknij **"Zapisz"**

### Krok 2: Zdefiniuj Zapotrzebowanie na Role

1. W widoku projektu kliknij **"Zapotrzebowanie"** lub **"Dodaj Zapotrzebowanie"**
2. Wypełnij formularz:
   - **Data od** i **Data do** (okres zapotrzebowania)
   - Dla każdej roli podaj **Ilość potrzebnych osób** (np. 2 spawaczy, 1 dekarza)
   - **Uwagi** (opcjonalnie)
3. Kliknij **"Zapisz"**

### Krok 3: Dodaj Rotację dla Pracownika

**Rotacja określa okres, w którym pracownik jest dostępny do pracy.**

1. Z Dashboard kliknij **"Rotacje Pracowników"**
2. Kliknij **"Dodaj Rotację"**
3. Wybierz **Pracownika** z listy
4. Wprowadź:
   - **Data rozpoczęcia** (od kiedy pracownik jest dostępny)
   - **Data zakończenia** (do kiedy pracownik jest dostępny)
   - **Uwagi** (opcjonalnie)
5. Kliknij **"Zapisz"**
   - Status rotacji jest automatyczny: **Zaplanowana** (przyszłość), **Aktywna** (obecnie), **Zakończona** (przeszłość)
   - Możesz ręcznie ustawić status **Anulowana**

**Alternatywnie:** Możesz dodać rotację bezpośrednio z profilu pracownika:
1. Kliknij **"Pracownicy"** → wybierz pracownika
2. Przejdź do zakładki **"Rotacje"**
3. Kliknij **"Dodaj Rotację"**

### Krok 4: Dodaj Dokumenty Pracownika

**System sprawdza ważność dokumentów przed przypisaniem do projektu.**

1. Z Dashboard kliknij **"Pracownicy"**
2. Wybierz pracownika
3. Przejdź do zakładki **"Dokumenty"**
4. Kliknij **"Dodaj Dokument"**
5. Wybierz **Typ dokumentu** (np. "Uprawnienia spawacza")
6. Wypełnij:
   - **Rodzaj:** Okresowy lub Bezokresowy
   - **Data ważności od** (i **Data ważności do** dla okresowych)
7. Kliknij **"Zapisz"**

### Krok 5: Przypisz Pracownika do Projektu

1. Z Dashboard kliknij **"Projekty"** → wybierz projekt
2. Kliknij **"Przypisania"** lub **"Dodaj Przypisanie"**
3. Wypełnij formularz:
   - **Pracownik** - wybierz z listy (niedostępni są wyszarzeni z powodem)
   - **Rola w Projekcie** - musi być zgodna z rolami pracownika
   - **Data rozpoczęcia** i **Data zakończenia**
   - **Status** (domyślnie: Aktywne)
4. Kliknij **"Zapisz"**

**System automatycznie sprawdza:**
- ✅ Czy pracownik ma rotację pokrywającą cały okres przypisania
- ✅ Czy pracownik ma wszystkie wymagane dokumenty ważne w tym okresie
- ✅ Czy pracownik nie jest już przypisany do innego projektu w tym czasie
- ✅ Czy projekt ma zapotrzebowanie na tę rolę w danym okresie

Jeśli któryś warunek nie jest spełniony, zobaczysz komunikat błędu z dokładnym powodem.

### Krok 6: Przypisz Pojazd i Mieszkanie (Opcjonalnie)

**Z widoku tygodniowego:**

1. Z Dashboard kliknij **"Przegląd Tygodniowy"**
2. Wybierz tydzień (użyj przycisków "Poprzedni Tydzień" / "Następny Tydzień")
3. W karcie projektu znajdź sekcję **"Auta w projekcie"** lub **"Domy w projekcie"**
4. Dla pracowników bez auta/mieszkania kliknij przycisk **"Auto"** lub **"Dom"**
5. Wybierz pojazd/mieszkanie i daty
6. Kliknij **"Zapisz"**

**Alternatywnie z profilu pracownika:**

1. Kliknij **"Pracownicy"** → wybierz pracownika
2. Przejdź do zakładki **"Pojazdy"** lub **"Mieszkania"**
3. Kliknij **"Dodaj Przypisanie"**

---

## 📅 Przegląd Tygodniowy - Główny Widok Zarządzania

**Przegląd Tygodniowy** to najważniejszy widok do zarządzania przydziałami:

### Jak używać:

1. Z Dashboard kliknij **"Przegląd Tygodniowy"**
2. Użyj przycisków **"Poprzedni Tydzień"** / **"Następny Tydzień"** do nawigacji
3. Dla każdego projektu zobaczysz:
   - **Zapotrzebowanie** - tabela z rolami, ilością potrzebnych i przypisanych osób
   - **Osoby w projekcie** - lista przypisanych pracowników z rolami
   - **Auta w projekcie** - przypisane pojazdy i pracownicy bez auta
   - **Domy w projekcie** - przypisane mieszkania i pracownicy bez domu

### Szybkie akcje:

- **Edytuj zapotrzebowanie** - kliknij przycisk "Edytuj" w sekcji zapotrzebowania
- **Dodaj pracownika** - kliknij "Dodaj" w sekcji osób
- **Przypisz auto/dom** - kliknij przycisk "Auto" lub "Dom" przy pracowniku bez przypisania

---

## 🔍 Filtrowanie i Wyszukiwanie

### Rotacje Pracowników

1. Kliknij **"Rotacje Pracowników"**
2. Użyj filtrów:
   - **Pracownik** - wybierz konkretnego pracownika
   - **Status** - Zaplanowana, Aktywna, Zakończona, Anulowana
   - **Data rozpoczęcia** - zakres dat
   - **Data zakończenia** - zakres dat
3. Kliknij **"Filtruj"** lub **"Wyczyść filtry"**

### Pracownicy

1. Kliknij **"Pracownicy"**
2. Użyj pola wyszukiwania do filtrowania po imieniu, nazwisku lub emailu
3. Sortuj klikając nagłówki kolumn

### Pojazdy i Mieszkania

- Podobnie jak pracownicy - użyj wyszukiwania i sortowania

---

## ⚠️ Ważne Informacje

### Walidacja Przypisań

System **automatycznie blokuje** przypisania, jeśli:
- Pracownik nie ma rotacji pokrywającej cały okres przypisania
- Pracownik nie ma wszystkich wymaganych dokumentów ważnych w tym okresie
- Pracownik jest już przypisany do innego projektu w tym czasie
- Projekt nie ma zapotrzebowania na daną rolę w tym okresie

### Statusy Rotacji

- **Zaplanowana** - rotacja zaczyna się w przyszłości
- **Aktywna** - rotacja trwa obecnie
- **Zakończona** - rotacja już się zakończyła
- **Anulowana** - rotacja została ręcznie anulowana

Status jest **automatycznie obliczany** na podstawie dat - nie musisz go ustawiać ręcznie (oprócz "Anulowana").

### Dokumenty

- **Okresowe** - mają datę ważności od-do
- **Bezokresowe** - ważne od daty wydania bez końca

System sprawdza ważność dokumentów przed przypisaniem pracownika do projektu.

---

## 🛠️ Wymagania Techniczne

### Dla Docker (Zalecane)
*   Docker Desktop (Windows/Mac) lub Docker Engine (Linux)
*   Docker Compose

### Dla Lokalnego Uruchomienia
*   PHP >= 8.1
*   Composer
*   Node.js & npm
*   MySQL lub SQLite

---

## 🐳 Instalacja i Uruchomienie

### Szybki Start z Docker (Zalecane)

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
   ./vendor/bin/sail up -d
   ```

4. **Zainstaluj zależności (tylko przy pierwszym uruchomieniu):**
   ```bash
   ./vendor/bin/sail composer install
   ./vendor/bin/sail npm install
   ./vendor/bin/sail npm run build
   ```

5. **Wygeneruj klucz aplikacji:**
   ```bash
   ./vendor/bin/sail artisan key:generate
   ```

6. **Uruchom migracje i seedery:**
   ```bash
   ./vendor/bin/sail artisan migrate --seed
   ```

7. **Otwórz aplikację w przeglądarce:**
   ```
   http://localhost
   ```

### Przydatne Komendy Sail

```bash
./vendor/bin/sail up -d              # Uruchom kontenery w tle
./vendor/bin/sail down               # Zatrzymaj kontenery
./vendor/bin/sail artisan ...        # Uruchom komendy Artisan
./vendor/bin/sail composer ...       # Uruchom komendy Composer
./vendor/bin/sail npm ...            # Uruchom komendy NPM
./vendor/bin/sail mysql              # Dostęp do MySQL CLI
./vendor/bin/sail shell              # Dostęp do bash w kontenerze
./vendor/bin/sail logs               # Zobacz logi kontenerów
```

### Naprawa Uprawnień Cache (Sail)

Jeśli wystąpi problem z cache (błąd `file_put_contents: Failed to open stream`):

```bash
./fix-cache-permissions.sh
```

Lub ręcznie:
```bash
./vendor/bin/sail exec laravel.test bash -c "mkdir -p /var/www/html/storage/framework/cache/data && chown -R sail:sail /var/www/html/storage/framework/cache && chmod -R 775 /var/www/html/storage/framework/cache"
```

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

## 📊 Moduł Raportów

Moduł raportowania jest w fazie rozwoju.

**Planowane funkcjonalności:**
- Podsumowanie delegacji
- Godziny pracy pracowników
- Status projektów
- Eksport do PDF/Excel

---

## 🧪 Testowanie

```bash
# Z Docker
./vendor/bin/sail artisan test

# Lokalnie
php artisan test
```

---

## 📝 Struktura Projektu

```
delegacje/
├── app/                    # Logika aplikacji (Controllers, Models, Services)
│   ├── Http/
│   │   ├── Controllers/   # Kontrolery
│   │   └── Requests/      # Form Requests (walidacja danych wejściowych)
│   ├── Models/            # Modele Eloquent
│   ├── Services/          # Logika biznesowa i walidacja
│   └── Traits/            # Traity (wspólne funkcjonalności)
├── database/              # Migracje, seedery, factory
├── resources/             # Widoki Blade, CSS, JS
├── routes/                # Definicje tras
├── public/                # Publiczne pliki (index.php, assets)
├── vendor/                # Zależności Composer
├── docker-compose.yml     # Konfiguracja Docker Sail
├── .env.example           # Przykładowy plik środowiskowy
└── README.md             # Ten plik
```

---

## 🏗️ Architektura i Konwencje

### 1. Kontrakty (Contracts)
**Gdzie:** `app/Contracts/`
**Kiedy używać:**
- Polimorficzne relacje (HasEmployee, HasDateRange)
- Read-models / Query services
- Gdzie naprawdę potrzebujesz polimorfizmu

**NIE używaj:**
- Gdy masz konkretny typ - typuj konkretnie
- Nigdy razem z instanceof

### 2. Traity (Traits)
**Gdzie:** `app/Traits/`
**Kiedy używać:**
- Wspólna logika powtarzająca się w wielu klasach
- Częste operacje: overlap dat, walidacja start_date < end_date
- Przykład: `HasDateRange` trait dla operacji na zakresach dat

### 3. Modele (Models)
**Konwencja nazewnictwa pól dat:**
- ZAWSZE: `start_date` / `end_date` (nie date_from/date_to/issued_date/returned_date)
- Zgodnie z konwencją od poziomu bazy danych
- Użyj trait `HasDateRange` dla spójnej obsługi

### 4. Migracje (Migrations)
**Konwencja:**
```php
$table->date('start_date');
$table->date('end_date')->nullable();
```
- ZAWSZE `start_date` / `end_date`
- Spójnie we wszystkich tabelach

### 5. Kontrolery (Controllers)
**Zasady:**
- CIENKIE - tylko orkiestracja
- Przekazują logikę biznesową do serwisów
- Przekazują CAŁE OBIEKTY, nie ID
- Używają route model binding
- Robią findOrFail (nie serwisy)

### 6. Serwisy (Services)
**Zasady:**
- NIE robią findOrFail
- NIE pytają bazy danych (dostają obiekty)
- Liczą / sprawdzają / wykonują logikę biznesową
- Używają Eloquent (scopes, relationships)
- Używają Carbona - operują na obiektach
- Przyjmują JAWNE ARGUMENTY, nie array $data

### 7. Traity w Serwisach
- Centralizują tę samą logikę w różnych serwisach
- Częste operacje: overlap dat, walidacja dat

### 8. Kontrakty w Serwisach
- Serwisy implementują kontrakty
- Zapewniają spójne nazewnictwo + przejrzystość
- Definiują kontrakt API serwisu

### Warstwy Aplikacji

1. **Form Requests** - Walidacja danych wejściowych (required, date, exists, etc.)
2. **Services** - Cała logika biznesowa i walidacja (role, availability, overlaps, etc.)
3. **Models** - Metody pomocnicze (hasRole, isAvailable, etc.) - sprawdzanie stanu
4. **Controllers** - Orkiestracja, wywołanie serwisów, zwracanie odpowiedzi

### Zasady

- **DRY (Don't Repeat Yourself)** - Logika biznesowa w serwisach, nie duplikowana
- **Single Responsibility** - Każda klasa ma jedną odpowiedzialność
- **Separation of Concerns** - Form Requests dla walidacji, Services dla logiki, Controllers dla orkiestracji
- **No Repository Pattern** - Używamy Eloquent bezpośrednio + scopes + query services
- **No Overengineering** - Kontrakty tylko tam, gdzie naprawdę potrzebne (polimorfizm, read-models)

---

## 🤝 Wkład w Projekt

1. Fork projektu
2. Utwórz branch dla nowej funkcjonalności (`git checkout -b feature/AmazingFeature`)
3. Commit zmian (`git commit -m 'Add some AmazingFeature'`)
4. Push do brancha (`git push origin feature/AmazingFeature`)
5. Otwórz Pull Request

---

## 📄 Licencja

Projekt stworzony dla celów demonstracyjnych i edukacyjnych.

---

## 🆘 Wsparcie

Jeśli napotkasz problemy:
1. Sprawdź sekcję **Instrukcje dla Użytkownika** powyżej
2. Sprawdź dokumentację Docker dla problemów z Docker
3. Otwórz Issue na GitHub

---

**Rekomendowane:** Użyj Docker z Laravel Sail dla najlepszego doświadczenia deweloperskiego! 🚢
