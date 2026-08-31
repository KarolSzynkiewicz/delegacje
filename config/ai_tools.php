<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Użytkownik wykonujący operacje MCP
    |--------------------------------------------------------------------------
    |
    | Lokalny serwer MCP (stdio) nie ma sesji HTTP — narzędzia logują się
    | jako ten użytkownik. Przy HTTP /mcp/tasks obowiązuje użytkownik OAuth.
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

    'max_search_results' => (int) env('MCP_MAX_SEARCH_RESULTS', 200),

    'max_comments_per_thread' => (int) env('MCP_MAX_COMMENTS_PER_THREAD', 100),

];
