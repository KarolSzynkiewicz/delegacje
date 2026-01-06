# Plan projektu "Stocznia"

## Cel
Stworzenie aplikacji webowej w Laravel do zarządzania logistyką i delegowaniem pracowników w firmie, z pełnym wsparciem dla zarządzania zasobami, magazynem, kosztami i raportowaniem.

## Kluczowe Funkcjonalności (Core Features)

### ✅ Zaimplementowane

| Kategoria | Funkcjonalność | Opis | Status |
| :--- | :--- | :--- | :--- |
| **Zarządzanie Użytkownikami i Rolami** | **Autoryzacja i Role** | System logowania/rejestracji. Role: Administrator, Kierownik (Manager), Pracownik (Employee). | ✅ |
| **Zarządzanie Pracownikami** | **Rejestr Pracowników** | Dodawanie, edycja, usuwanie danych pracowników (imię, nazwisko, kontakt, umiejętności, dostępność). | ✅ |
| | **Rotacje Pracowników** | Definiowanie okresów dostępności pracowników (od-do). Status automatyczny: zaplanowana/aktywna/zakończona/anulowana. | ✅ |
| | **Dokumenty Pracowników** | Zarządzanie dokumentami pracowników (okresowe i bezokresowe) z datami ważności. | ✅ |
| | **Status Delegacji** | Przegląd aktualnego statusu delegacji każdego pracownika. | ✅ |
| **Zarządzanie Logistyką/Projektami** | **Projekty/Zlecenia** | Tworzenie i zarządzanie głównymi projektami/kontraktami. | ✅ |
| | **Lokalizacje/Stocznie** | Zarządzanie miejscami pracy (np. nazwa stoczni, adres, dane kontaktowe). | ✅ |
| | **Zapotrzebowanie Projektów** | Definiowanie zapotrzebowania na role w projektach w określonych okresach (od-do). | ✅ |
| **Zarządzanie Delegacjami** | **Tworzenie Delegacji** | Przypisywanie pracowników do konkretnych projektów i lokalizacji na określony czas z walidacją dostępności. | ✅ |
| | **Monitorowanie Postępu** | Śledzenie statusu delegowanych zadań (Oczekujące, Aktywne, Zakończone, Anulowane). | ✅ |
| | **Walidacja Przypisań** | Automatyczne sprawdzanie: rotacji, dokumentów, konfliktów czasowych, zapotrzebowania. | ✅ |
| **Zarządzanie Zasobami** | **Pojazdy** | Zarządzanie flotą pojazdów (marka, model, numer rejestracyjny, stan techniczny, przegląd). | ✅ |
| | **Przypisania Pojazdów** | Przypisywanie pojazdów do pracowników na określony okres z walidacją dostępności. | ✅ |
| | **Akomodacje** | Zarządzanie mieszkaniami (nazwa, adres, pojemność). | ✅ |
| | **Przypisania Mieszkań** | Przypisywanie mieszkań do pracowników z kontrolą pojemności. | ✅ |
| **Planowanie** | **Planer Tygodniowy** | Wizualny przegląd projektów, zapotrzebowania, przypisań i zasobów w ujęciu tygodniowym. | ✅ |
| **Raportowanie** | **Ewidencja Czasu Pracy** | Podstawowa rejestracja czasu pracy pracowników na delegacjach (Time Logs). | ✅ |

### 🚧 Planowane do Implementacji

