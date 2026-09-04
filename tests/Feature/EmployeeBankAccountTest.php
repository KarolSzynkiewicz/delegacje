<?php

namespace Tests\Feature;

use App\Livewire\EmployeeTabs;
use App\Models\Employee;
use App\Models\EmployeeBankAccount;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EmployeeBankAccountTest extends TestCase
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
        $this->actingAs($this->user);
    }

    public function test_can_create_open_ended_bank_account_and_see_it_on_profile(): void
    {
        $employee = Employee::factory()->create();
        $number = '12123412341234123412341234';

        $response = $this->from(route('employee-bank-accounts.create', ['employee_id' => $employee->id]))
            ->post(route('employee-bank-accounts.store'), [
                'employee_id' => $employee->id,
                'account_number' => '12 1234 1234 1234 1234 1234 1234',
                'start_date' => now()->toDateString(),
                'end_date' => null,
            ]);

        $response->assertRedirect(route('employees.show', ['employee' => $employee, 'tab' => 'bank']));
        $this->assertDatabaseHas('employee_bank_accounts', [
            'employee_id' => $employee->id,
            'account_number' => $number,
            'end_date' => null,
        ]);

        $this->get(route('employees.show', $employee))
            ->assertOk()
            ->assertSee('Konto bankowe')
            ->assertSee('12 1234 1234 1234 1234 1234 1234', false);
    }

    public function test_rejects_overlapping_bank_accounts(): void
    {
        $employee = Employee::factory()->create();

        EmployeeBankAccount::factory()->create([
            'employee_id' => $employee->id,
            'start_date' => '2026-01-01',
            'end_date' => null,
        ]);

        $response = $this->from(route('employee-bank-accounts.create', ['employee_id' => $employee->id]))
            ->post(route('employee-bank-accounts.store'), [
                'employee_id' => $employee->id,
                'account_number' => 'PL61109010140000071219812874',
                'start_date' => '2026-03-01',
                'end_date' => null,
            ]);

        $response->assertSessionHasErrors('start_date');
        $this->assertDatabaseCount('employee_bank_accounts', 1);
    }

    public function test_allows_sequential_bank_accounts(): void
    {
        $employee = Employee::factory()->create();

        EmployeeBankAccount::factory()->create([
            'employee_id' => $employee->id,
            'account_number' => '11111111111111111111111111',
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
        ]);

        $this->post(route('employee-bank-accounts.store'), [
            'employee_id' => $employee->id,
            'account_number' => '22222222222222222222222222',
            'start_date' => '2026-07-01',
            'end_date' => null,
        ])->assertRedirect();

        $this->assertDatabaseCount('employee_bank_accounts', 2);
    }

    public function test_past_account_is_not_shown_as_current(): void
    {
        $employee = Employee::factory()->create();

        EmployeeBankAccount::factory()->create([
            'employee_id' => $employee->id,
            'account_number' => '33333333333333333333333333',
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->subDay()->toDateString(),
        ]);

        $this->assertNull($employee->currentBankAccount());

        $this->get(route('employees.show', $employee))
            ->assertOk()
            ->assertSee('Konto bankowe')
            ->assertDontSee('33 3333 3333 3333 3333 3333 3333', false);
    }

    public function test_bank_tab_lists_history(): void
    {
        $employee = Employee::factory()->create();

        EmployeeBankAccount::factory()->create([
            'employee_id' => $employee->id,
            'account_number' => '44444444444444444444444444',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
        ]);
        EmployeeBankAccount::factory()->create([
            'employee_id' => $employee->id,
            'account_number' => '55555555555555555555555555',
            'start_date' => '2026-01-01',
            'end_date' => null,
        ]);

        Livewire::test(EmployeeTabs::class, ['employee' => $employee])
            ->set('activeTab', 'bank')
            ->assertSee('Konta bankowe')
            ->assertSee('44 4444 4444 4444 4444 4444 4444', false)
            ->assertSee('55 5555 5555 5555 5555 5555 5555', false);
    }

    public function test_can_save_shoe_and_pants_size_on_edit(): void
    {
        $role = Role::factory()->create();
        $employee = Employee::factory()->create();
        $employee->roles()->sync([$role->id]);

        $this->put(route('employees.update', $employee), [
            'first_name' => $employee->first_name,
            'last_name' => $employee->last_name,
            'email' => $employee->email,
            'phone' => $employee->phone,
            'roles' => [$role->id],
            'notes' => $employee->notes,
            'shoe_size' => '42',
            'pants_size' => '52',
            'has_komornik' => '1',
        ])->assertRedirect(route('employees.show', $employee));

        $employee->refresh();
        $this->assertSame('42', $employee->shoe_size);
        $this->assertSame('52', $employee->pants_size);
        $this->assertTrue($employee->has_komornik);

        $this->get(route('employees.show', $employee))
            ->assertOk()
            ->assertSee('Rozmiar buta')
            ->assertSee('Rozmiar spodni')
            ->assertSee('42')
            ->assertSee('52')
            ->assertSee('Komornik');

        $this->put(route('employees.update', $employee), [
            'first_name' => $employee->first_name,
            'last_name' => $employee->last_name,
            'email' => $employee->email,
            'phone' => $employee->phone,
            'roles' => [$role->id],
            'notes' => $employee->notes,
            'shoe_size' => '42',
            'pants_size' => '52',
        ]);

        $this->assertFalse($employee->fresh()->has_komornik);
    }

    public function test_legacy_comments_tab_opens_info_with_thread(): void
    {
        $employee = Employee::factory()->create();

        $this->get(route('employees.show', ['employee' => $employee, 'tab' => 'comments']))
            ->assertOk()
            ->assertSee('Rozmiar buta')
            ->assertSee('Dodaj komentarz');

        Livewire::withQueryParams(['tab' => 'comments'])
            ->test(EmployeeTabs::class, ['employee' => $employee])
            ->assertSet('activeTab', 'info')
            ->assertSee('Rozmiar buta')
            ->assertSee('Dodaj komentarz')
            ->assertDontSee('emp-rail-item-comments', false);
    }

    public function test_formats_nrb_and_iban(): void
    {
        $account = new EmployeeBankAccount(['account_number' => '12123412341234123412341234']);
        $this->assertSame('12 1234 1234 1234 1234 1234 1234', $account->formattedAccountNumber());

        $iban = new EmployeeBankAccount(['account_number' => 'PL61109010140000071219812874']);
        $this->assertSame('PL61 1090 1014 0000 0712 1981 2874', $iban->formattedAccountNumber());
    }
}
