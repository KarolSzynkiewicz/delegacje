---
name: task-copilot
description: Playbooki pracy na zadaniach i backlogu ChronoLogic przez serwer MCP chrono-tasks. Użyj, gdy użytkownik prosi o podsumowanie tygodnia lub okresu, uzupełnienie kategorii zadań, utworzenie zadania z podzadaniami, przegląd backlogu albo pomoc w planowaniu sprintu.
---

# Task Copilot

Praca na zadaniach aplikacji przez serwer MCP `chrono-tasks`. Wszystkie dane
pobieraj narzędziami, nigdy nie zgaduj stanu zadań z pamięci ani z kodu.

Dostępne narzędzia: `tasks_in_period`, `tasks_without_category`,
`backlog_overview` (czytające) oraz `set_task_categories`, `create_task`,
`create_sprint`, `assign_tasks_to_sprint` (zapisujące, wymagają HITL).

**Nigdy nie mutuj danych przez tinker, shell ani SQL – wyłącznie narzędzia MCP.**

## Zasada nadrzędna

Zmiana danych wymaga wyraźnej zgody użytkownika.
Nie wywołuj `set_task_categories` ani `create_task`, dopóki użytkownik nie zobaczył propozycji
i ich nie zaakceptował. Flagi `confirmed_by_user` i `overwrite` odzwierciedlają
decyzję użytkownika, nie Twoją ocenę.

## Playbook: podsumowanie okresu

Wyzwalacze: „co się działo w tym tygodniu”, „podsumuj ostatnie dni”, „raport za sierpień”.

1. Wywołaj `tasks_in_period` z `period` (`this_week`, `last_week`, `last_7_days`,
   `this_month`, `last_month`) albo z parą `start_date` i `end_date`.
2. Napisz zwięzłe podsumowanie prozą, nie listę wszystkich zadań. Pogrupuj po
   temacie lub kategorii, a nie po ID.
3. Uwzględnij: co zamknięto, co utknęło (długo `in_progress`, termin po czasie),
   co jest nowe i gdzie toczy się dyskusja w komentarzach.
4. Wyróżnij ryzyka: przeterminowane `due_date`, zadania bez przypisanej osoby,
   podzadania stojące w miejscu.
5. Nazwy zadań cytuj oryginalnie i podawaj ID, żeby dało się je odnaleźć.

## Playbook: uzupełnianie kategorii

Wyzwalacze: „ogarnij kategorie”, „które taski nie mają kategorii”, „poprzypisuj etykiety”.

1. Wywołaj `tasks_without_category`. Odpowiedź zawiera też `known_categories`
   ze słownikiem wartości już używanych.
2. Zaproponuj kategorię dla każdego zadania, korzystając z istniejącego słownika.
   Nową nazwę twórz tylko wtedy, gdy żadna istniejąca nie pasuje, i oznacz ją
   wprost jako nową propozycję.
3. Pokaż tabelę: ID, nazwa zadania, proponowana kategoria, jednozdaniowe uzasadnienie.
4. Poproś o akceptację i pozwól odrzucić lub poprawić pojedyncze pozycje.
5. Po zgodzie wywołaj `set_task_categories` z `confirmed_by_user: true` i wyłącznie
   zaakceptowanymi pozycjami. Nadpisanie istniejącej kategorii wymaga osobnej
   zgody i `overwrite: true`.
6. Podsumuj raport zwrotny: co zapisano, co pominięto i dlaczego.

Uwaga na spójność słownika: w bazie istnieją zarówno warianty typu `Bug / UI`,
jak i `UI / Bug`. Preferuj wariant częstszy według liczników w `known_categories`.

## Playbook: planowanie sprintu

Wyzwalacze: „zaplanuj sprint”, „co wziąć z backlogu”, „pogrupuj backlog”.

1. Wywołaj `backlog_overview`. Zwraca otwarte pozycje bez sprintu, listę sprintów
   z celem i definition of done oraz rozkład po kategoriach i typach.
2. Pogrupuj pozycje w spójne tematy — po wspólnym celu, module lub zależności.
3. Przedstaw propozycję sprintu (nazwa, cel, DoD, daty) i listę zadań z podzadaniami.
4. Po zgodzie użytkownika:
   - `create_sprint` z `confirmed_by_user: true`
   - dla każdego zadania `create_task` z `sprint_id` albo `assign_tasks_to_sprint`
     dla pozycji już istniejących w backlogu
5. Podaj linki do sprintu i utworzonych zadań z odpowiedzi narzędzi.

## Playbook: tworzenie zadania

Wyzwalacze: „zrób zadanie”, „dodaj task z podzadaniami”, „stwórz checklistę”.

1. Na podstawie prośby użytkownika przygotuj propozycję: nazwa, opis, kategoria
   (preferuj `known_categories` z `tasks_without_category` jeśli nie wiesz),
   opcjonalnie priorytet, termin, przypisanie.
2. Jeśli zadanie ma kroki — dodaj numerowaną listę podzadań w logicznej kolejności.
3. Pokaż użytkownikowi pełną propozycję przed zapisem. Nic nie twórz „w tle”.
4. Po zgodzie wywołaj `create_task` z `confirmed_by_user: true` i dokładnie tymi
   danymi, które zaakceptował.
5. Podaj link do utworzonego zadania z odpowiedzi narzędzia (`task.url`).

## Ograniczenia

- Lokalny stdio (Cursor) działa na koncie z `MCP_ACTOR_USER_ID`.
- HTTP `/mcp/tasks` (ChatGPT, Grok) działa na koncie użytkownika z OAuth.
- Kategoria to zwykły tekst; nie ma rejestru etykiet ani ich walidacji.
- Edycja istniejących zadań (poza kategoriami i sprintem) — na razie ręcznie w aplikacji.
