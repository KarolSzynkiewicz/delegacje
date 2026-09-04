<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectDemandsIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'UserRoleSeeder']);
    }

    public function test_global_list_does_not_offer_create_without_week_context(): void
    {
        $project = Project::factory()->create();

        $this->actingAs($this->admin())
            ->get(route('project-demands.index'))
            ->assertOk()
            ->assertSee('Wszystkie zapotrzebowania projektów')
            ->assertDontSee('Dodaj zapotrzebowanie')
            ->assertDontSee(route('projects.demands.create', $project), false);
    }

    public function test_project_list_does_not_offer_create_without_week_context(): void
    {
        $project = Project::factory()->create(['name' => 'Projekt Bez Dat']);

        $this->actingAs($this->admin())
            ->get(route('projects.demands.index', $project))
            ->assertOk()
            ->assertSee('Zapotrzebowanie projektu: Projekt Bez Dat')
            ->assertDontSee('Dodaj Zapotrzebowanie')
            ->assertDontSee('Dodaj pierwsze zapotrzebowanie')
            ->assertDontSee(route('projects.demands.create', $project), false);
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
}
