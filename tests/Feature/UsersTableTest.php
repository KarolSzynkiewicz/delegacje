<?php

namespace Tests\Feature;

use App\Livewire\UsersTable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UsersTableTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'UserRoleSeeder']);

        $this->admin = User::factory()->create([
            'name' => 'Admin Testowy',
            'email' => 'admin.testowy@example.com',
        ]);
        $this->admin->assignRole(Role::where('name', 'administrator')->first());
        $this->actingAs($this->admin);
    }

    public function test_index_renders_livewire_table_with_desktop_and_mobile_markup(): void
    {
        $response = $this->get(route('users.index'));

        $response->assertOk();
        $response->assertSeeLivewire(UsersTable::class);
        $response->assertSee('Admin Testowy');
        $response->assertSee('dt-card');
        $response->assertSee('dt-cards');
        $response->assertSee('dt-table-card');
    }

    public function test_search_filters_users_by_name(): void
    {
        $other = User::factory()->create([
            'name' => 'Zdzislaw Unikatowy',
            'email' => 'zdzislaw.unikatowy@example.com',
        ]);

        Livewire::test(UsersTable::class)
            ->assertSee('Admin Testowy')
            ->assertSee($other->name)
            ->set('search', 'Unikatowy')
            ->assertSee('Zdzislaw Unikatowy')
            ->assertDontSee('Admin Testowy');
    }

    public function test_role_filter_limits_to_selected_role(): void
    {
        $coordRole = Role::create(['name' => 'koordynator', 'guard_name' => 'web']);
        $coord = User::factory()->create([
            'name' => 'Koordynator Lista',
            'email' => 'koordynator.lista@example.com',
        ]);
        $coord->assignRole($coordRole);

        Livewire::test(UsersTable::class)
            ->set('roleFilter', 'koordynator')
            ->assertSee('Koordynator Lista')
            ->assertDontSee('Admin Testowy');
    }

    public function test_clear_filters_restores_all_users(): void
    {
        User::factory()->create([
            'name' => 'Zdzislaw Unikatowy',
            'email' => 'zdzislaw.unikatowy@example.com',
        ]);

        Livewire::test(UsersTable::class)
            ->set('search', 'Unikatowy')
            ->assertDontSee('Admin Testowy')
            ->call('clearFilters')
            ->assertSee('Admin Testowy')
            ->assertSee('Zdzislaw Unikatowy');
    }
}
