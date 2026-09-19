# ChronoLogic MCP `/mcp/tasks`

HTTP **`POST /mcp/tasks`** (JSON-RPC 2.0, protokół MCP) to ten sam serwer
co lokalny `php artisan mcp:start chrono-tasks`. Klienci zdalni (ChatGPT,
Grok, …) łączą się tu przez OAuth 2.1. Lokalny Cursor — stdio, bez OAuth.

Kod: `app/Mcp/Servers/TasksServer.php` (wersja serwera `0.9.0`),
`routes/ai.php`, `app/Mcp/Tools/*`. Playbook agenta:
`.cursor/skills/task-copilot/SKILL.md`.

---

## 1. Dwa kanały, jedno API narzędzi

| | HTTP | Stdio (Cursor) |
|---|---|---|
| Wejście | `POST /mcp/tasks` | `php artisan mcp:start chrono-tasks` |
| Auth | Bearer OAuth, scope `mcp:use`, middleware `auth:api` + `throttle:mcp` | brak HTTP; proces artisan |
| Kim jest **konto MCP** (aktor) | zalogowany użytkownik tokenu | `MCP_ACTOR_USER_ID` z `.env` (`config('ai_tools.actor_user_id')`) |
| Odkrywanie | `/.well-known/oauth-*` | `.cursor/mcp.json` woła Sail / `docker compose exec` |

Aktor **zapisuje** (komentarze, piny, przesunięcia). Kalendarz Planu może
być **inną osobą** — patrz §4.

Bez `MCP_ACTOR_USER_ID` stdio nie wystartuje narzędzi (brak użytkownika).
HTTP nigdy nie używa tej zmiennej: zawsze OAuth.

Lokalna konfiguracja Cursora (`.cursor/mcp.json`):

```json
{
  "mcpServers": {
    "chrono-tasks": {
      "command": "docker",
      "args": [
        "compose", "exec", "-T", "laravel.test",
        "php", "artisan", "mcp:start", "chrono-tasks"
      ],
      "cwd": "/home/karol/delegacje"
    }
  }
}
```

---

## 2. OAuth 2.1 (tylko HTTP)

Laravel 10 + Passport 11 nie obsługuje wbudowanego `Mcp::oauthRoutes()`
z laravel/mcp (Passport 13). ChronoLogic ma własną warstwę:
`App\Mcp\Support\McpOAuth`.

### 2.1 Discovery

**Protected resource** (RFC 9728):

`GET /.well-known/oauth-protected-resource/mcp/tasks`

```json
{
  "resource": "https://app/mcp/tasks",
  "authorization_servers": ["https://app"],
  "scopes_supported": ["mcp:use"]
}
```

**Authorization server:**

`GET /.well-known/oauth-authorization-server`

- `authorization_endpoint` — zgoda użytkownika (Passport)
- `token_endpoint` — `/oauth/token`
- `registration_endpoint` — `/oauth/register`
- `code_challenge_methods_supported`: `S256` (PKCE)
- `token_endpoint_auth_methods_supported`: `none` (publiczny klient, bez secret)
- `grant_types_supported`: `authorization_code`, `refresh_token`

### 2.2 Dynamiczna rejestracja klienta (RFC 7591)

`POST /oauth/register` (throttle 10/min):

```json
{
  "client_name": "Grok",
  "redirect_uris": ["https://grok.com/oauth/callback"]
}
```

Odpowiedź `201`: `client_id`, `token_endpoint_auth_method: "none"`,
`grant_types`: authorization_code + refresh_token, `scope`: `mcp:use`.
Klient **nie ma secretu** — wymiana kodu tylko PKCE.

Domeny redirect: `MCP_OAUTH_REDIRECT_DOMAINS` (`*` = wszystkie;
na produkcji zawęzić, np. `https://chatgpt.com,https://grok.com`).

### 2.3 Token

1. Przeglądarka: authorize + PKCE S256, scope `mcp:use`.
2. `POST /oauth/token` (`grant_type=authorization_code`) →
   `access_token` + `refresh_token`. Access żyje > 24 h.
3. Refresh: ten sam endpoint, `grant_type=refresh_token`.
   Grant MCP (`McpRefreshTokenGrant`) jest przyjazny klientom publicznym
   (form **i** JSON; stary refresh da się użyć ponownie w oknie).

### 2.4 Wywołanie MCP

