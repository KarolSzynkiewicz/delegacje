<?php

use App\Mcp\Servers\TasksServer;
use App\Mcp\Support\McpOAuth;
use Laravel\Mcp\Facades\Mcp;

/*
|--------------------------------------------------------------------------
| AI / MCP Routes
|--------------------------------------------------------------------------
|
| Lokalny serwer (stdio) dla Cursora: php artisan mcp:start chrono-tasks.
| HTTP /mcp/tasks dla ChatGPT, Grok i innych klientów zdalnych (OAuth 2.1).
|
*/

Mcp::local('chrono-tasks', TasksServer::class);

McpOAuth::routes();

Mcp::web('/mcp/tasks', TasksServer::class)
    ->middleware(['auth:api', 'throttle:mcp']);
