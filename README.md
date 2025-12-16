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

## 🐳 Uruchomienie Projektu (Docker)

Aby uruchomić projekt za pomocą Docker Compose (zalecane dla środowiska produkcyjnego/deweloperskiego):

1.  **Upewnij się, że masz zainstalowany Docker i Docker Compose.**

2.  **Sklonuj repozytorium** (jeśli jeszcze tego nie zrobiłeś).

3.  **Skonfiguruj środowisko:**
    ```bash
    cp .env.example .env
    ```

4.  **Uruchom kontenery:**
    ```bash
    docker-compose up --build -d
    ```

5.  **Wygeneruj klucz aplikacji i uruchom migracje/seedery wewnątrz kontenera:**
    ```bash
    docker-compose exec app php artisan key:generate
    docker-compose exec app php artisan migrate --seed
    ```

6.  **Kompilacja zasobów front-end (opcjonalnie, jeśli wprowadzasz zmiany w CSS/JS):**
    ```bash
    docker-compose exec app npm install
    docker-compose exec app npm run build
    ```

Aplikacja będzie dostępna pod adresem: `http://localhost:8000`

---
*Projekt stworzony przez **Manus AI**.*
