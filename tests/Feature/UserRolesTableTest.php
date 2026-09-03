<?php

namespace Tests\Feature;

use App\Livewire\UserRolesTable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserRolesTableTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'UserRoleSeeder']);

        $admin = User::factory()->create();
        $admin->assignRole(Role::where('name', 'administrator')->first());
        $this->actingAs($admin);
    }

    public function test_index_renders_livewire_table_with_desktop_and_mobile_markup(): void
    {
        $response = $this->get(route('user-roles.index'));

        $response->assertOk();
        $response->assertSeeLivewire(UserRolesTable::class);
        $response->assertSee('administrator');
        $response->assertSee('dt-card');
        $response->assertSee('dt-cards');
        $response->assertSee('dt-table-card');
        $response->assertSee('Wszystkie');
    }

    public function test_search_filters_roles_by_name(): void
    {
        Role::create(['name' => 'koordynator', 'guard_name' => 'web']);

        Livewire::test(UserRolesTable::class)
            ->assertSee('administrator')
            ->assertSee('koordynator')
            ->set('search', 'koord')
            ->assertSee('koordynator')
            ->assertDontSee('administrator');
    }

    public function test_search_finds_roles_by_permission_name(): void
    {
        $role = Role::create(['name' => 'ksiegowa', 'guard_name' => 'web']);
        $permission = Permission::firstOrCreate(
            ['name' => 'payrolls.view', 'guard_name' => 'web']
        );
        $role->givePermissionTo($permission);

        Livewire::test(UserRolesTable::class)
            ->set('search', 'payrolls.view')
            ->assertSee('ksiegowa');
    }

    public function test_clear_filters_restores_all_roles(): void
    {
        Role::create(['name' => 'koordynator', 'guard_name' => 'web']);

        Livewire::test(UserRolesTable::class)
            ->set('search', 'koord')
            ->assertDontSee('administrator')
            ->call('clearFilters')
            ->assertSee('administrator')
            ->assertSee('koordynator');
    }
}
