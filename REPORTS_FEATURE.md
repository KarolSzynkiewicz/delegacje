# Moduł Raportów - Feature/Raporty

## Opis

Moduł raportów umożliwia generowanie zaawansowanych raportów z delegacji pracowników. Funkcjonalność jest w fazie szkieletu i czeka na implementację.

## Struktura

### Pliki Dodane

*   **`app/Http/Controllers/ReportController.php`** - Kontroler obsługujący logikę raportów
*   **`app/Models/Report.php`** - Model bazy danych dla raportów
*   **`database/migrations/2025_12_16_120000_create_reports_table.php`** - Migracja tabeli raportów
*   **`resources/views/reports/index.blade.php`** - Widok listy raportów
*   **`resources/views/reports/create.blade.php`** - Widok tworzenia nowego raportu

### Trasy

*   `GET /reports` - Lista raportów
*   `GET /reports/create` - Formularz tworzenia raportu
*   `POST /reports` - Zapis nowego raportu
*   `GET /reports/{id}` - Wyświetl raport
*   `GET /reports/{id}/download` - Pobierz raport

## Funkcjonalności do Implementacji

### 1. Typy Raportów

#### Podsumowanie Delegacji (`delegation_summary`)
*   Liczba delegacji w wybranym okresie
*   Łączny czas trwania delegacji
*   Lista zaangażowanych pracowników
*   Projekty i lokalizacje
*   Średni czas delegacji

#### Godziny Pracowników (`employee_hours`)
*   Łączne godziny pracy na pracownika
*   Nadgodziny
*   Obecność/nieobecność
*   Porównanie z planem
*   Trendy czasowe

#### Status Projektów (`project_status`)
*   Postęp projektu
*   Liczba delegacji na projekt
*   Oś czasu realizacji
*   Zaangażowani pracownicy
*   Budżet vs. rzeczywistość

### 2. Formaty Eksportu

*   **PDF** - Raport w formacie PDF (użyć biblioteki `barryvdh/laravel-dompdf`)
*   **Excel** - Raport w formacie Excel (użyć biblioteki `maatwebsite/excel`)
*   **HTML** - Raport w formacie HTML (do wydruku)

### 3. Filtry

*   Data początkowa i końcowa
*   Pracownik (opcjonalnie)
*   Projekt (opcjonalnie)
*   Lokalizacja (opcjonalnie)

### 4. Przechowywanie

*   Raporty powinny być przechowywane w bazie danych
*   Pliki raportów powinny być zapisywane w `storage/app/reports/`
*   Możliwość pobierania wcześniej wygenerowanych raportów

## Kroki Implementacji

1.  **Instalacja bibliotek:**
   ```bash
   composer require barryvdh/laravel-dompdf
   composer require maatwebsite/excel
   ```

2.  **Implementacja logiki generowania raportów** w `ReportController`

3.  **Tworzenie serwisów** do obsługi logiki biznesowej:
   *   `app/Services/ReportGeneratorService.php`
   *   `app/Services/DelegationReportService.php`
   *   `app/Services/EmployeeHoursReportService.php`
   *   `app/Services/ProjectStatusReportService.php`

4.  **Tworzenie widoków** dla wyświetlania raportów:
   *   `resources/views/reports/show.blade.php`
   *   `resources/views/reports/pdf-template.blade.php`

5.  **Testy jednostkowe** dla logiki raportów

6.  **Dokumentacja** dla użytkowników

## Notatki Techniczne

*   Raporty mogą być generowane asynchronicznie za pomocą Laravel Jobs
*   Można dodać cache dla raportów, które są generowane wielokrotnie
*   Rozważ dodanie schedulera do automatycznego generowania raportów

## Status

**Gałąź:** `feature/raporty`  
**Status:** W trakcie rozwoju  
**Ostatnia aktualizacja:** 2025-12-16

---

*Moduł raportów jest częścią projektu Stocznia.*