| Kategoria | Funkcjonalność | Opis | Priorytet |
| :--- | :--- | :--- | :--- |
| **Zarządzanie Wyjazdami** | **Zjazdy-Wyjazdy** | Rejestrowanie wyjazdów pracowników na delegacje (data wyjazdu, miejsce docelowe, środek transportu, koszty podróży). | Wysoki |
| | **Powroty** | Rejestrowanie powrotów pracowników z delegacji (data powrotu, status, rozliczenie kosztów). | Wysoki |
| | **Trasy i Transport** | Planowanie tras, wybór środków transportu (własny pojazd, transport publiczny, loty), kalkulacja kosztów. | Średni |
| **Ewidencja Czasu Pracy** | **Rejestrowanie Realnych Godzin** | Szczegółowa rejestracja rzeczywistych godzin pracy (dzień, godziny rozpoczęcia/zakończenia, przerwy, nadgodziny). | Wysoki |
| | **Weryfikacja Godzin** | Porównanie planowanych vs rzeczywistych godzin pracy, wykrywanie rozbieżności. | Średni |
| | **Karty Czasu Pracy** | Generowanie kart czasu pracy dla pracowników (tygodniowe/miesięczne). | Średni |
| **Zarządzanie Magazynem** | **Sprzęt i Narzędzia** | Katalog sprzętu i narzędzi dostępnych w magazynie (nazwa, typ, stan, lokalizacja). | Wysoki |
| | **Zapotrzebowanie na Sprzęt** | Powiązanie ról z wymaganym sprzętem (np. Spawacz potrzebuje spawarki, maski, rękawic). | Wysoki |
| | **Wydania z Magazynu** | Rejestrowanie wydań sprzętu dla pracowników/projektów (data, ilość, odbiorca, projekt). | Wysoki |
| | **Zwroty do Magazynu** | Rejestrowanie zwrotów sprzętu z kontrolą stanu (uszkodzenia, zużycie). | Wysoki |
| | **Protokoły Zdawczo-Odbiorcze** | Generowanie protokołów zdawczo-odbiorczych sprzętu (PDF, podpisy, status). | Średni |
| | **Stan Magazynu** | Monitoring stanu magazynu (dostępność, rezerwacje, alerty o niskim stanie). | Średni |
| | **Wysyłka Sprzętu** | Planowanie i rejestrowanie wysyłki sprzętu na miejsce realizacji projektu. | Średni |
| **Raportowanie** | **Raport Wypełnienia Zapotrzebowania** | Analiza wypełnienia zapotrzebowania na role w projektach (planowane vs przypisane, braki, nadmiary). | Wysoki |
| | **Raport Zysków i Kosztów** | Kalkulacja zysków i kosztów projektów (przychody, koszty pracowników, transportu, sprzętu). | Wysoki |
| | **Miejsca Powstania Kosztów** | Szczegółowa analiza kosztów według kategorii: pracownicy, transport, sprzęt, akomodacja, inne. | Wysoki |
| | **Raporty Kosztowe** | Generowanie raportów kosztowych dla projektów (tygodniowe/miesięczne/końcowe). | Wysoki |
| | **Raporty Logistyczne** | Rozszerzone raporty o wykorzystaniu pracowników, postępie projektów i obłożeniu lokalizacji. | Średni |
| | **Eksport Raportów** | Eksport raportów do PDF/Excel/CSV. | Średni |

## Schemat Bazy Danych

### ✅ Zaimplementowane Tabele

| Tabela | Opis | Kluczowe Pola | Relacje |
| :--- | :--- | :--- | :--- |
| `users` | Użytkownicy systemu (Admin, Manager, Employee). | `id`, `name`, `email`, `password`, `role` | One-to-One: `users` -> `employees` |
| `employees` | Szczegółowe dane pracowników delegowanych. | `id`, `user_id`, `first_name`, `last_name`, `phone`, `email`, `image_path` | One-to-One: `employees` -> `users` |
| `roles` | Role pracowników (Spawacz, Dekarz, Elektryk, itp.). | `id`, `name`, `description` | Many-to-Many: `roles` <-> `employees` |
| `employee_role` | Tabela pivot: pracownicy-role. | `employee_id`, `role_id` | - |
| `locations` | Miejsca, do których delegowani są pracownicy (Stocznie). | `id`, `name`, `address`, `contact_person` | One-to-Many: `locations` -> `projects` |
| `projects` | Główne zlecenia lub kontrakty. | `id`, `location_id`, `name`, `description`, `status`, `client_name`, `budget` | One-to-Many: `projects` -> `project_demands`, `project_assignments` |
| `project_demands` | Zapotrzebowanie projektu na role w okresie. | `id`, `project_id`, `role_id`, `required_count`, `date_from`, `date_to`, `notes` | Many-to-One: `project_demands` -> `projects`, `roles` |
| `project_assignments` | Przypisanie pracownika do projektu w roli. | `id`, `project_id`, `employee_id`, `role_id`, `start_date`, `end_date`, `status`, `notes` | Many-to-One: `project_assignments` -> `projects`, `employees`, `roles` |
| `rotations` | Rotacje dostępności pracowników. | `id`, `employee_id`, `start_date`, `end_date`, `status`, `notes` | Many-to-One: `rotations` -> `employees` |
| `documents` | Typy dokumentów (Uprawnienia A1, Prawo jazdy, itp.). | `id`, `name`, `kind` (okresowy/bezokresowy), `is_required` | One-to-Many: `documents` -> `employee_documents` |
| `employee_documents` | Dokumenty pracowników. | `id`, `employee_id`, `document_id`, `valid_from`, `valid_to`, `notes` | Many-to-One: `employee_documents` -> `employees`, `documents` |
| `vehicles` | Pojazdy firmowe. | `id`, `registration_number`, `brand`, `model`, `capacity`, `technical_condition`, `inspection_valid_to`, `image_path` | One-to-Many: `vehicles` -> `vehicle_assignments` |
| `vehicle_assignments` | Przypisania pojazdów do pracowników. | `id`, `employee_id`, `vehicle_id`, `start_date`, `end_date`, `notes` | Many-to-One: `vehicle_assignments` -> `employees`, `vehicles` |
| `accommodations` | Mieszkania dostępne dla pracowników. | `id`, `name`, `address`, `capacity`, `description`, `image_path` | One-to-Many: `accommodations` -> `accommodation_assignments` |
| `accommodation_assignments` | Przypisania mieszkań do pracowników. | `id`, `employee_id`, `accommodation_id`, `start_date`, `end_date`, `notes` | Many-to-One: `accommodation_assignments` -> `employees`, `accommodations` |
| `time_logs` | Rejestracja czasu pracy na delegacji. | `id`, `project_assignment_id`, `date`, `hours_worked`, `notes` | Many-to-One: `time_logs` -> `project_assignments` |

