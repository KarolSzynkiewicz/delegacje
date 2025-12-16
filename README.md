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

## 🛠️ Wymagania

*   PHP >= 8.1
*   Composer
*   Node.js & npm (dla kompilacji zasobów front-end)
*   Docker i Docker Compose (dla łatwego uruchomienia)

## 💻 Uruchomienie Projektu (Lokalnie)

### 1. Klonowanie i Instalacja

1.  **Sklonuj repozytorium:**
    ```bash
    git clone https://github.com/KarolSzynkiewicz/delegacje.git
    cd delegacje
    ```

2.  **Zainstaluj zależności PHP:**
    ```bash
    composer install
    ```

3.  **Skonfiguruj środowisko:**
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```
    *(Projekt domyślnie używa bazy danych SQLite, która jest już skonfigurowana w pliku `.env`)*

4.  **Zainstaluj zależności front-end i skompiluj zasoby:**
    ```bash
    npm install
    npm run build
    ```

5.  **Uruchom migracje i seedery (przykładowe dane):**
    ```bash
    php artisan migrate --seed
    ```

6.  **Uruchom serwer deweloperski:**
    ```bash
    php artisan serve
    ```
    Aplikacja będzie dostępna pod adresem: `http://127.0.0.1:8000`

### Dane Logowania (Testowe)

Po uruchomieniu `php artisan migrate --seed`, możesz zalogować się testowym użytkownikiem:

| Pole | Wartość |
| :--- | :--- |
| **Email** | `test@example.com` |
| **Hasło** | `password123` |

## 🐳 Uruchomienie Projektu (Docker Compose)

Ta metoda jest zalecana i najłatwiejsza do uruchomienia projektu na Twoim komputerze.

**Wymagania:**
*   Zainstalowany **Docker Desktop** (lub Docker i Docker Compose) na Twoim komputerze.

**Instrukcja:**

1.  **Sklonuj Repozytorium i Przejdź do Katalogu:**
    ```bash
    git clone https://github.com/KarolSzynkiewicz/delegacje.git
    cd delegacje
    ```

2.  **Skopiuj plik środowiskowy:**
    ```bash
    cp .env.example .env
    ```
    *(W pliku `.env` ustawiono domyślne wartości dla Docker, w tym `DB_CONNECTION=sqlite`)*

3.  **Uruchom Kontenery i Zbuduj Obraz:**
    ```bash
    docker-compose up --build -d
    ```
    *(Użycie `--build` jest ważne przy pierwszym uruchomieniu lub po zmianach w `Dockerfile`)*

4.  **Wykonaj Konfigurację w Kontenerze (Jednorazowo):**
    Po uruchomieniu kontenerów, musisz wykonać poniższe komendy, aby przygotować aplikację:
    ```bash
    # 1. Wygeneruj klucz aplikacji
    docker-compose exec app php artisan key:generate

    # 2. Uruchom migracje i seedery (tworzy bazę danych i dodaje testowego użytkownika)
    docker-compose exec app php artisan migrate --seed

    # 3. Zainstaluj i skompiluj zasoby front-end
    docker-compose exec app npm install
    docker-compose exec app npm run build
    ```

5.  **Gotowe!** Aplikacja będzie dostępna pod adresem:
    [http://localhost:8000](http://localhost:8000)

**Wskazówka dla Docker Desktop:**
Jeśli używasz Docker Desktop, po wykonaniu kroku 3, projekt powinien pojawić się w sekcji **"Projects"** i możesz go tam uruchomić/zatrzymać za pomocą przycisków. Konfigurację (krok 4) nadal musisz wykonać w terminalu.

---
*Projekt stworzony przez **Manus AI**.*
