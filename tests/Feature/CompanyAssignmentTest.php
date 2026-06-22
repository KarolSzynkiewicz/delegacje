<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyAssignment;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyAssignmentTest extends TestCase
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

    public function test_can_create_company_assignment(): void
    {
        $employee = Employee::factory()->create();
        $company = Company::create([
            'name' => 'Firma A',
            'nip' => '1234567890',
        ]);

        $response = $this->actingAs($this->user)
            ->from(route('company-assignments.create'))
            ->post(route('company-assignments.store'), [
                'employee_id' => $employee->id,
                'company_id' => $company->id,
                'start_date' => '2026-01-01',
                'end_date' => '2026-06-30',
            ]);

        $response->assertRedirect(route('company-assignments.index'));
        $this->assertDatabaseHas('company_assignments', [
            'employee_id' => $employee->id,
            'company_id' => $company->id,
        ]);
    }

    public function test_rejects_overlapping_company_assignments(): void
    {
        $employee = Employee::factory()->create();
        $companyA = Company::create(['name' => 'Firma A', 'nip' => '1234567890']);
        $companyB = Company::create(['name' => 'Firma B', 'nip' => '0987654321']);

        CompanyAssignment::create([
            'employee_id' => $employee->id,
            'company_id' => $companyA->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
        ]);

        $response = $this->actingAs($this->user)
            ->from(route('company-assignments.create'))
            ->post(route('company-assignments.store'), [
                'employee_id' => $employee->id,
                'company_id' => $companyB->id,
                'start_date' => '2026-03-01',
                'end_date' => '2026-12-31',
            ]);

        $response->assertSessionHasErrors('employee_id');
        $this->assertDatabaseCount('company_assignments', 1);
    }
}
