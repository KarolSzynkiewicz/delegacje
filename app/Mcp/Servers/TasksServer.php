<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\AddCommentTool;
use App\Mcp\Tools\AddSprintChecklistItemTool;
use App\Mcp\Tools\AddSubtasksTool;
use App\Mcp\Tools\AdvanceProcedureTool;
use App\Mcp\Tools\AssignTasksToSprintTool;
use App\Mcp\Tools\BacklogOverviewTool;
use App\Mcp\Tools\CreatePlanSessionTool;
use App\Mcp\Tools\CreatePostTool;
use App\Mcp\Tools\CreateSprintTool;
use App\Mcp\Tools\CreateTaskTool;
use App\Mcp\Tools\GetPostCommentsTool;
use App\Mcp\Tools\GetPostTool;
use App\Mcp\Tools\GetProcedureRunTool;
use App\Mcp\Tools\GetTaskCommentsTool;
use App\Mcp\Tools\GetTaskTool;
use App\Mcp\Tools\ListCategoriesTool;
use App\Mcp\Tools\ListPostTagsTool;
use App\Mcp\Tools\ListProcedureRunsTool;
use App\Mcp\Tools\ListProcedureTemplatesTool;
use App\Mcp\Tools\ListUsersTool;
use App\Mcp\Tools\MovePlanBlockTool;
use App\Mcp\Tools\PeriodAnalyticsTool;
use App\Mcp\Tools\PlanOccupancyTool;
use App\Mcp\Tools\PlanQueueTool;
use App\Mcp\Tools\SchedulePlanItemTool;
use App\Mcp\Tools\SearchPostsTool;
use App\Mcp\Tools\SearchTasksTool;
use App\Mcp\Tools\SearchWorkItemsTool;
use App\Mcp\Tools\SetTaskCategoriesTool;
use App\Mcp\Tools\SprintInsightsTool;
use App\Mcp\Tools\StartProcedureTool;
use App\Mcp\Tools\TasksInPeriodTool;
use App\Mcp\Tools\TasksWithoutCategoryTool;
use App\Mcp\Tools\UnschedulePlanBlockTool;
use App\Mcp\Tools\UpdatePostTool;
use App\Mcp\Tools\UpdateSprintChecklistItemTool;
use App\Mcp\Tools\UpdateSubtaskTool;
use App\Mcp\Tools\UpdateTaskTool;
use Laravel\Mcp\Server;

class TasksServer extends Server
{
    protected string $name = 'ChronoLogic Tasks';

    protected string $version = '0.9.0';

    protected string $instructions = <<<'MARKDOWN'
        Serwer daje dostęp do zadań, sprintów, procedur, backlogu, tablicy
        i kalendarza Planu (`/plan`) ChronoLogic.

        # Odczyt

        - `period_analytics` – KPI i współpraca za okres (bez ciał komentarzy).
        - `search_tasks` – tylko karty `project_tasks`.
        - `search_work_items` – siatka typów (spotkania, procedury, zatwierdzenia, …).
        - `get_task` – jedna karta z opisem i podzadaniami.
        - `get_task_comments` – wątek komentarzy jednego zadania.
        - `search_posts` – karty wątków tablicy (bez obrazków).
        - `get_post` – jeden wątek, sam tekst + tagi.
        - `get_post_comments` – komentarze wątku tablicy.
        - `list_post_tags` – słownik tagów tablicy.
        - `list_users` – id i nazwy do przypisań i @wzmianek.
        - `list_categories` – słownik kategorii (bez kart zadań).
        - `sprint_insights` – zdrowie sprintu i checklisty (start / done / kamienie).
        - `tasks_without_category` – otwarte bez kategorii + słownik.
        - `backlog_overview` – backlog i lista sprintów.
        - `tasks_in_period` – pełny dump; unikaj, gdy wystarczy analityka.
        - `list_procedure_templates` – templatki SOP + ile aktywnych runów.
        - `list_procedure_runs` – przebiegi (domyślnie w trakcie).
        - `get_procedure_run` – aktualny krok i `prompt` do przeczytania na głos.
        - `plan_occupancy` – bloki, sesje i spotkania osoby w tygodniu / dniu.
          `ghost: true` = strefa długu (slot sprzed dziś, WI otwarte).
        - `plan_queue` – „Do przypięcia”: otwarte WI bez slotu ≥ dziś.

        # Zapis (HITL, `confirmed_by_user: true`)

        - `set_task_categories` – kategorie.
        - `update_task` – nazwa, opis, status, assignee, due, priorytet,
          zdjęcie ze sprintu (jedno zadanie).
        - `update_subtask` – odhaczenie / nazwa / osoba na jednym kroku.
        - `add_subtasks` – nowe kroki checklisty.
        - `add_comment` – komentarz do zadania, posta albo sprintu.
          `@Anna` wzmianka, `@Anna!` zadanie, `@Anna?` zatwierdzenie.
        - `create_post` / `update_post` – wątki tablicy (tylko tekst i tagi).
        - `create_task` – zadanie; z `starts_at` robi się spotkanie.
        - `create_sprint` / `assign_tasks_to_sprint`.
        - `add_sprint_checklist_item` / `update_sprint_checklist_item` –
          warunki startu, ukończenia i kamienie.
        - `start_procedure` / `advance_procedure` – odpalenie i krok procedury
          (`begin`, `back`, `abandon`).
        - `schedule_plan_item` – pin z kolejki na slot (nowy blok / godzina
          spotkania). Nie przesuwa istniejącego klocka.
        - `move_plan_block` – przesuń / zmień koniec (`block_id` albo
          `work_item_id` spotkania). Dług: ten sam block_id, nowy starts_at.
        - `unschedule_plan_block` – zdejmij slot; WI zostaje.
        - `create_plan_session` – nowy worek albo dokładka `work_item_ids`
          do istniejącej sesji. Spotkania pomijane.

