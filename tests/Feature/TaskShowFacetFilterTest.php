<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Livewire\TaskShowQuickEdit;
use App\Models\ProjectTask;
use App\Models\User;
use App\Support\TasksGridUrlParams;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TaskShowFacetFilterTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'UserRoleSeeder']);

        $this->user = User::factory()->create(['name' => 'Admin']);
        $adminRole = \Spatie\Permission\Models\Role::where('name', 'administrator')->first();
        if ($adminRole) {
            $this->user->assignRole($adminRole);
        }
    }

    public function test_show_card_category_priority_and_due_date_link_to_filtered_grid_without_dropping_active_status(): void
    {
        $task = ProjectTask::query()->create([
            'name' => 'Karta filtrów',
            'status' => TaskStatus::PENDING,
            'category' => 'Transport',
            'priority' => 4,
            'due_date' => '2026-09-03',
            'created_by' => $this->user->id,
        ]);

        $categoryUrl = TasksGridUrlParams::gridUrl(['searchCategory' => 'Transport']);
        $priorityUrl = TasksGridUrlParams::gridUrl(['priority' => '4']);
        $dueUrl = TasksGridUrlParams::gridUrl(['due' => '2026-09-03']);

        $this->assertStringContainsString('searchCategory=Transport', $categoryUrl);
        $this->assertStringContainsString('priority=4', $priorityUrl);
        $this->assertStringContainsString('due=2026-09-03', $dueUrl);
        $this->assertStringNotContainsString('status=', $categoryUrl);
        $this->assertStringNotContainsString('status=', $priorityUrl);
        $this->assertStringNotContainsString('status=', $dueUrl);

        Livewire::actingAs($this->user)
            ->test(TaskShowQuickEdit::class, ['task' => $task])
            ->assertSeeHtml('href="'.e($categoryUrl).'"')
            ->assertSeeHtml('href="'.e($priorityUrl).'"')
            ->assertSeeHtml('href="'.e($dueUrl).'"')
            ->assertSeeHtml("openQuickEdit({$task->id}, 'category'")
            ->assertSeeHtml("openQuickEdit({$task->id}, 'priority'")
            ->assertSeeHtml("openQuickEdit({$task->id}, 'due_date'");
    }

    public function test_empty_category_priority_and_due_date_are_not_filter_links(): void
    {
        $task = ProjectTask::query()->create([
            'name' => 'Puste facety',
            'status' => TaskStatus::PENDING,
            'created_by' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(TaskShowQuickEdit::class, ['task' => $task])
            ->assertDontSee('searchCategory=', false)
            ->assertDontSeeHtml('title="Zawęź listę do tej kategorii"')
            ->assertDontSeeHtml('title="Zawęź listę do tego priorytetu"')
            ->assertDontSeeHtml('title="Zawęź listę do tego dnia"');
    }
}
