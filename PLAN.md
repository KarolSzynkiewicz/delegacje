# Plan projektu "Stocznia"

## Cel
Stworzenie aplikacji webowej w Laravel do zarządzania logistyką i delegowaniem pracowników w firmie.

## Kluczowe Funkcjonalności (Core Features)

| Kategoria | Funkcjonalność | Opis |
| :--- | :--- | :--- |
| **Zarządzanie Użytkownikami i Rolami** | **Autoryzacja i Role** | System logowania/rejestracji. Role: Administrator, Kierownik (Manager), Pracownik (Employee). |
| **Zarządzanie Pracownikami** | **Rejestr Pracowników** | Dodawanie, edycja, usuwanie danych pracowników (imię, nazwisko, kontakt, umiejętności, dostępność). |
| | **Status Delegacji** | Przegląd aktualnego statusu delegacji każdego pracownika. |
| **Zarządzanie Logistyką/Projektami** | **Projekty/Zlecenia** | Tworzenie i zarządzanie głównymi projektami/kontraktami. |
| | **Lokalizacje/Stocznie** | Zarządzanie miejscami pracy (np. nazwa stoczni, adres, dane kontaktowe). |
| **Zarządzanie Delegacjami** | **Tworzenie Delegacji** | Przypisywanie pracowników do konkretnych projektów i lokalizacji na określony czas. |
| | **Monitorowanie Postępu** | Śledzenie statusu delegowanych zadań (np. Oczekujące, W trakcie, Zakończone). |
| **Raportowanie** | **Ewidencja Czasu Pracy** | Rejestracja czasu pracy pracowników na delegacjach (Time Logs). |
| | **Raporty Logistyczne** | Generowanie raportów o wykorzystaniu pracowników, postępie projektów i obłożeniu lokalizacji. |

## Wstępny Schemat Bazy Danych (Preliminary Database Schema)

Poniżej przedstawiono wstępny schemat relacji między kluczowymi encjami.

| Tabela | Opis | Kluczowe Pola | Relacje |
| :--- | :--- | :--- | :--- |
| `users` | Użytkownicy systemu (Admin, Manager, Employee). | `id`, `name`, `email`, `password`, `role` | One-to-One: `users` -> `employees` |
| `employees` | Szczegółowe dane pracowników delegowanych. | `id`, `user_id`, `phone`, `skills`, `availability` | One-to-One: `employees` -> `users` |
| `locations` | Miejsca, do których delegowani są pracownicy (Stocznie). | `id`, `name`, `address`, `contact_person` | One-to-Many: `locations` -> `projects` |
| `projects` | Główne zlecenia lub kontrakty. | `id`, `location_id`, `name`, `start_date`, `end_date`, `status` | One-to-Many: `projects` -> `delegations` |
| `delegations` | Konkretne przypisanie pracownika do projektu. | `id`, `employee_id`, `project_id`, `start_time`, `end_time`, `status` | Many-to-One: `delegations` -> `employees`, `projects` |
| `time_logs` | Rejestracja czasu pracy na delegacji. | `id`, `delegation_id`, `start_time`, `end_time`, `hours_worked` | Many-to-One: `time_logs` -> `delegations` |

## Następne Kroki
1.  Implementacja migracji bazy danych na podstawie powyższego schematu.
2.  Konfiguracja modeli i relacji w Laravel.
3.  Implementacja podstawowych interfejsów użytkownika (UI) dla zarządzania danymi.
