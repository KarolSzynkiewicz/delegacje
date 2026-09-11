---
name: task-copilot
description: Playbooki pracy na zadaniach, backlogu i tablicy ChronoLogic przez serwer MCP chrono-tasks. Użyj, gdy użytkownik prosi o podsumowanie tygodnia, zadania, sprint, komentarz z @ / zadaniem / zatwierdzeniem, albo wątki na tablicy.
---

# Task Copilot

Praca na zadaniach aplikacji przez serwer MCP `chrono-tasks`. Wszystkie dane
pobieraj narzędziami, nigdy nie zgaduj stanu zadań z pamięci ani z kodu.

**Nigdy nie mutuj danych przez tinker, shell ani SQL – wyłącznie narzędzia MCP.**

## Narzędzia

Czytające: `period_analytics`, `search_tasks`, `get_task`, `get_task_comments`,
`search_posts`, `get_post`, `get_post_comments`, `list_post_tags`,
`list_users`, `list_categories`, `sprint_insights`, `tasks_without_category`,
`backlog_overview`, `tasks_in_period` (dump – unikaj na rzecz analityki).

Zapisujące (HITL): `set_task_categories`, `update_task`, `update_subtask`,
`add_subtasks`, `add_comment`, `create_post`, `update_post`, `create_task`,
`create_sprint`, `assign_tasks_to_sprint`.

## Zasada nadrzędna

Zmiana danych wymaga wyraźnej zgody użytkownika.
Nie wywołuj narzędzi zapisujących, dopóki użytkownik nie zobaczył propozycji
i ich nie zaakceptował. `confirmed_by_user` odzwierciedla decyzję użytkownika,
nie Twoją ocenę.

## Playbook: hygiene kategorii i przypisań

Wyzwalacze: „ogarnij kategorie”, „kto nie ma kategorii / osoby”, „przypisz Anię”.

1. `search_tasks` z `missing_category` i/lub `unassigned` (albo
   `tasks_without_category`). Słownik: `list_categories` (albo `known_categories`
   z hygiene / analityki).
2. Osoby: `list_users` (albo `assignee_name` w `search_tasks`).
3. Tabela: ID, nazwa, proponowana kategoria / osoba, uzasadnienie.
4. Po zgodzie: `set_task_categories` albo `update_task` (jedno zadanie na
   wywołanie przypisania) z `confirmed_by_user: true`.

## Playbook: podsumowanie okresu

Wyzwalacze: „co się działo”, „podsumuj tydzień”, „co mówią wykresy”.

1. `period_analytics` (`this_week`, `last_week`, … albo daty). To KPI,
   hottest threads, stale, macierze współpracy – bez ciał komentarzy.
2. Dla 3–5 ID z `pointers.hottest_task_ids`: `get_task` (o czym jest)
   i `get_task_comments` (co się działo w wątku).
3. Napisz prozą: tematy (nie lista ID), kto komu komentuje / pomaga
   w podzadaniach, ryzyka (stale, unassigned, overdue).
4. Opcjonalnie zaproponuj ping na stale: treść
   `@{assignee} proszę o krótki update statusu.` Po zgodzie `add_comment`.

Nie wołaj `tasks_in_period` do raportu – za duży JSON.

## Playbook: taski osoby / kategorii

Wyzwalacze: „co ma Karol”, „Bug / UI”, „otwarte u Ani”.

`list_categories` gdy nie znasz dokładnej nazwy. Potem `search_tasks` z
`assignee_name` / `assigned_to` / `created_by_name` / `category` / `status`.
Ewentualnie `get_task` na konkretne ID.

## Playbook: planowanie sprintu

Wyzwalacze: „zaplanuj sprint”, „co wziąć z backlogu”, „wypadnie ze sprintu”.

1. `backlog_overview`.
2. Propozycja: nazwa, cel, DoD, daty, lista zadań (i co wypada).
3. Po zgodzie: `create_sprint` → `create_task` z `sprint_id` albo
   `assign_tasks_to_sprint`. Odpinanie: `update_task` z `unassign_sprint: true`
   (jedno zadanie na wywołanie).
4. W trakcie sprintu: `sprint_insights`.

## Playbook: tworzenie zadania

1. Propozycja (nazwa, opis, kategoria ze słownika, priorytet, termin, osoba,
   podzadania). `list_users` gdy przypisujesz. `list_categories` po nazwę.
2. Pokaż pełną kartę. Nic nie twórz w tle.
3. `create_task` z `confirmed_by_user: true`. Podaj `task.url`.

## Playbook: checklista / podzadania

Wyzwalacze: „odhacz”, „dopisz krok”, „zrób podzadanie”.

1. `get_task` – ID kroków i stan `is_completed`.
2. Propozycja: które odhaczyć / otworzyć, nowa nazwa, osoba, nowe kroki.
   Odhaczenie kroku **nie** zamyka rodzica.
3. Po zgodzie: `update_subtask` (jedno podzadanie) albo `add_subtasks`.
4. Zamknięcie karty: osobno `update_task` `status: completed`.

## Playbook: tablica

Wyzwalacze: „co na tablicy”, „post o logistyce”, „dopisz wątek”, „skomentuj”.

1. Tag: `list_post_tags`. Wątki: `search_posts` z `tag` / `q` (karty, bez obrazków).
2. Treść: `get_post`. Komentarze: `get_post_comments`.
3. Nowy wątek: propozycja tytułu, treści, tagów → `create_post`.
   Edycja (autor/admin): `update_post`. MCP nie wysyła załączników.
4. Komentarz: `add_comment` z `post_id` (patrz playbook komentarzy).

## Playbook: komentarz (@ / zadanie / zatwierdzenie)

Wyzwalacze: „pingnij”, „zrób z tego zadanie”, „poproś o zatwierdzenie”.

Cel: `task_id` albo `post_id` – dokładnie jedno.

W `body` (jak w UI):
- `@Anna` – wzmianka
- `@Anna!` – zadanie z kontekstu (backlog)
- `@Anna?` – wniosek o zatwierdzenie
- `Tytuł wniosku @Anna? // opis`

Albo `mentions: [{name: "Anna", kind: "task"|"approval"|"notify"}]`.

Pokaż treść i skutki. Po zgodzie `add_comment` z `confirmed_by_user: true`.
Bez załączników.

## Ograniczenia

- Lokalny stdio (Cursor) działa na koncie z `MCP_ACTOR_USER_ID`.
- HTTP `/mcp/tasks` (ChatGPT, Grok) działa na koncie użytkownika z OAuth.
- Kategoria to zwykły tekst; preferuj częstszy wariant ze słownika
  (`Bug / UI` vs `UI / Bug`).
- `update_task` nie zmienia kategorii ani nie wkłada do sprintu
  (`set_task_categories` / `assign_tasks_to_sprint`).
