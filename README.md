# Stocznia - System Zarządzania Logistyką i Delegowaniem Pracowników

**Stocznia** to aplikacja webowa stworzona w oparciu o framework **Laravel**, zaprojektowana do zarządzania kluczowymi aspektami logistyki i zasobów ludzkich w firmie delegującej pracowników.

## 🚀 Funkcjonalności

Aplikacja oferuje następujące moduły:

| Moduł | Opis | Kluczowe Dane |
| :--- | :--- | :--- |
| **Autentykacja** | Logowanie, rejestracja, resetowanie hasła (Laravel Breeze). | Użytkownicy, hasła. |
| **Pracownicy** | Zarządzanie personelem delegowanym. | Imię, Nazwisko, Kontakt, Rola (Spawacz/Dekarz), Ważność A1, Dokumenty (1, 2, 3). |
| **Akomodacje** | Zarządzanie dostępnymi mieszkaniami. | Nazwa, Adres, Pojemność (liczba osób). |
| **Pojazdy** | Zarządzanie flotą pojazdów. | Numer Rejestracyjny, Pojemność, Stan Techniczny, Przegląd Ważny Do. |
| **Lokalizacje** | Zarządzanie miejscami pracy (stoczniami). | Nazwa, Adres. |
| **Projekty** | Tworzenie i zarządzanie projektami. | Nazwa, Opis. |
| **Delegacje** | Przypisywanie pracowników do projektów i lokalizacji. | Pracownik, Projekt, Lokalizacja, Daty. |
| **Zapisy Czasu Pracy** | Rejestrowanie czasu pracy. | Pracownik, Data, Godziny. |
| **Raporty** | Generowanie raportów z delegacji (w rozwoju). | Typy raportów, eksport PDF/Excel. |

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
   git checkout feature/raporty
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
   git checkout feature/raporty
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
├── app/                    # Logika aplikacji (Controllers, Models)
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
