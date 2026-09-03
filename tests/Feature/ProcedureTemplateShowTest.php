<?php

namespace Tests\Feature;

use App\Models\ProcedureRun;
use App\Models\ProcedureTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcedureTemplateShowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'UserRoleSeeder']);
    }

    protected function admin(): User
    {
        $user = User::factory()->create();
        $adminRole = \Spatie\Permission\Models\Role::where('name', 'administrator')->first();
        if ($adminRole) {
            $user->assignRole($adminRole);
        }

        return $user;
    }

    public function test_show_page_renders_with_steps_and_run_stats(): void
    {
        $template = ProcedureTemplate::query()->create([
            'name' => 'Onboarding testowy',
            'category' => 'HR',
            'description' => 'Opis procedury testowej.',
            'definition' => [
                'nodes' => [
                    ['id' => 'n1', 'type' => 'start', 'name' => 'Start', 'color' => '#3b82f6'],
                    ['id' => 'n2', 'type' => 'task', 'name' => 'Podpisz umowę', 'color' => '#a855f7'],
                    ['id' => 'n3', 'type' => 'end', 'name' => 'Koniec', 'color' => '#10b981'],
                ],
                'edges' => [],
            ],
            'created_by' => $this->admin()->id,
        ]);

        $version = $template->latestVersion();

        ProcedureRun::query()->create([
            'procedure_template_id' => $template->id,
            'procedure_template_version_id' => $version->id,
            'active_node_ids' => ['n2'],
            'path' => ['n1'],
            'status' => 'in_progress',
            'started_by' => $template->created_by,
            'started_at' => now(),
        ]);

        $response = $this->actingAs($this->admin())
            ->get(route('procedure-templates.show', $template));

        $response->assertOk()
            ->assertSee('Onboarding testowy')
            ->assertSee('Podpisz umowę')
            ->assertSee('W trakcie')
            ->assertSee('Przebiegi')
            ->assertSee('v1')
            ->assertSee('1 uruchomień');
    }

    public function test_index_links_to_preview_and_editor_still_renders(): void
    {
        $template = ProcedureTemplate::query()->create([
            'name' => 'Prosta procedura',
            'definition' => ['nodes' => [], 'edges' => []],
            'created_by' => $this->admin()->id,
        ]);

        $this->actingAs($this->admin())
            ->get(route('procedure-templates.index'))
            ->assertOk()
            ->assertSee('Prosta procedura')
            ->assertSee(route('procedure-templates.show', $template), false);

        $this->actingAs($this->admin())
            ->get(route('procedure-templates.editor', $template))
            ->assertOk()
            ->assertSee(route('procedure-templates.show', $template), false)
            ->assertSee('btnToggleProps', false)
            ->assertSee('pe-narrow-only', false);
    }

    public function test_weekly_overview_period_nav_renders(): void
    {
        $this->actingAs($this->admin())
            ->get(route('weekly-overview.index'))
            ->assertOk()
            ->assertSee('Poprzedni tydzień')
            ->assertSee('Następny tydzień');
    }
}
