<?php

namespace Tests\Feature;

use App\Livewire\LlmProviderSettings;
use App\Models\LlmCredential;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LlmProviderSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->artisan('db:seed', ['--class' => 'UserRoleSeeder']);
        $user = User::factory()->create();
        $user->assignRole(Role::where('name', 'administrator')->first());

        return $user;
    }

    public function test_system_actions_page_renders_with_llm_card(): void
    {
        $this->actingAs($this->admin())
            ->get('/system-actions')
            ->assertOk()
            ->assertSee('Integracja AI');
    }

    public function test_saving_key_stores_it_encrypted_and_activates_provider(): void
    {
        Livewire::actingAs($this->admin())
            ->test(LlmProviderSettings::class)
            ->set('provider', 'gemini')
            ->set('apiKey', 'AIzaTESTKEY1234567890')
            ->set('model', 'gemini-2.5-flash')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('apiKey', '');

        $record = LlmCredential::firstWhere('provider', 'gemini');

        $this->assertTrue($record->is_active);
        $this->assertSame('AIzaTESTKEY1234567890', $record->api_key);
        $this->assertStringNotContainsString(
            'AIzaTESTKEY',
            \Illuminate\Support\Facades\DB::table('llm_credentials')->value('api_key')
        );
    }

    public function test_guest_cannot_touch_the_component(): void
    {
        Livewire::test(LlmProviderSettings::class)->assertForbidden();
    }
}