```http
POST /mcp/tasks
Authorization: Bearer <access_token>
Content-Type: application/json

{ "jsonrpc": "2.0", "id": 1, "method": "initialize", "params": { … } }
```

Bez tokenu: **401** + `WWW-Authenticate` z `invalid_token` i
`resource_metadata=` (URL discovery). Klient MCP ma sam się zarejestrować
i zdobyć token.

Passport: scope `mcp:use` = „Dostęp do zadań i sprintów ChronoLogic przez MCP”.
Klucze: pliki `storage/oauth-*.key` albo para w `oauth_key_pairs`
(Railway ma ulotny dysk). `PASSPORT_PRIVATE_KEY` / `PASSPORT_PUBLIC_KEY`
mają pierwszeństwo.

---

## 3. Protokół MCP (JSON-RPC)

Typowe metody:

| method | co robi |
|---|---|
| `initialize` | handshake; `result.serverInfo.name` = `ChronoLogic Tasks` |
| `tools/list` | nazwy, opisy, JSON Schema argumentów |
| `tools/call` | `params.name` + `params.arguments` |

Odczyt zwraca JSON w treści narzędzia. Błąd walidacji / HITL / uprawnień
to treść błędu MCP (w testach `assertHasErrors`), **nie** wyjątek HTTP 500.

Nie mutuj bazy tinkerem, shelleem ani SQL — tylko te narzędzia.

---

## 4. Aktor vs osoba kalendarza

**Aktor** = konto MCP (§1). To on musi mieć `tasks.update` (albo rolę
administratora) przy każdym zapisie Planu i zadań.

**Kalendarz** (tylko narzędzia Planu): czyj `/plan` czytamy / zmieniamy.

Argumenty (wszystkie opcjonalne; pierwszeństwo z góry):

1. `assigned_to` — `users.id`
2. `assigned_to_me: true` — kalendarz = aktor
3. `assignee_name` — jak w `list_users` / handle `@Anna`
4. *nic* — kalendarz = aktor („sprawdź **mój** dzień”)

„U Ani”: `list_users` `{ "q": "Anna" }` → occupancy / queue / zapisy z
`assignee_name: "Anna"`. Nie zgaduj ID.

---

## 5. Warstwy danych: karta ≠ siatka Planu

`search_work_items` / `get_task` widzą **kartę**: tytuł, osobę, kategorię,
`due_date`. **Nie** widzą klocków `work_item_time_blocks`.

| Byt | Baza | Jak ruszać z MCP |
|---|---|---|
| Klocek jednego WI | `work_item_time_blocks` `kind=item` | `block_id` + `work_item_id` |
| Sesja (worek) | ten sam + pivot `work_item_time_block_items` | `block_id`, `is_session` |
| Spotkanie z godziną | `project_tasks.starts_at` / `ends_at` | `kind: meeting`, **bez** `block_id`, ruszasz `work_item_id` |
| Kolejka „Do przypięcia” | otwarte WI osoby **bez** slotu ≥ dziś | `plan_queue` → `work_item_id` |
| Termin (due) | `work_items.due_at` | **nie** jest occupancy; w UI to zwinięty akordeon Terminy |

Zasady z UI, których agent nie może łamać:

- Spotkanie **musi mieć godzinę** (zakaz `all_day`).
- Spotkanie **nie wchodzi do sesji** (`skipped` z powodem).
- Zamknięte WI znikają z siatki (tylko `pending` / `in_progress`).
- **Ghost / dług:** dzień slotu `< dziś`, WI nadal otwarte. To nie jest
  cały backlog niedokończonych — tylko to, co wisi na wczorajszej godzinie
  (klocek, sesja z otwartymi członkami, spotkanie).
- Przesunięcie długu = `move_plan_block` na **istniejącym** `block_id`
  (spotkanie: `work_item_id`). `schedule_plan_item` na już zaplanowanym WI
  **doda drugi blok**.
- `create_task` z `starts_at` = **spotkanie na karcie**, nie klocek WI.
- `update_task` nie rusza `work_item_time_blocks`.
- Odpinanie kasuje slot, nie kartę. Sesja znika cała; WI w worku zostają.
- Snap 15 min. Domyślna długość bez `ends_at`: 30 min.
- Occupancy maluje spotkanie u **assignee i uczestników** (`participant_ids`).

Timesheet na sesjach **nie** jest w MCP (osobny temat).

---

## 6. HITL (`confirmed_by_user`)

