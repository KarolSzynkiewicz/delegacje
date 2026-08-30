<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Client;
use Laravel\Passport\Passport;
use Tests\TestCase;

class McpHttpServerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! file_exists(storage_path('oauth-private.key'))) {
            $this->artisan('passport:keys', ['--length' => 2048]);
        }
    }

    public function test_oauth_discovery_endpoints_are_public(): void
    {
        $this->getJson('/.well-known/oauth-protected-resource/mcp/tasks')
            ->assertOk()
            ->assertJsonPath('scopes_supported.0', 'mcp:use')
            ->assertJsonPath('resource', url('/mcp/tasks'));

        $this->getJson('/.well-known/oauth-authorization-server')
            ->assertOk()
            ->assertJsonPath('registration_endpoint', url('/oauth/register'))
            ->assertJsonPath('authorization_endpoint', route('passport.authorizations.authorize'))
            ->assertJsonPath('token_endpoint', route('passport.token'));
    }

    public function test_unauthenticated_mcp_request_returns_401_with_www_authenticate(): void
    {
        $this->postJson('/mcp/tasks', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
            'params' => [
                'protocolVersion' => '2025-06-18',
                'capabilities' => [],
                'clientInfo' => ['name' => 'phpunit', 'version' => '1.0'],
            ],
        ])
            ->assertUnauthorized()
            ->assertHeader('WWW-Authenticate');
    }

    public function test_dynamic_client_registration_creates_public_pkce_client(): void
    {
        $response = $this->postJson('/oauth/register', [
            'client_name' => 'Grok',
            'redirect_uris' => ['https://grok.com/oauth/callback'],
        ]);

        $response->assertCreated()
            ->assertJsonPath('token_endpoint_auth_method', 'none')
            ->assertJsonPath('grant_types.0', 'authorization_code');

        $this->assertDatabaseHas('oauth_clients', [
            'name' => 'Grok',
            'password_client' => 0,
            'personal_access_client' => 0,
        ]);

        $client = Client::query()->where('name', 'Grok')->first();
        $this->assertNotNull($client);
        $this->assertNull($client->secret);
    }

    public function test_authenticated_mcp_initialize_returns_server_info(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user, ['mcp:use']);

        $response = $this->postJson('/mcp/tasks', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
            'params' => [
                'protocolVersion' => '2025-06-18',
                'capabilities' => [],
                'clientInfo' => ['name' => 'phpunit', 'version' => '1.0'],
            ],
        ]);

        $response->assertOk();
        $payload = $response->json();
        $this->assertSame('2.0', $payload['jsonrpc'] ?? null);
        $this->assertSame('ChronoLogic Tasks', data_get($payload, 'result.serverInfo.name'));
    }

    public function test_authenticated_mcp_lists_task_tools(): void
    {
        $user = User::factory()->create();
        Passport::actingAs($user, ['mcp:use']);

        $response = $this->postJson('/mcp/tasks', [
            'jsonrpc' => '2.0',
            'id' => 2,
            'method' => 'tools/list',
        ]);

        $response->assertOk();
        $names = collect(data_get($response->json(), 'result.tools', []))
            ->pluck('name')
            ->all();

        $this->assertContains('create_task', $names);
        $this->assertContains('backlog_overview', $names);
        $this->assertContains('tasks_in_period', $names);
    }
}
