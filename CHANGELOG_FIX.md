# Podsumowanie zmian - Fix REST & Tests

Zmiany zostały wprowadzone w celu naprawy widoków po refaktoryzacji routingu na RESTful API oraz zapewnienia pełnej testowalności aplikacji.

## 1. Routing i Widoki (REST Refactor)
- **Naprawa konfliktów `shallow()`**: Rozwiązano problem nakładających się nazw tras dla zasobów zagnieżdżonych. Przypisania pojazdów i mieszkań otrzymały unikalne prefiksy w `web.php`, co przywróciło działanie standardowych CRUDów dla `vehicles` i `accommodations`.
- **Aktualizacja linków**: Wszystkie widoki (`demands`, `assignments`, `vehicle-assignments`, `accommodation-assignments`) zostały zaktualizowane o poprawne nazwy tras zgodne z nową strukturą.
- **Brakujące akcje**: Dodano brakujące formularze usuwania oraz linki do edycji w widokach list (index), które wcześniej nie były dostępne lub nie działały.
- **Nawigacja**: Poprawiono `navigation.blade.php`, usuwając odwołania do nieistniejących już globalnych tras `assignments.index`.

## 2. Testy i Jakość Kodu
- **Testy Jednostkowe (Unit)**: Dodano `EmployeeTest.php` sprawdzający logikę dostępności pracownika oraz atrybuty modelu.
- **Testy Funkcjonalne (Feature)**: Naprawiono i zaktualizowano `ProjectAssignmentTest.php` oraz `VehicleAssignmentTest.php`, dostosowując je do nowych tras REST.
- **Fabryki (Factories)**: Utworzono brakujące fabryki dla modeli: `Employee`, `Project`, `Location`, `Role`, `Vehicle`. Umożliwia to łatwe generowanie danych testowych.
- **Konfiguracja PHPUnit**: Skonfigurowano bazę danych SQLite `:memory:` dla testów, co znacznie przyspiesza ich wykonywanie i eliminuje zależność od zewnętrznej bazy.
- **CSRF w testach**: Globalnie wyłączono weryfikację CSRF w środowisku testowym (`TestCase.php`), aby umożliwić testowanie formularzy POST/PUT bez zbędnego boilerplate'u.

## 3. Automatyzacja i Plug-and-Play
- **Skrypt `start.sh`**: Zaktualizowano skrypt startowy, dodając automatyczną instalację zależności Composer przez tymczasowy kontener Docker, jeśli folder `vendor` nie istnieje. Rozwiązuje to problem "jajka i kury" przy pierwszym uruchomieniu Sail.
- **UserSeeder**: Dodano automatyczne tworzenie domyślnego użytkownika testowego podczas seedowania bazy.
- **Migracje**: Poprawiono migrację `update_time_logs_table`, aby była kompatybilna z SQLite (pominięcie usuwania kluczy obcych, które w SQLite wymagają przebudowy tabeli).

## Jak uruchomić aplikację?

1. **Pobierz zmiany**:
   ```bash
   git fetch origin
   git checkout fix/rest-views-and-tests
   ```

2. **Uruchom automatyczny setup**:
   ```bash
   chmod +x start.sh
   ./start.sh
   ```
   Skrypt zajmie się wszystkim: instalacją paczek, uruchomieniem kontenerów, migracjami, seedami i budowaniem frontendu.

3. **Dane logowania**:
   - **URL**: `http://localhost`
   - **Email**: `test@example.com`
   - **Hasło**: `password123`

4. **Uruchomienie testów**:
   ```bash
   ./vendor/bin/sail artisan test
   ```
