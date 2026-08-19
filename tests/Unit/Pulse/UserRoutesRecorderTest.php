<?php

namespace Tests\Unit\Pulse;

use App\Pulse\Recorders\UserRoutes;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Laravel\Pulse\Entry;
use Laravel\Pulse\Pulse;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class UserRoutesRecorderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_records_user_method_and_route_pattern(): void
    {
        $entry = Mockery::mock(Entry::class);
        $entry->shouldReceive('count')->once()->andReturnSelf();

        $pulse = Mockery::mock(Pulse::class);
        $pulse->shouldReceive('resolveAuthenticatedUserId')->once()->andReturn(7);
        $pulse->shouldReceive('record')
            ->once()
            ->withArgs(function (string $type, string $key) {
                $this->assertSame('user_route', $type);
                $this->assertSame(['7', 'GET', '/projects/{project}'], json_decode($key, true, flags: JSON_THROW_ON_ERROR));

                return true;
            })
            ->andReturn($entry);

        $recorder = new UserRoutes($pulse);
        $recorder->record(now(), $this->requestWithRoute('GET', '/projects/15', 'projects/{project}'), new Response);
    }

    public function test_groups_numeric_ids_from_livewire_page_path(): void
    {
        $entry = Mockery::mock(Entry::class);
        $entry->shouldReceive('count')->once()->andReturnSelf();

        $pulse = Mockery::mock(Pulse::class);
        $pulse->shouldReceive('resolveAuthenticatedUserId')->once()->andReturn(7);
        $pulse->shouldReceive('record')
            ->once()
            ->withArgs(function (string $type, string $key) {
                $this->assertSame('user_route', $type);
                $this->assertSame(['7', 'POST', '/projects/{id}'], json_decode($key, true, flags: JSON_THROW_ON_ERROR));

                return true;
            })
            ->andReturn($entry);

        $snapshot = json_encode(['memo' => ['path' => '/projects/15']], flags: JSON_THROW_ON_ERROR);
        $request = Request::create('/livewire/update', 'POST', [
            'components' => [['snapshot' => $snapshot]],
        ]);
        $route = new Route(['POST'], 'livewire/update', fn () => null);
        $route->name('livewire.update');
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);

        $recorder = new UserRoutes($pulse);
        $recorder->record(now(), $request, new Response);
    }

    public function test_skips_guests(): void
    {
        $pulse = Mockery::mock(Pulse::class);
        $pulse->shouldReceive('resolveAuthenticatedUserId')->once()->andReturn(null);
        $pulse->shouldNotReceive('record');

        $recorder = new UserRoutes($pulse);
        $recorder->record(now(), $this->requestWithRoute('GET', '/projects', 'projects'), new Response);
    }

    public function test_ignores_pulse_dashboard(): void
    {
        $pulse = Mockery::mock(Pulse::class);
        $pulse->shouldReceive('resolveAuthenticatedUserId')->once()->andReturn(7);
        $pulse->shouldNotReceive('record');

        $recorder = new UserRoutes($pulse);
        $recorder->record(now(), $this->requestWithRoute('GET', '/pulse', 'pulse'), new Response);
    }

    private function requestWithRoute(string $method, string $url, string $uri): Request
    {
        $request = Request::create($url, $method);
        $route = new Route([$method], $uri, fn () => null);
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);

        return $request;
    }
}
