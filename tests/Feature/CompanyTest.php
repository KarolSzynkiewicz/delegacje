<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyTest extends TestCase
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

    public function test_can_create_company(): void
    {
        $response = $this->actingAs($this->user)
            ->from(route('companies.create'))
            ->post(route('companies.store'), [
                'name' => 'Test Sp. z o.o.',
                'nip' => '1234567890',
                'regon' => '123456789',
                'address' => 'ul. Testowa 1',
                'city' => 'Warszawa',
                'postal_code' => '00-001',
                'country' => 'PL',
                'founded_at' => '2020-01-15',
                'president_name' => 'Jan Kowalski',
            ]);

        $response->assertRedirect(route('companies.index'));
        $this->assertDatabaseHas('companies', [
            'name' => 'Test Sp. z o.o.',
            'nip' => '1234567890',
            'president_name' => 'Jan Kowalski',
        ]);
    }

    public function test_rejects_invalid_nip(): void
    {
        $response = $this->actingAs($this->user)
            ->from(route('companies.create'))
            ->post(route('companies.store'), [
                'name' => 'Test Sp. z o.o.',
                'nip' => '123',
            ]);

        $response->assertSessionHasErrors('nip');
    }
}
