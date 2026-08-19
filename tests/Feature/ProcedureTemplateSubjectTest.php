<?php

namespace Tests\Feature;

use App\Livewire\ProcedureTemplatesIndex;
use App\Models\ProcedureRun;
use App\Models\ProcedureTemplate;
use App\Models\ProjectTask;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProcedureTemplateSubjectTest extends TestCase
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

    public function test_creating_a_template_stores_subject_type(): void
    {
        Livewire::actingAs($this->user)
            ->test(ProcedureTemplatesIndex::class)
            ->call('openNewModal')
            ->set('newName', 'Przegląd auta')
            ->set('newSubjectType', 'vehicle')
            ->call('createTemplate');

        $template = ProcedureTemplate::query()->where('name', 'Przegląd auta')->first();

        $this->assertNotNull($template);
        $this->assertSame('vehicle', $template->subject_type);
        $this->assertSame('Samochód', $template->subjectType()?->label());
    }

    public function test_starting_a_procedure_binds_selected_vehicle_and_appends_it_to_task_name(): void
    {
        $vehicle = Vehicle::factory()->create([
            'registration_number' => 'WA 12345',
            'brand' => 'Ford',
            'model' => 'Transit',
        ]);

        $template = ProcedureTemplate::query()->create([
            'name' => 'Przegląd auta',
            'subject_type' => 'vehicle',
            'created_by' => $this->user->id,
            'definition' => [
                'nodes' => [
                    ['id' => 'start-1', 'type' => 'start', 'name' => 'Start'],
                ],
                'edges' => [],
            ],
        ]);

        Livewire::actingAs($this->user)
            ->test(ProcedureTemplatesIndex::class)
            ->call('openStartModal', $template->id)
            ->assertSee('WA 12345')
            ->set('startSubjectId', (string) $vehicle->id)
            ->call('startRun');

        $run = ProcedureRun::query()->latest('id')->first();
        $this->assertNotNull($run);
        $this->assertSame('vehicle', $run->subject_type);
        $this->assertSame($vehicle->id, $run->subject_id);

        $task = ProjectTask::query()->where('procedure_run_id', $run->id)->first();
        $this->assertNotNull($task);
        $this->assertSame('Przegląd auta WA 12345 Ford Transit', $task->name);
        $this->assertSame('Procedura', $task->category);
    }
}
