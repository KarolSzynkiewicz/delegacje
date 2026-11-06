<?php

namespace Tests\Feature;

use App\Enums\OfficeWorkLocation;
use App\Livewire\OfficeLeaveManager;
use App\Models\OfficeLeaveAdjustment;
use App\Models\OfficeProfile;
use App\Models\OfficeWorkDay;
use App\Models\User;
use App\Services\OfficeLeaveBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OfficeLeaveBalanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_shows_leave_balance_and_history_for_current_year(): void
    {
        $this->artisan('db:seed', ['--class' => 'UserRoleSeeder']);

        $user = User::factory()->create(['name' => 'Anna Urlop']);
        $adminRole = \Spatie\Permission\Models\Role::where('name', 'administrator')->first();
        if ($adminRole) {
            $user->assignRole($adminRole);
        }

        $profile = OfficeProfile::factory()->create([
            'user_id' => $user->id,
            'annual_leave_days' => 20,
        ]);

        $year = (int) now()->year;
        OfficeWorkDay::query()->create([
            'office_profile_id' => $profile->id,
            'work_date' => sprintf('%d-03-10', $year),
            'location' => OfficeWorkLocation::Leave->value,
            'hours' => 8,
            'starts_at' => null,
            'ends_at' => null,
        ]);
        OfficeWorkDay::query()->create([
            'office_profile_id' => $profile->id,
            'work_date' => sprintf('%d-03-11', $year),
            'location' => OfficeWorkLocation::Leave->value,
            'hours' => 8,
            'starts_at' => null,
            'ends_at' => null,
        ]);
        OfficeWorkDay::query()->create([
            'office_profile_id' => $profile->id,
            'work_date' => sprintf('%d-06-02', $year),
            'location' => OfficeWorkLocation::Leave->value,
            'hours' => 8,
            'starts_at' => null,
            'ends_at' => null,
        ]);

        $balance = app(OfficeLeaveBalanceService::class)->forProfile($profile, $year);
        $this->assertSame(20, $balance['entitlement']);
        $this->assertSame(0, $balance['adjustments']);
        $this->assertSame(3, $balance['used']);
        $this->assertSame(17, $balance['remaining']);
        $this->assertCount(2, $balance['ranges']);
        $this->assertSame(2, $balance['ranges'][0]['days']);
        $this->assertSame(1, $balance['ranges'][1]['days']);

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Urlop '.$year)
            ->assertSee('Wymiar')
            ->assertSee('Korekty')
            ->assertSee('Wykorzystane')
            ->assertSee('Pozostało')
            ->assertSee('Wykorzystane w '.$year);
    }

    public function test_adjustments_change_remaining_and_appear_on_profile(): void
    {
        $this->artisan('db:seed', ['--class' => 'UserRoleSeeder']);

        $user = User::factory()->create(['name' => 'Bartek Korekta']);
        $adminRole = \Spatie\Permission\Models\Role::where('name', 'administrator')->first();
        if ($adminRole) {
            $user->assignRole($adminRole);
        }

        $profile = OfficeProfile::factory()->create([
            'user_id' => $user->id,
            'annual_leave_days' => 20,
        ]);

        $year = (int) now()->year;
        OfficeLeaveAdjustment::factory()->create([
            'office_profile_id' => $profile->id,
            'year' => $year,
            'days' => 5,
            'reason' => 'Przeniesienie z poprzedniego roku',
            'created_by' => $user->id,
        ]);
        OfficeLeaveAdjustment::factory()->create([
            'office_profile_id' => $profile->id,
            'year' => $year,
            'days' => -2,
            'reason' => 'Korekta HR',
            'created_by' => $user->id,
        ]);

        $balance = app(OfficeLeaveBalanceService::class)->forProfile($profile->fresh(), $year);
        $this->assertSame(3, $balance['adjustments']);
        $this->assertSame(23, $balance['available']);
        $this->assertSame(23, $balance['remaining']);
        $this->assertCount(2, $balance['adjustment_rows']);

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Przeniesienie z poprzedniego roku')
            ->assertSee('Korekta HR')
            ->assertSee('+5')
            ->assertSee('-2');
    }

    public function test_profile_shows_empty_leave_state_without_office_profile(): void
    {
        $this->artisan('db:seed', ['--class' => 'UserRoleSeeder']);

        $user = User::factory()->create();
        $adminRole = \Spatie\Permission\Models\Role::where('name', 'administrator')->first();
        if ($adminRole) {
            $user->assignRole($adminRole);
        }

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Urlop '.(int) now()->year)
            ->assertSee('Nie masz jeszcze profilu biurowego');
    }

    public function test_admin_can_manage_leave_adjustments_from_office_leave_page(): void
    {
        $this->artisan('db:seed', ['--class' => 'UserRoleSeeder']);

        $admin = User::factory()->create(['name' => 'Admin Leave']);
        $adminRole = \Spatie\Permission\Models\Role::where('name', 'administrator')->first();
        if ($adminRole) {
            $admin->assignRole($adminRole);
        }

        $employee = User::factory()->create(['name' => 'Ewa Urlop']);
        $profile = OfficeProfile::factory()->create([
            'user_id' => $employee->id,
            'annual_leave_days' => 26,
            'is_active' => true,
        ]);

        $year = (int) now()->year;

        $this->actingAs($admin)
            ->get(route('office-leave.index'))
            ->assertOk()
            ->assertSee('Urlopy biurowe')
            ->assertSee('Ewa Urlop');

        Livewire::actingAs($admin)
            ->test(OfficeLeaveManager::class)
            ->set('year', $year)
            ->call('selectProfile', $profile->id)
            ->set('days', '4')
            ->set('reason', 'Dodatkowy urlop uznaniowy')
            ->set('effectiveOn', now()->toDateString())
            ->call('addAdjustment')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('office_leave_adjustments', [
            'office_profile_id' => $profile->id,
            'year' => $year,
            'days' => 4,
            'reason' => 'Dodatkowy urlop uznaniowy',
            'created_by' => $admin->id,
        ]);

        $row = OfficeLeaveAdjustment::query()->where('office_profile_id', $profile->id)->first();
        $this->assertNotNull($row);

        Livewire::actingAs($admin)
            ->test(OfficeLeaveManager::class)
            ->set('year', $year)
            ->call('selectProfile', $profile->id)
            ->call('deleteAdjustment', $row->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('office_leave_adjustments', [
            'id' => $row->id,
        ]);
    }
}
