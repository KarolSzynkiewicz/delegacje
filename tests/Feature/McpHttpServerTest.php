<?php

namespace Tests\Feature;

use App\Mcp\Support\PassportKeyStore;
use App\Models\OAuthKeyPair;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
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
            ->assertJsonPath('token_endpoint', route('passport.token'))
            ->assertJsonPath('grant_types_supported.1', 'refresh_token')
            ->assertJsonPath('token_endpoint_auth_methods_supported.0', 'none');
    }

    public function test_unauthenticated_mcp_request_returns_401_with_www_authenticate(): void
    {
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

        $response->assertUnauthorized()->assertHeader('WWW-Authenticate');
        $this->assertStringContainsString('invalid_token', (string) $response->headers->get('WWW-Authenticate'));
        $this->assertStringContainsString('resource_metadata=', (string) $response->headers->get('WWW-Authenticate'));
    }

    public function test_dynamic_client_registration_creates_public_pkce_client(): void
    {
        $response = $this->postJson('/oauth/register', [
            'client_name' => 'Grok',
            'redirect_uris' => ['https://grok.com/oauth/callback'],
        ]);

        $response->assertCreated()
            ->assertJsonPath('token_endpoint_auth_method', 'none')
            ->assertJsonPath('grant_types.0', 'authorization_code')
            ->assertJsonPath('grant_types.1', 'refresh_token');

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

    public function test_pkce_token_response_includes_refresh_token_and_authorizes_mcp(): void
    {
        [$tokens] = $this->issueMcpTokens();

        $this->assertNotEmpty($tokens['access_token']);
        $this->assertNotEmpty($tokens['refresh_token']);
        $this->assertSame('Bearer', $tokens['token_type']);
        $this->assertGreaterThan(86400, $tokens['expires_in']);

        $this->withToken($tokens['access_token'])
            ->postJson('/mcp/tasks', [
                'jsonrpc' => '2.0',
                'id' => 1,
                'method' => 'initialize',
                'params' => [
                    'protocolVersion' => '2025-06-18',
                    'capabilities' => [],
                    'clientInfo' => ['name' => 'phpunit', 'version' => '1.0'],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('result.serverInfo.name', 'ChronoLogic Tasks');
    }

    public function test_public_client_can_refresh_with_form_and_json_and_reuse_old_refresh_token(): void
    {
        [$tokens, $clientId] = $this->issueMcpTokens();
        $originalRefresh = $tokens['refresh_token'];

        $formRefresh = $this->post('/oauth/token', [
            'grant_type' => 'refresh_token',
            'client_id' => $clientId,
            'refresh_token' => $originalRefresh,
            'resource' => url('/mcp/tasks'),
        ]);

        $formRefresh->assertOk();
        $formRefresh->assertJsonStructure(['access_token', 'refresh_token', 'expires_in']);
        $this->assertNotSame($tokens['access_token'], $formRefresh->json('access_token'));

        $jsonRefresh = $this->postJson('/oauth/token', [
            'grant_type' => 'refresh_token',
            'client_id' => $clientId,
            'refresh_token' => $originalRefresh,
            'resource' => url('/mcp/tasks'),
        ]);

        $jsonRefresh->assertOk();
        $this->assertNotEmpty($jsonRefresh->json('access_token'));

        $this->withToken($jsonRefresh->json('access_token'))
            ->postJson('/mcp/tasks', [
                'jsonrpc' => '2.0',
                'id' => 3,
                'method' => 'tools/list',
            ])
            ->assertOk();
    }

    public function test_passport_keys_survive_file_loss_from_database(): void
    {
        $this->artisan('mcp:ensure-passport-keys')->assertSuccessful();

        $this->assertDatabaseCount('oauth_key_pairs', 1);

        $privatePath = storage_path('oauth-private.key');
        $publicPath = storage_path('oauth-public.key');
        $private = (string) file_get_contents($privatePath);
        $public = (string) file_get_contents($publicPath);

        try {
            unlink($privatePath);
            unlink($publicPath);

            $this->assertFalse(file_exists($privatePath));
            $this->assertSame('database', PassportKeyStore::ensure());

            $this->assertFileExists($privatePath);
            $this->assertSame($private, file_get_contents($privatePath));
            $this->assertSame($public, file_get_contents($publicPath));
            $this->assertSame($private, OAuthKeyPair::query()->first()?->private_key);
        } finally {
            if (! is_file($privatePath)) {
                file_put_contents($privatePath, $private);
            }
            if (! is_file($publicPath)) {
                file_put_contents($publicPath, $public);
            }
        }
    }

    /**
     * @return array{0: array{access_token: string, refresh_token: string, token_type: string, expires_in: int}, 1: string}
     */
    protected function issueMcpTokens(): array
    {
        $user = User::factory()->create();
        $redirectUri = 'https://grok.com/oauth/callback';

        $registration = $this->postJson('/oauth/register', [
            'client_name' => 'Grok Test',
            'redirect_uris' => [$redirectUri],
        ])->assertCreated();

        $clientId = (string) $registration->json('client_id');
        $state = Str::random(40);
        $verifier = Str::random(64);
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

        $this->actingAs($user)->get(route('passport.authorizations.authorize', [
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'mcp:use',
            'state' => $state,
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
            'resource' => url('/mcp/tasks'),
        ]))->assertOk();

        $approve = $this->actingAs($user)->post(route('passport.authorizations.approve'), [
            'state' => $state,
            'client_id' => $clientId,
            'auth_token' => session('authToken'),
        ]);

        $approve->assertRedirect();
        $location = (string) $approve->headers->get('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        $this->assertArrayHasKey('code', $query);

        $tokens = $this->post('/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'code' => $query['code'],
            'code_verifier' => $verifier,
            'resource' => url('/mcp/tasks'),
        ]);

        $tokens->assertOk()->assertJsonStructure(['access_token', 'refresh_token', 'token_type', 'expires_in']);

        return [$tokens->json(), $clientId];
    }
}
