<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectDemand;
use App\Models\Role;
use App\Services\ProjectDemandService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectDemandServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_split_preserves_week_before_when_editing_later_week_only(): void
    {
        $project = Project::factory()->create();
        $role = Role::factory()->create();

        ProjectDemand::create([
            'project_id' => $project->id,
            'role_id' => $role->id,
            'required_count' => 10,
            'start_date' => '2026-04-13',
            'end_date' => '2026-04-26',
            'notes' => null,
        ]);

        /** @var ProjectDemandService $service */
        $service = app(ProjectDemandService::class);
        $service->createDemands(
            $project->fresh(),
            Carbon::parse('2026-04-20'),
            Carbon::parse('2026-04-26'),
            null,
            [
                $role->id => [
                    'role_id' => $role->id,
                    'required_count' => 12,
                ],
            ]
        );

        $demands = $project->demands()->where('role_id', $role->id)->orderBy('start_date')->get();
        $this->assertCount(2, $demands);

        $first = $demands[0];
        $this->assertSame('2026-04-13', $first->start_date->format('Y-m-d'));
        $this->assertSame('2026-04-19', $first->end_date->format('Y-m-d'));
        $this->assertSame(10, $first->required_count);

        $second = $demands[1];
        $this->assertSame('2026-04-20', $second->start_date->format('Y-m-d'));
        $this->assertSame('2026-04-26', $second->end_date->format('Y-m-d'));
        $this->assertSame(12, $second->required_count);
    }

    public function test_split_preserves_tail_when_editing_only_first_week(): void
    {
        $project = Project::factory()->create();
        $role = Role::factory()->create();

        ProjectDemand::create([
            'project_id' => $project->id,
            'role_id' => $role->id,
            'required_count' => 10,
            'start_date' => '2026-04-13',
            'end_date' => '2026-04-26',
            'notes' => null,
        ]);

        /** @var ProjectDemandService $service */
        $service = app(ProjectDemandService::class);
        $service->createDemands(
            $project->fresh(),
            Carbon::parse('2026-04-13'),
            Carbon::parse('2026-04-19'),
            null,
            [
                $role->id => [
                    'role_id' => $role->id,
                    'required_count' => 11,
                ],
            ]
        );

        $demands = $project->demands()->where('role_id', $role->id)->orderBy('start_date')->get();
        $this->assertCount(2, $demands);

        $this->assertSame('2026-04-13', $demands[0]->start_date->format('Y-m-d'));
        $this->assertSame('2026-04-19', $demands[0]->end_date->format('Y-m-d'));
        $this->assertSame(11, $demands[0]->required_count);

        $this->assertSame('2026-04-20', $demands[1]->start_date->format('Y-m-d'));
        $this->assertSame('2026-04-26', $demands[1]->end_date->format('Y-m-d'));
        $this->assertSame(10, $demands[1]->required_count);
    }

    public function test_required_count_zero_removes_only_window(): void
    {
        $project = Project::factory()->create();
        $role = Role::factory()->create();

        ProjectDemand::create([
            'project_id' => $project->id,
            'role_id' => $role->id,
            'required_count' => 10,
            'start_date' => '2026-04-13',
            'end_date' => '2026-04-26',
            'notes' => null,
        ]);

        /** @var ProjectDemandService $service */
        $service = app(ProjectDemandService::class);
        $service->createDemands(
            $project->fresh(),
            Carbon::parse('2026-04-20'),
            Carbon::parse('2026-04-26'),
            null,
            [
                $role->id => [
                    'role_id' => $role->id,
                    'required_count' => 0,
                ],
            ]
        );

        $demands = $project->demands()->where('role_id', $role->id)->orderBy('start_date')->get();
        $this->assertCount(1, $demands);
        $this->assertSame('2026-04-13', $demands[0]->start_date->format('Y-m-d'));
        $this->assertSame('2026-04-19', $demands[0]->end_date->format('Y-m-d'));
        $this->assertSame(10, $demands[0]->required_count);
    }
}
