<?php

namespace Tests\Feature;

use App\Livewire\EmployeePicker;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EmployeePickerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'UserRoleSeeder']);
        $user = User::factory()->create();
        $adminRole = \Spatie\Permission\Models\Role::where('name', 'administrator')->first();
        if ($adminRole) {
            $user->assignRole($adminRole);
        }
        $this->actingAs($user);
    }

    public function test_pagination_changes_page_without_href_links(): void
    {
        foreach (range(1, 17) as $i) {
            Employee::factory()->create([
                'first_name' => 'Test',
                'last_name' => sprintf('Aaa%02d', $i),
            ]);
        }

        $component = Livewire::test(EmployeePicker::class)
            ->assertSee('Test Aaa01')
            ->assertSee('Test Aaa16')
            ->assertDontSee('Test Aaa17')
            ->assertDontSeeHtml('href="?employeePickerPage=2"')
            ->assertSeeHtml("wire:click=\"nextPage('employeePickerPage')\"");

        $component
            ->call('nextPage', 'employeePickerPage')
            ->assertSet('paginators.employeePickerPage', 2)
            ->assertSee('Test Aaa17')
            ->assertDontSee('Test Aaa01');
    }

    public function test_selection_stays_visible_on_another_page(): void
    {
        foreach (range(1, 17) as $i) {
            Employee::factory()->create([
                'first_name' => 'Test',
                'last_name' => sprintf('Aaa%02d', $i),
            ]);
        }

        $first = Employee::query()->where('last_name', 'Aaa01')->firstOrFail();

        Livewire::test(EmployeePicker::class)
            ->call('toggleEmployee', $first->id)
            ->assertSee('Wybrano:')
            ->assertSeeHtml('<strong>1</strong>')
            ->call('nextPage', 'employeePickerPage')
            ->assertSet('paginators.employeePickerPage', 2)
            ->assertSeeHtml('employee-picker-selected-'.$first->id)
            ->assertSee('Wybrano:')
            ->assertSet('selectedEmployeeIds', [$first->id]);
    }

    public function test_search_resets_to_first_page(): void
    {
        foreach (range(1, 17) as $i) {
            Employee::factory()->create([
                'first_name' => 'Test',
                'last_name' => sprintf('Aaa%02d', $i),
            ]);
        }

        Livewire::test(EmployeePicker::class)
            ->call('gotoPage', 2, 'employeePickerPage')
            ->assertSet('paginators.employeePickerPage', 2)
            ->set('employeeSearch', 'Aaa01')
            ->assertSet('paginators.employeePickerPage', 1)
            ->assertSee('Test Aaa01')
            ->assertDontSee('Test Aaa17');
    }
}
