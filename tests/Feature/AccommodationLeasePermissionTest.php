<?php

namespace Tests\Feature;

use App\Models\Accommodation;
use App\Models\User;
use App\Services\RoutePermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccommodationLeasePermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_lease_routes_use_accommodations_permissions_not_leases(): void
    {
        $service = app(RoutePermissionService::class);

        $this->assertSame('accommodations.create', $service->getPermissionForRoute('accommodations.leases.store'));
        $this->assertSame('accommodations.update', $service->getPermissionForRoute('accommodations.leases.update'));
        $this->assertSame('accommodations.delete', $service->getPermissionForRoute('accommodations.leases.destroy'));

        $names = $service->getAllPermissionsFromRoutes()->pluck('name');
        $this->assertTrue($names->contains('accommodations.create'));
        $this->assertFalse($names->contains('leases.create'));
        $this->assertFalse($names->contains('leases.update'));
        $this->assertFalse($names->contains('leases.delete'));
    }

    public function test_user_with_accommodations_create_can_add_lease_period(): void
    {
        foreach (['accommodations.view', 'accommodations.create'] as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $role = Role::create(['name' => 'housing-editor', 'guard_name' => 'web']);
        $role->givePermissionTo(['accommodations.view', 'accommodations.create']);

        $user = User::factory()->create();
        $user->assignRole($role);

        $accommodation = Accommodation::factory()->create();

        $response = $this->actingAs($user)->post(
            route('accommodations.leases.store', $accommodation),
            [
                'start_date' => now()->toDateString(),
                'monthly_rent' => 1200,
                'currency' => 'EUR',
            ]
        );

        $response->assertRedirect(route('accommodations.show', $accommodation));
        $this->assertDatabaseHas('accommodation_leases', [
            'accommodation_id' => $accommodation->id,
            'monthly_rent' => 1200,
        ]);
    }

    public function test_user_without_accommodations_create_cannot_add_lease_period(): void
    {
        Permission::findOrCreate('accommodations.view', 'web');

        $role = Role::create(['name' => 'housing-viewer', 'guard_name' => 'web']);
        $role->givePermissionTo(['accommodations.view']);

        $user = User::factory()->create();
        $user->assignRole($role);

        $accommodation = Accommodation::factory()->create();

        $this->actingAs($user)->post(
            route('accommodations.leases.store', $accommodation),
            ['start_date' => now()->toDateString()]
        )->assertForbidden();
    }
}