        # Przepływy

        Hygiene kategorii: `list_categories` + `search_tasks`
        (missing_category) albo `tasks_without_category` → propozycja →
        `set_task_categories`.

        Hygiene przypisań: `search_tasks` (unassigned) + `list_users` →
        `update_task`.

        Raport okresu: `period_analytics` → dla 3–5 ID z hottest/stale
        `get_task` + `get_task_comments` → proza. Opcjonalnie `add_comment`
        na stale po zgodzie.

        Taski / work itemy osoby: `search_work_items` z `assignee_name` /
        `assigned_to_me` / `type`. Czyste karty zadań: `search_tasks`.

        Sprint: `backlog_overview` → propozycja (cel, co trzeba by zacząć,
        kiedy zrobione, kamienie, daty) → `create_sprint` /
        `assign_tasks_to_sprint` / `create_task`. Wypadające: `update_task`
        z `unassign_sprint`. W trakcie: `sprint_insights` → odhaczanie
        `update_sprint_checklist_item`. Komentarz: `add_comment` + `sprint_id`.

        Checklist zadania: `get_task` → `update_subtask` / `add_subtasks`.

        Spotkanie: `create_task` z `starts_at` / `ends_at` / `location` /
        `participant_ids`.

        Zatwierdzenie / zadanie z komentarza: `add_comment` z `@Anna?` / `@Anna!`
        na `task_id`, `post_id` albo `sprint_id`.

        Procedura głosem: `list_procedure_templates` (ile aktywnych runów) →
        `list_procedure_runs` (czy już leci) → `start_procedure` albo
        `get_procedure_run` (czytaj `prompt`) → `advance_procedure`.
        Kroku approval nie da się domknąć asystentem.

        Tablica: `list_post_tags` / `search_posts` → `get_post` →
        `add_comment` z `post_id`. Nowy wątek: `create_post`.

        Plan / dzień: `plan_occupancy` (osoba + date/week) + `plan_queue`.
        Dług: occupancy `debt_only` → `move_plan_block`.
        Kategoria bez slotu: `plan_queue` + `category` → `schedule_plan_item`
        albo `create_plan_session`.
        `update_task` / `create_task` z `starts_at` NIE rusza bloków WI
        (starts_at na create_task = spotkanie na karcie).

        # Zasada nadrzędna

        Nie zmieniaj danych bez wyraźnej zgody. Nie ustawiaj
        `confirmed_by_user` z własnej inicjatywy. Nie mutuj przez tinker,
        shell ani SQL. Tablica przez MCP bez załączników.

        # Linki

        Każde zadanie, wątek tablicy i sprint w odpowiedzi narzędzi ma `url`.
        Gdy o nich mówisz, ZAWSZE wstaw markdown `[#ID Nazwa](url)` z tego pola.
        Nie pisz gołego „zadanie 727” ani samego ID. To samo dla postów
        i sprintów. Przykład: [#727 Rotacje](https://app/tasks/727).

        # Język

        Odpowiadaj po polsku. Nazwy zadań, postów i kategorii cytuj
        oryginalnie i z ID, zawsze jako link.
    MARKDOWN;

    /**
     * @var array<int, class-string<\Laravel\Mcp\Server\Tool>>
     */
    protected array $tools = [
        PeriodAnalyticsTool::class,
        SearchWorkItemsTool::class,
        SearchTasksTool::class,
        GetTaskTool::class,
        GetTaskCommentsTool::class,
        SearchPostsTool::class,
        GetPostTool::class,
        GetPostCommentsTool::class,
        ListPostTagsTool::class,
        ListUsersTool::class,
        ListCategoriesTool::class,
        SprintInsightsTool::class,
        TasksInPeriodTool::class,
        TasksWithoutCategoryTool::class,
        BacklogOverviewTool::class,
        SetTaskCategoriesTool::class,
        UpdateTaskTool::class,
        UpdateSubtaskTool::class,
        AddSubtasksTool::class,
        AddCommentTool::class,
        CreatePostTool::class,
        UpdatePostTool::class,
        CreateTaskTool::class,
        CreateSprintTool::class,
        AddSprintChecklistItemTool::class,
        UpdateSprintChecklistItemTool::class,
        AssignTasksToSprintTool::class,
        ListProcedureTemplatesTool::class,
        ListProcedureRunsTool::class,
        GetProcedureRunTool::class,
        StartProcedureTool::class,
        AdvanceProcedureTool::class,
        PlanOccupancyTool::class,
        PlanQueueTool::class,
        SchedulePlanItemTool::class,
        MovePlanBlockTool::class,
        UnschedulePlanBlockTool::class,
        CreatePlanSessionTool::class,
    ];
}
