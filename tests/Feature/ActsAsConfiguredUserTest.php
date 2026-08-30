<?php

namespace Tests\Feature;

use App\Mcp\Concerns\ActsAsConfiguredUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class ActsAsConfiguredUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_prefers_authenticated_user_over_env_actor(): void
    {
        $oauthUser = User::factory()->create(['name' => 'OAuth User']);
        $envUser = User::factory()->create(['name' => 'Env Actor']);

        config(['ai_tools.actor_user_id' => $envUser->id]);
        Auth::setUser($oauthUser);

        $actor = (new class
        {
            use ActsAsConfiguredUser;

            public function resolve(): User
            {
                return $this->actingUser();
            }
        })->resolve();

        $this->assertTrue($actor->is($oauthUser));
    }

    public function test_falls_back_to_env_actor_without_session(): void
    {
        $envUser = User::factory()->create();
        config(['ai_tools.actor_user_id' => $envUser->id]);
        Auth::forgetGuards();

        $actor = (new class
        {
            use ActsAsConfiguredUser;

            public function resolve(): User
            {
                return $this->actingUser();
            }
        })->resolve();

        $this->assertTrue($actor->is($envUser));
    }
}
