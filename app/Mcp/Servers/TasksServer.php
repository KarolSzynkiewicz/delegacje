<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\AddCommentTool;
use App\Mcp\Tools\AssignTasksToSprintTool;
use App\Mcp\Tools\BacklogOverviewTool;
use App\Mcp\Tools\CreateSprintTool;
use App\Mcp\Tools\CreateTaskTool;
use App\Mcp\Tools\GetTaskCommentsTool;
use App\Mcp\Tools\GetTaskTool;
use App\Mcp\Tools\ListUsersTool;
use App\Mcp\Tools\PeriodAnalyticsTool;
use App\Mcp\Tools\SearchTasksTool;
use App\Mcp\Tools\SetTaskCategoriesTool;
use App\Mcp\Tools\SprintInsightsTool;
use App\Mcp\Tools\TasksInPeriodTool;
use App\Mcp\Tools\TasksWithoutCategoryTool;
use App\Mcp\Tools\UpdateTaskTool;
use Laravel\Mcp\Server;

class TasksServer extends Server
{
    protected string $name = 'ChronoLogic Tasks';

    protected string $version = '0.3.0';

    protected string $instructions = <<<'MARKDOWN'
        Serwer daje dostęp do zadań, sprintów i backlogu aplikacji ChronoLogic.

        # Odczyt

        - `period_analytics` – KPI i współpraca za okres (bez ciał komentarzy).
        - `search_tasks` – karty po osobie, kategorii, statusie, sprincie, hygiene.
        - `get_task` – jedna karta z opisem i podzadaniami.
        - `get_task_comments` – wątek komentarzy jednego zadania.
        - `list_users` – id i nazwy do przypisań i @wzmianek.
        - `sprint_insights` – zdrowie sprintu (jak tablica).
        - `tasks_without_category` – otwarte bez kategorii + słownik.
        - `backlog_overview` – backlog i lista sprintów.
        - `tasks_in_period` – pełny dump; unikaj, gdy wystarczy analityka.

        # Zapis (HITL, `confirmed_by_user: true`)

        - `set_task_categories` – kategorie.
        - `update_task` – status, assignee, due, priorytet (jedno zadanie).
        - `add_comment` – komentarz / @wzmianka / ping ownera.
        - `create_task` / `create_sprint` / `assign_tasks_to_sprint`.

        # Przepływy

        Hygiene kategorii: `search_tasks` (missing_category) albo
        `tasks_without_category` → propozycja → `set_task_categories`.

        Hygiene przypisań: `search_tasks` (unassigned) + `list_users` →
        `update_task`.

        Raport okresu: `period_analytics` → dla 3–5 ID z hottest/stale
        `get_task` + `get_task_comments` → proza. Opcjonalnie `add_comment`
        na stale po zgodzie.

        Taski osoby / kategorii: `search_tasks` z `assignee_name` / `assigned_to`
        / `category`.

        Sprint: `backlog_overview` → propozycja → `create_sprint` /
        `assign_tasks_to_sprint` / `create_task`. W trakcie: `sprint_insights`.

        # Zasada nadrzędna

        Nie zmieniaj danych bez wyraźnej zgody. Nie ustawiaj
        `confirmed_by_user` z własnej inicjatywy. Nie mutuj przez tinker,
        shell ani SQL.

        # Język

        Odpowiadaj po polsku. Nazwy zadań i kategorii cytuj oryginalnie i z ID.
    MARKDOWN;

    /**
     * @var array<int, class-string<\Laravel\Mcp\Server\Tool>>
     */
    protected array $tools = [
        PeriodAnalyticsTool::class,
        SearchTasksTool::class,
        GetTaskTool::class,
        GetTaskCommentsTool::class,
        ListUsersTool::class,
        SprintInsightsTool::class,
        TasksInPeriodTool::class,
        TasksWithoutCategoryTool::class,
        BacklogOverviewTool::class,
        SetTaskCategoriesTool::class,
        UpdateTaskTool::class,
        AddCommentTool::class,
        CreateTaskTool::class,
        CreateSprintTool::class,
        AssignTasksToSprintTool::class,
    ];
}
