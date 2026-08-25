<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Użytkownik wykonujący operacje MCP
    |--------------------------------------------------------------------------
    |
    | Lokalny serwer MCP działa po stdio, poza sesją HTTP, więc nie ma
    | zalogowanego użytkownika. Narzędzia logują się jako ten użytkownik,
    | żeby polityki, uprawnienia i pola audytowe działały tak samo jak w UI.
    |
    */

    'actor_user_id' => env('MCP_ACTOR_USER_ID'),

    /*
    |--------------------------------------------------------------------------
    | Limity narzędzi
    |--------------------------------------------------------------------------
    */

    'max_tasks_per_bundle' => (int) env('MCP_MAX_TASKS_PER_BUNDLE', 200),

    'max_category_assignments' => (int) env('MCP_MAX_CATEGORY_ASSIGNMENTS', 50),

    'max_subtasks_per_task' => (int) env('MCP_MAX_SUBTASKS_PER_TASK', 30),

    'max_sprint_assignments' => (int) env('MCP_MAX_SPRINT_ASSIGNMENTS', 50),

];