Każde narzędzie **zapisujące** wymaga `confirmed_by_user: true`.

Obowiązkowy rytm:

1. Odczyt.
2. Propozycja po polsku: kto, jakie ID, stary slot → nowy, co pominięte.
   Linki `[#ID Tytuł](url)` z pola `url` narzędzia — nigdy gołe „zadanie 727”.
3. Czekaj na wyraźną zgodę człowieka.
4. To samo narzędzie z `confirmed_by_user: true`.

`false` albo brak flagi → błąd w stylu
„Zapis wstrzymany: brak potwierdzenia…”, **zero** mutacji.

**Nie ustawiaj flagi z własnej inicjatywy** — nawet gdy prośba brzmi
„po prostu zrób”.

---

## 7. Narzędzia Planu

### 7.1 `plan_occupancy` (odczyt, `IsReadOnly`)

Kalendarz tygodnia Pn–Nd albo jednego dnia.

| argument | znaczenie |
|---|---|
| `assigned_to` / `assignee_name` / `assigned_to_me` | osoba kalendarza |
| `date` | jeden dzień `YYYY-MM-DD` |
| `week` | dowolna data w tygodniu |
| `period` | `this_week` \| `last_week` |
| `debt_only` | tylko dni `< dziś` z eventami |

Domyślnie: kalendarz aktora, bieżący tydzień (`Carbon::now()`).

Odpowiedź (skrót):

```json
{
  "meta": {
    "calendar_user": { "id": 2, "name": "Anna" },
    "today": "2026-09-17",
    "week_start": "2026-09-14",
    "week_end": "2026-09-20",
    "plan_url": "https://…/plan?w=2026-09-14&u=2",
    "debt_event_count": 1,
    "day_count": 7
  },
  "days": [
    {
      "date": "2026-09-16",
      "debt": true,
      "timed": [
        {
          "key": "block:88",
          "kind": "block",
          "block_id": 88,
          "work_item_id": 410,
          "title": "Kompletacja",
          "url": "https://…",
          "ghost": true,
          "is_session": false,
          "starts_at": "…",
          "ends_at": "…",
          "all_day": false,
          "time_label": "10:00–10:30",
          "members": []
        }
      ],
      "all_day": []
    }
  ]
}
```

- `ghost: true` i `days[].debt: true` = strefa długu.
- `debt_only: true` wycina dziś i przyszłość oraz puste dni przeszłości.
- Spotkanie: `kind: "meeting"`, `block_id: null`, `key: "meeting:{work_item_id}"`.
- Sesja: `is_session: true`, `members[]` = otwarte WI w worku
  (`id`, `title`, `url`, …).
- `plan_url` otwiera ten sam tydzień i osobę co UI (`?w=` + `?u=`).

Kolejka bez slotu ≥ dziś: **`plan_queue`**, nie occupancy.

### 7.2 `plan_queue` (odczyt)

Lewa kolumna `/plan`. Filtr jak `WorkItemPlanService::applyQueueConstraints`:

- `assignee_id` = kalendarz
- status otwarty
- **ukryte**, gdy mają blok / członkostwo sesji / `starts_at` spotkania
  z dniem **≥ dziś**
- wczorajszy ghost **wraca** do kolejki, jeśli nie ma slotu od dziś

| argument | znaczenie |
|---|---|
| osoba | jak occupancy |
| `category` | dokładna nazwa (`Bug / UI`, nie fuzzy) |
| `q` | fragment tytułu |
| `limit` | domyślnie 80, max `MCP_MAX_SEARCH_RESULTS` |

Odpowiedź: `meta` (osoba, `today`, `returned`, `total_matching`, `plan_url`)
+ `items[]` w kształcie `search_work_items` (`work_item_id`, `url`,
`next.tool` → `get_task` / `get_procedure_run`, …).

„Wszystko z Bug / UI bez bloku” = `plan_queue` + `category` + osoba.

### 7.3 `schedule_plan_item` (zapis, HITL, destructive)

Pin z kolejki. Tworzy **nowy** blok albo ustawia godzinę spotkania
(`placeFromQueue`).

Wymagane: `work_item_id`, `starts_at`, `confirmed_by_user`.
Opcjonalnie `ends_at`, `all_day` (zakazane dla spotkań), osoba kalendarza.

Assignee WI musi = kalendarz. Inaczej błąd, zero zapisu.

ISO albo `YYYY-MM-DD HH:MM`.