### 🚧 Planowane Tabele

| Tabela | Opis | Kluczowe Pola | Relacje |
| :--- | :--- | :--- | :--- |
| `trips` | Wyjazdy pracowników na delegacje. | `id`, `employee_id`, `project_assignment_id`, `departure_date`, `return_date`, `destination`, `transport_type`, `cost`, `status`, `notes` | Many-to-One: `trips` -> `employees`, `project_assignments` |
| `work_hours` | Szczegółowa rejestracja rzeczywistych godzin pracy. | `id`, `project_assignment_id`, `date`, `start_time`, `end_time`, `break_duration`, `overtime_hours`, `notes` | Many-to-One: `work_hours` -> `project_assignments` |
| `equipment` | Sprzęt i narzędzia w magazynie. | `id`, `name`, `type`, `category`, `unit`, `current_stock`, `min_stock`, `location`, `status`, `notes` | One-to-Many: `equipment` -> `equipment_requirements`, `equipment_issues`, `equipment_returns` |
| `equipment_requirements` | Wymagany sprzęt dla ról. | `id`, `role_id`, `equipment_id`, `quantity_required`, `notes` | Many-to-One: `equipment_requirements` -> `roles`, `equipment` |
| `equipment_issues` | Wydania sprzętu z magazynu. | `id`, `equipment_id`, `employee_id`, `project_id`, `issue_date`, `quantity`, `expected_return_date`, `status`, `notes` | Many-to-One: `equipment_issues` -> `equipment`, `employees`, `projects` |
| `equipment_returns` | Zwroty sprzętu do magazynu. | `id`, `equipment_issue_id`, `return_date`, `quantity_returned`, `condition` (dobry/uszkodzony/zużyty), `notes` | Many-to-One: `equipment_returns` -> `equipment_issues` |
| `equipment_transfers` | Wysyłka/przeniesienie sprzętu między lokalizacjami. | `id`, `equipment_id`, `from_location_id`, `to_location_id`, `project_id`, `transfer_date`, `quantity`, `status`, `notes` | Many-to-One: `equipment_transfers` -> `equipment`, `locations`, `projects` |
| `handover_protocols` | Protokoły zdawczo-odbiorcze sprzętu. | `id`, `equipment_issue_id`, `protocol_number`, `issue_date`, `return_date`, `issuer_signature`, `receiver_signature`, `pdf_path`, `notes` | Many-to-One: `handover_protocols` -> `equipment_issues` |
| `project_costs` | Koszty projektów (szczegółowe). | `id`, `project_id`, `cost_type` (pracownik/transport/sprzęt/akomodacja/inne), `description`, `amount`, `date`, `employee_id`, `vehicle_id`, `equipment_id`, `accommodation_id`, `notes` | Many-to-One: `project_costs` -> `projects`, `employees`, `vehicles`, `equipment`, `accommodations` |
| `project_revenues` | Przychody projektów. | `id`, `project_id`, `revenue_type`, `description`, `amount`, `date`, `invoice_number`, `notes` | Many-to-One: `project_revenues` -> `projects` |

## Diagram Relacji (Rozszerzony)

```
Employee (1) -----> (N) Rotation
Employee (1) -----> (N) EmployeeDocument
Employee (N) <-----> (N) Role (przez employee_role)
Employee (N) <-----> (N) Project (przez ProjectAssignment)
Employee (1) -----> (N) VehicleAssignment
Employee (1) -----> (N) AccommodationAssignment
Employee (1) -----> (N) Trip
Employee (1) -----> (N) EquipmentIssue

Project (1) -----> (N) ProjectDemand
Project (1) -----> (N) ProjectAssignment
Project (1) -----> (N) ProjectCost
Project (1) -----> (N) ProjectRevenue
Project (1) -----> (N) EquipmentIssue
Project (1) -----> (N) EquipmentTransfer

ProjectAssignment (1) -----> (N) TimeLog
ProjectAssignment (1) -----> (N) WorkHour
ProjectAssignment (1) -----> (N) Trip

Role (1) -----> (N) EquipmentRequirement
Equipment (1) -----> (N) EquipmentRequirement
Equipment (1) -----> (N) EquipmentIssue
EquipmentIssue (1) -----> (N) EquipmentReturn
EquipmentIssue (1) -----> (1) HandoverProtocol
```

