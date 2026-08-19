<?php

namespace App\Pulse\Recorders;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Carbon;
use Laravel\Pulse\Concerns\ConfiguresAfterResolving;
use Laravel\Pulse\Pulse;
use Laravel\Pulse\Recorders\Concerns\Groups;
use Laravel\Pulse\Recorders\Concerns\Ignores;
use Laravel\Pulse\Recorders\Concerns\LivewireRoutes;
use Laravel\Pulse\Recorders\Concerns\Sampling;
use Symfony\Component\HttpFoundation\Response;

class UserRoutes
{
    use ConfiguresAfterResolving;
    use Groups;
    use Ignores;
    use LivewireRoutes;
    use Sampling;

    public function __construct(
        protected Pulse $pulse,
    ) {}

    public function register(callable $record, Application $app): void
    {
        $this->afterResolving(
            $app,
            Kernel::class,
            fn (Kernel $kernel) => $kernel->whenRequestLifecycleIsLongerThan(-1, $record)
        );
    }

    public function record(Carbon $startedAt, Request $request, Response $response): void
    {
        if (
            ($userId = $this->pulse->resolveAuthenticatedUserId()) === null ||
            ! $request->route() instanceof Route ||
            ! $this->shouldSample()
        ) {
            return;
        }

        [$path] = $this->resolveRoutePath($request);

        if ($this->shouldIgnore($path)) {
            return;
        }

        $this->pulse->record(
            type: 'user_route',
            key: json_encode([(string) $userId, $request->method(), $this->group($path)], flags: JSON_THROW_ON_ERROR),
            timestamp: $startedAt->getTimestamp(),
        )->count();
    }
}
