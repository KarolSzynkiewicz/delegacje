<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\AssignTasksToSprintTool;
use App\Mcp\Tools\BacklogOverviewTool;
use App\Mcp\Tools\CreateSprintTool;
use App\Mcp\Tools\CreateTaskTool;
use App\Mcp\Tools\SetTaskCategoriesTool;
use App\Mcp\Tools\TasksInPeriodTool;
use App\Mcp\Tools\TasksWithoutCategoryTool;
use Laravel\Mcp\Server;

class TasksServer extends Server
{
    protected string $name = 'ChronoLogic Tasks';

    protected string $version = '0.2.0';

    protected string $instructions = <<<'MARKDOWN'
        Serwer daje dostęp do zadań, sprintów i backlogu aplikacji ChronoLogic.

        # Narzędzia czytające

        - `tasks_in_period` – zadania z okresu z podzadaniami i komentarzami.
        - `tasks_without_category` – zadania bez kategorii plus słownik kategorii.
        - `backlog_overview` – otwarty backlog i sprinty.

        # Narzędzia zapisujące (wymagają HITL)

        - `set_task_categories` – kategorie na istniejących zadaniach.
        - `create_task` – nowe zadanie z podzadaniami (opcjonalnie sprint_id).
        - `create_sprint` – nowy sprint.
        - `assign_tasks_to_sprint` – przypisanie istniejących zadań do sprintu.

        # Zasada nadrzędna: człowiek zatwierdza zmiany

        Nigdy nie zmieniaj danych bez wyraźnej zgody użytkownika. Zanim wywołasz
        narzędzie zapisujące:

        1. Pobierz dane narzędziem czytającym (jeśli potrzebne).
        2. Przedstaw propozycję użytkownikowi w czytelnej formie.
        3. Zapytaj wprost o akceptację.
        4. Dopiero po zgodzie wywołaj z `confirmed_by_user: true`.

        Nie ustawiaj `confirmed_by_user` z własnej inicjatywy.

        # Planowanie sprintu

        Typowy przepływ: `backlog_overview` → propozycja → `create_sprint` →
        `create_task` (z sprint_id) lub `assign_tasks_to_sprint` dla istniejących zadań.

        Nigdy nie używaj tinker, shell ani SQL do mutacji – wyłącznie narzędzia MCP.

        # Kategorie

        Kategoria to zwykły tekst, bez rejestru etykiet. Proponuj wartości ze
        słownika `known_categories`; nową nazwę wymyślaj tylko wtedy, gdy żadna
        istniejąca nie pasuje, i wyraźnie to zaznacz jako nową kategorię.

        # Język

        Dane i interfejs są po polsku – odpowiadaj po polsku, zachowując oryginalne
        nazwy zadań i kategorii.
    MARKDOWN;

    /**
     * @var array<int, class-string<\Laravel\Mcp\Server\Tool>>
     */
    protected array $tools = [
        TasksInPeriodTool::class,
        TasksWithoutCategoryTool::class,
        BacklogOverviewTool::class,
        SetTaskCategoriesTool::class,
        CreateTaskTool::class,
        CreateSprintTool::class,
        AssignTasksToSprintTool::class,
    ];
}
