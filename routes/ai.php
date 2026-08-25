<?php

use App\Mcp\Servers\TasksServer;
use Laravel\Mcp\Facades\Mcp;

/*
|--------------------------------------------------------------------------
| AI / MCP Routes
|--------------------------------------------------------------------------
|
| Lokalne serwery MCP uruchamiane po stdio przez `php artisan mcp:start`.
| Konfiguracja klienta znajduje się w .cursor/mcp.json.
|
*/

Mcp::local('chrono-tasks', TasksServer::class);
