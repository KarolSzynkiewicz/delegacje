# Projekt Systemu Logistyki Przypisań

## Cel
Refaktoryzacja systemu do zarządzania logistyką przypisań pracowników, samochodów, domów i zapotrzebowania projektów w czasie.

## Use Case
1. Klient dzwoni i zgłasza zapotrzebowanie na projekt
2. System rejestruje zapotrzebowanie projektu (ile ludzi, jakie role, od kiedy, do kiedy)
3. Menadżer przypisuje pracowników do projektu w określonych rolach i czasie
4. System śledzi przypisania pracowników do projektów

## Nowa Struktura Modeli

### 1. Project (istniejący - zmodyfikowany)
Reprezentuje projekt klienta.

**Pola:**
- `id` - klucz główny
- `name` - nazwa projektu
- `description` - opis projektu
- `client_name` - nazwa klienta
- `start_date` - data rozpoczęcia projektu
- `end_date` - data zakończenia projektu
- `status` - status projektu (active, completed, on_hold, cancelled)
- `location_id` - lokalizacja projektu (FK)
- `timestamps`

**Relacje:**
- `hasOne(ProjectDemand)` - zapotrzebowanie projektu
- `belongsToMany(Employee)` przez `ProjectAssignment` - przypisani pracownicy
- `belongsTo(Location)` - lokalizacja

---

### 2. ProjectDemand (NOWY)
Reprezentuje zapotrzebowanie projektu na zasoby ludzkie.

**Pola:**
- `id` - klucz główny
- `project_id` - FK do projektu (relacja 1:1)
- `required_workers_count` - liczba potrzebnych pracowników
- `start_date` - od kiedy potrzebni pracownicy
- `end_date` - do kiedy potrzebni pracownicy
- `notes` - dodatkowe uwagi
- `timestamps`

**Relacje:**
- `belongsTo(Project)` - projekt
- `hasMany(ProjectDemandRole)` - wymagane role

---

### 3. ProjectDemandRole (NOWY)
Reprezentuje zapotrzebowanie na konkretną rolę w projekcie.

**Pola:**
- `id` - klucz główny
- `project_demand_id` - FK do zapotrzebowania projektu
- `role_id` - FK do roli
- `required_count` - ile osób w tej roli jest potrzebnych
- `timestamps`

**Relacje:**
- `belongsTo(ProjectDemand)` - zapotrzebowanie projektu
- `belongsTo(Role)` - rola

---

### 4. ProjectAssignment (NOWY - zastępuje Delegation)
Reprezentuje przypisanie pracownika do projektu w określonej roli i czasie (relacja M:N).

**Pola:**
- `id` - klucz główny
- `project_id` - FK do projektu
- `employee_id` - FK do pracownika
- `role_id` - FK do roli (w jakiej roli pracownik jest przypisany)
- `start_date` - od kiedy pracownik jest przypisany
- `end_date` - do kiedy pracownik jest przypisany
- `status` - status przypisania (pending, active, completed, cancelled)
- `notes` - dodatkowe uwagi
- `timestamps`

**Relacje:**
- `belongsTo(Project)` - projekt
- `belongsTo(Employee)` - pracownik
- `belongsTo(Role)` - rola

---

### 5. Employee (istniejący - bez zmian)
Reprezentuje pracownika.

**Relacje (zaktualizowane):**
- `belongsToMany(Project)` przez `ProjectAssignment` - przypisane projekty
- `belongsTo(Role)` - główna rola pracownika

---

### 6. Role (istniejący - bez zmian)
Reprezentuje rolę pracownika (np. Spawacz, Dekarz).

---

## Modele do Usunięcia/Zrefaktoryzowania

### Delegation (DO USUNIĘCIA)
Model `Delegation` będzie zastąpiony przez `ProjectAssignment`, który lepiej odzwierciedla logikę przypisań M:N.

### TimeLog (DO ZACHOWANIA)
Model `TimeLog` może być zachowany, ale jego relacja zostanie zmieniona z `delegation_id` na `project_assignment_id`.

---

## Migracje do Utworzenia

1. `create_project_demands_table` - tabela zapotrzebowań projektów
2. `create_project_demand_roles_table` - tabela zapotrzebowań na role
3. `create_project_assignments_table` - tabela przypisań pracowników do projektów
4. `update_time_logs_table` - aktualizacja relacji w time_logs (opcjonalnie)
5. `drop_delegations_table` - usunięcie starej tabeli delegations

---

## Diagram Relacji

```
Project (1) -----> (1) ProjectDemand
                        |
                        | (1:N)
                        v
                   ProjectDemandRole (N) -----> (1) Role

Project (N) <-----> (N) Employee
        (przez ProjectAssignment)
        
ProjectAssignment (N) -----> (1) Role
ProjectAssignment (N) -----> (1) Project
ProjectAssignment (N) -----> (1) Employee
```

---

## Zasady SOLID

1. **Single Responsibility Principle**: Każdy model ma jedną odpowiedzialność
   - `Project` - zarządza informacjami o projekcie
   - `ProjectDemand` - zarządza zapotrzebowaniem projektu
   - `ProjectAssignment` - zarządza przypisaniami pracowników

2. **Open/Closed Principle**: Modele są otwarte na rozszerzenia (np. dodanie nowych statusów)

3. **Liskov Substitution Principle**: Wszystkie modele dziedziczą z `Model` i mogą być używane zamiennie

4. **Interface Segregation Principle**: Modele nie wymuszają niepotrzebnych zależności

5. **Dependency Inversion Principle**: Kontrolery zależą od abstrakcji (Eloquent ORM), nie konkretnych implementacji

---

## Następne Kroki

1. Utworzenie modeli: `ProjectDemand`, `ProjectDemandRole`, `ProjectAssignment`
2. Utworzenie migracji dla nowych tabel
3. Aktualizacja istniejących modeli (`Project`, `Employee`)
4. Usunięcie modelu `Delegation` i jego migracji
5. Aktualizacja kontrolerów
6. Aktualizacja widoków
