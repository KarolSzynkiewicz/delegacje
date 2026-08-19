<?php

namespace Tests\Feature;

use App\Models\ProcedureTemplate;
use App\Models\ProjectTask;
use App\Models\User;
use App\Services\ProcedureRunService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcedureRunTaskCategoryTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'UserRoleSeeder']);

        $this->user = User::factory()->create();
        $adminRole = \Spatie\Permission\Models\Role::where('name', 'administrator')->first();
        if ($adminRole) {
            $this->user->assignRole($adminRole);
        }
    }

    public function test_starting_a_procedure_sets_task_category_to_procedura(): void
    {
        $this->actingAs($this->user);

        $template = ProcedureTemplate::query()->create([
            'name' => 'Onboarding',
            'created_by' => $this->user->id,
            'definition' => [
                'nodes' => [
                    ['id' => 'start-1', 'type' => 'start', 'name' => 'Start'],
                ],
                'edges' => [],
            ],
        ]);

        $run = app(ProcedureRunService::class)->startRun($template, [
            'task_name' => 'Onboarding Jan',
        ]);

        $task = ProjectTask::query()->where('procedure_run_id', $run->id)->first();

        $this->assertNotNull($task);
        $this->assertSame('Onboarding Jan', $task->name);
        $this->assertSame('Procedura', $task->category);
    }
}