## Następne Kroki - Roadmapa

### Faza 1: Wyjazdy i Realne Godziny Pracy (Priorytet Wysoki)
1. ✅ Implementacja podstawowej struktury przypisań i rotacji
2. 🚧 Utworzenie tabeli `trips` i modelu `Trip`
3. 🚧 Implementacja CRUD dla wyjazdów (zjazdy-wyjazdy)
4. 🚧 Utworzenie tabeli `work_hours` i modelu `WorkHour`
5. 🚧 Implementacja rejestracji rzeczywistych godzin pracy
6. 🚧 Interfejs do wprowadzania godzin pracy (dzienny/tygodniowy)
7. 🚧 Walidacja i porównanie planowanych vs rzeczywistych godzin

### Faza 2: Magazyn i Sprzęt (Priorytet Wysoki)
1. 🚧 Utworzenie tabel `equipment`, `equipment_requirements`, `equipment_issues`, `equipment_returns`
2. 🚧 Implementacja modeli: `Equipment`, `EquipmentRequirement`, `EquipmentIssue`, `EquipmentReturn`
3. 🚧 CRUD dla sprzętu i narzędzi
4. 🚧 Powiązanie ról z wymaganym sprzętem
5. 🚧 System wydań i zwrotów sprzętu
6. 🚧 Monitoring stanu magazynu (alerty o niskim stanie)
7. 🚧 Utworzenie tabeli `equipment_transfers` i implementacja wysyłki sprzętu
8. 🚧 Generowanie protokołów zdawczo-odbiorczych (PDF)

### Faza 3: Raportowanie (Priorytet Wysoki)
1. 🚧 Utworzenie tabel `project_costs` i `project_revenues`
2. 🚧 Implementacja modeli `ProjectCost` i `ProjectRevenue`
3. 🚧 Raport wypełnienia zapotrzebowania (planowane vs przypisane)
4. 🚧 Raport zysków i kosztów projektów
5. 🚧 Analiza miejsc powstania kosztów (kategorie, wykresy)
6. 🚧 Raporty kosztowe (tygodniowe/miesięczne/końcowe)
7. 🚧 Eksport raportów do PDF/Excel/CSV
8. 🚧 Dashboard z kluczowymi metrykami

### Faza 4: Optymalizacja i Rozszerzenia (Priorytet Średni)
1. 🚧 Karty czasu pracy (tygodniowe/miesięczne)
2. 🚧 Weryfikacja godzin (automatyczne wykrywanie rozbieżności)
3. 🚧 Rozszerzone raporty logistyczne
4. 🚧 Notyfikacje i alerty (email/push)
5. 🚧 API REST dla integracji zewnętrznych
6. 🚧 Aplikacja mobilna (opcjonalnie)

## Definicja "Done" (Definition of Done)

Funkcjonalność jest uznana za zakończoną, gdy:
- ✅ Kod jest napisany zgodnie z PSR-12
- ✅ Testy jednostkowe i integracyjne pokrywają >= 80% logiki biznesowej
- ✅ Migracje bazy danych są utworzone i przetestowane
- ✅ Modele i relacje są poprawnie zdefiniowane
- ✅ Kontrolery obsługują wszystkie wymagane operacje CRUD
- ✅ Widoki Blade są responsywne i zgodne z designem
- ✅ Walidacja działa poprawnie (frontend + backend)
- ✅ Dokumentacja jest zaktualizowana (README, komentarze w kodzie)
- ✅ Code review zostało przeprowadzone
- ✅ Funkcjonalność została przetestowana manualnie

## Technologie i Narzędzia

- **Backend:** Laravel 11, PHP 8.1+
- **Frontend:** Blade Templates, Livewire 3, Alpine.js, Tailwind CSS
- **Baza Danych:** MySQL
- **Docker:** Laravel Sail
- **Testy:** PHPUnit, Pest (opcjonalnie)
- **Raporty PDF:** DomPDF / wkhtmltopdf
- **Eksport Excel:** Laravel Excel (Maatwebsite)
- **CI/CD:** GitHub Actions (planowane)
