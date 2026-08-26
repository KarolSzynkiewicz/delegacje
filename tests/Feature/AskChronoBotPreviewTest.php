<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AskChronoBotPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_ui_playground_renders_every_bot_and_brand_variant(): void
    {
        $this->artisan('db:seed', ['--class' => 'UserRoleSeeder']);
        $user = User::factory()->create();
        $user->assignRole(Role::where('name', 'administrator')->first());

        $response = $this->actingAs($user)->get('/2');

        $response->assertOk()->assertSee('AskChrono');

        foreach (['clock', 'visor', 'orb', 'spark'] as $variant) {
            $response->assertSee('ac-bot--v-'.$variant, false);
        }

        foreach (['dial', 'aperture', 'bot', 'monogram', 'pulse', 'timer'] as $variant) {
            $response->assertSee('cl-mark--'.$variant, false);
        }

        // Oś obrotu wskazówek musi jechać per wariant, inaczej zegar kręci się obok tarczy.
        $response->assertSee('--ac-pivot: 36px 65.5px', false);
        $response->assertSee('--cl-mark-pivot: 20px 24.5px', false);
        $response->assertSee('--cl-mark-pivot: 20px 21.5px', false);

        $response->assertSee('clBootScreen', false);
    }

    public function test_login_page_does_not_start_the_boot_screen_itself(): void
    {
        // Sekwencję odgrywa dopiero strona docelowa — gdyby startowała tutaj,
        // przeładowanie po POST restartowałoby ją w połowie.
        $this->get('/login')
            ->assertOk()
            ->assertDontSee('data-boot-screen', false)
            ->assertDontSee('clBootScreen', false);
    }

    public function test_successful_login_hands_the_boot_screen_to_the_destination_page(): void
    {
        $user = User::factory()->create(['password' => bcrypt('haslo-testowe')]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'haslo-testowe',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('cl_boot', true);
    }

    public function test_boot_screen_in_auto_mode_plays_the_whole_sequence(): void
    {
        $html = view('components.boot-screen', ['variant' => 'timer', 'auto' => true])->render();

        $this->assertStringContainsString('cl-boot--auto', $html);
        $this->assertStringContainsString('aria-hidden="false"', $html);
        $this->assertStringContainsString('cl-mark--timer', $html);
        $this->assertStringContainsString('Szykowanie agentów AI', $html);
    }

    public function test_application_logo_uses_the_monogram_mark(): void
    {
        $html = view('components.application-logo')->render();

        $this->assertStringContainsString('cl-mark--monogram', $html);
    }

    public function test_brand_mark_renders_unique_gradient_ids_per_instance(): void
    {
        $html = view('components.brand-mark', ['variant' => 'dial', 'size' => 40])->render()
            .view('components.brand-mark', ['variant' => 'dial', 'size' => 40])->render();

        preg_match_all('/id="(clm-[a-f0-9]+)"/', $html, $matches);

        $this->assertCount(2, $matches[1]);
        $this->assertNotSame($matches[1][0], $matches[1][1]);
    }
}