### 7.4 `move_plan_block` (zapis, HITL, idempotent)

Istniejący slot.

- klocek i sesja: `block_id` (musi należeć do `user_id` kalendarza)
- spotkanie: `work_item_id` (`kind=meeting`)

| co podasz | skutek |
|---|---|
| `starts_at` | przesuń, zachowaj długość (całodniowy → timed = 30 min) |
| `all_day: true` | cały dzień (nie spotkania) |
| samo `ends_at` | resize końca |
| oba | przesuń, potem ustaw koniec |

Dług: ten sam `block_id`, `starts_at` dzisiaj lub później.

### 7.5 `unschedule_plan_block` (zapis, HITL)

Zdejmuje slot. WI zostaje (wraca do kolejki, jeśli nie ma innego slotu ≥ dziś).

- `block_id` — DELETE bloku / całej sesji
- `work_item_id` spotkania — `starts_at`/`ends_at` = null na karcie

Odpowiedź: `removed` (snapshot) + `meta.plan_url`.

### 7.6 `create_plan_session` (zapis, HITL)

Worek. Max 30 `work_item_ids`.

- **Nowa:** `title`, `starts_at`, opcjonalnie `ends_at` / `all_day`, lista WI.
- **Istniejąca:** `block_id` sesji + `work_item_ids` (+ `title` = rename).

Pętla dokładania:

| warunek | `skipped[].reason` |
|---|---|
| brak WI | `nie istnieje` |
| spotkanie | `spotkanie nie wchodzi do sesji` |
| inny assignee | `inny assignee niż kalendarz` |
| zamknięte | `zamknięte` |

`added[]` to to, co weszło. `session.open_count` = otwarte w worku po zapisie.

---

## 8. Playbooki Planu (głos / czat)

Zawsze: odczyt → propozycja → zgoda → zapis.

### Sprawdź mój dzień

1. `plan_occupancy` `{ "date": "YYYY-MM-DD" }` albo bez daty (tydzień).
2. `plan_queue`.
3. Opowiedz: timed, sesje, spotkania, ghost, kolejka. Wklej `plan_url`.

### Sprawdź dzień Ani

`list_users` `{ "q": "Anna" }` → occupancy / queue z `assignee_name`.

### Zwin dług

1. `plan_occupancy` `{ "debt_only": true, "assignee_name": "…" }`.
2. Propozycja: który `block_id` / meeting `work_item_id` na który slot
   dziś/jutro (kolizje z occupancy — agent tylko **mówi**, nie rozstrzyga
   overlapu w kodzie).
3. Po zgodzie `move_plan_block` per klocek. Nie `schedule_plan_item`.

### Kategoria X bez godziny → slot albo sesja

1. `plan_queue` `{ "category": "Bug / UI", "assignee_name": "…" }`.
2. Jeden WI → `schedule_plan_item`.
3. Kilka nie-spotkań → `create_plan_session` z `work_item_ids`, nazwą i slotem.

### Przesuń / skróć / odpinaj

Occupancy → `move_plan_block` albo `unschedule_plan_block`.

### Czego nie wołać „żeby zaplanować”

- `update_task` / `create_task` z `starts_at` — to spotkanie na karcie,
  nie klocek WI.
- `search_work_items` jako occupancy — nie ma tam godzin bloków.
- Drugi `schedule_plan_item` na WI, które już ma `block_id`.

---

## 9. Przykłady JSON-RPC (HTTP)

Lista narzędzi:

```http
POST /mcp/tasks
Content-Type: application/json
Authorization: Bearer <access_token>

{
  "jsonrpc": "2.0",
  "id": 1,
  "method": "tools/list"
}
```

Dzień Ani:

```json
{
  "jsonrpc": "2.0",
  "id": 2,
  "method": "tools/call",
  "params": {
    "name": "plan_occupancy",
    "arguments": {
      "assignee_name": "Anna",
      "date": "2026-09-17"
    }
  }
}
```

Przesunięcie **po zgodzie**:

```json
{
  "jsonrpc": "2.0",
  "id": 3,
  "method": "tools/call",
  "params": {
    "name": "move_plan_block",
    "arguments": {
      "block_id": "88",
      "starts_at": "2026-09-17 10:00",
      "assignee_name": "Anna",
      "confirmed_by_user": true
    }
  }
}
```

Sesja z kolejki:

```json
{
  "jsonrpc": "2.0",
  "id": 4,
  "method": "tools/call",
  "params": {
    "name": "create_plan_session",
    "arguments": {
      "title": "Dzwonienie",
      "starts_at": "2026-09-18 13:00",
      "ends_at": "2026-09-18 14:00",
      "work_item_ids": ["410", "411"],
      "assignee_name": "Anna",
      "confirmed_by_user": true
    }
  }
}
```

---

## 10. Pozostałe narzędzia (skrót)

Pełne opisy są w `TasksServer::$instructions` i w `description` z
`tools/list`. HITL = `confirmed_by_user: true`.

### Odczyt

| narzędzie | po co |
|---|---|
| `period_analytics` | KPI, współpraca, hottest, stale — **bez** ciał komentarzy |
| `search_tasks` | tylko karty `project_tasks` |
| `search_work_items` | siatka typów (spotkania, procedury, zatwierdzenia, …) |
| `get_task` | jedna karta + podzadania |
| `get_task_comments` | wątek zadania |
| `search_posts` / `get_post` / `get_post_comments` / `list_post_tags` | tablica |
| `list_users` | id i nazwy do przypisań i `@` |
| `list_categories` | słownik kategorii |
| `sprint_insights` | zdrowie sprintu + checklisty |
| `tasks_without_category` | otwarte bez kategorii |
| `backlog_overview` | backlog + sprinty |
| `tasks_in_period` | pełny dump — unikaj, gdy wystarczy analityka |
| `list_procedure_templates` / `list_procedure_runs` / `get_procedure_run` | SOP |

### Zapis (HITL)

| narzędzie | po co |
|---|---|
| `set_task_categories` | kategorie (nie `update_task`) |
| `update_task` | nazwa, opis, status, assignee, due, priorytet, zdjęcie ze sprintu |
| `update_subtask` / `add_subtasks` | checklista karty |
| `add_comment` | zadanie / post / sprint; `@Anna` / `@Anna!` / `@Anna?` |
| `create_post` / `update_post` | wątki tablicy (tekst + tagi, bez załączników) |
| `create_task` | nowa karta; z `starts_at` = spotkanie |
| `create_sprint` / `assign_tasks_to_sprint` | sprint |
| `add_sprint_checklist_item` / `update_sprint_checklist_item` | ready / done / milestone |
| `start_procedure` / `advance_procedure` | SOP (`begin`, `edge_id`, `checklist`, `back`, `abandon`) |

Kroku **approval** w procedurze asystent nie domyka.

---

## 11. Zmienne środowiska

Z `.env.example`:

| zmienna | znaczenie |
|---|---|
| `MCP_ACTOR_USER_ID` | aktor stdio |
| `MCP_MAX_TASKS_PER_BUNDLE` | limit paczek odczytu (200) |
| `MCP_MAX_CATEGORY_ASSIGNMENTS` | 50 |
| `MCP_MAX_SUBTASKS_PER_TASK` | 30 |
| `MCP_MAX_SPRINT_ASSIGNMENTS` | 50 |
| `MCP_MAX_SEARCH_RESULTS` | 200 (`plan_queue.limit`) |
| `MCP_OAUTH_REDIRECT_DOMAINS` | `*` albo lista originów |
| `PASSPORT_PRIVATE_KEY` / `PASSPORT_PUBLIC_KEY` | opcjonalnie zamiast plików |

---

## 12. Testy

```bash
docker exec -e XDEBUG_MODE=off delegacje-laravel.test-1 php artisan test --filter=McpPlanToolsTest
docker exec -e XDEBUG_MODE=off delegacje-laravel.test-1 php artisan test --filter=McpHttpServerTest
```

- `tests/Feature/McpPlanToolsTest.php` — occupancy, dług, kolejka, HITL,
  pin, move, unschedule, sesja (skip spotkań), przesunięcie spotkania.
- `tests/Feature/McpHttpServerTest.php` — discovery OAuth, 401, DCR,
  `initialize`, `tools/list` zawiera sześć nazw Planu, PKCE + refresh.
- Inne MCP: `McpTaskToolsTest`, `McpForumToolsTest`, …

Nie odpalaj dwóch PHPUnit naraz na bazie `testing` — migracje się gryzą
(`oauth_key_pairs already exists`). W razie korupcji:
`DROP DATABASE testing; CREATE DATABASE testing;` i ponów test.

Pint: `./vendor/bin/pint --dirty`.
