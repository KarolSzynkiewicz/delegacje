<?php

namespace Tests\Feature;

use App\Enums\OfficeContractType;
use App\Enums\OfficePayMode;
use App\Enums\OfficeWorkLocation;
use App\Livewire\OfficeHoursGrid;
use App\Livewire\OfficeProfilesTable;
use App\Models\OfficeProfile;
use App\Models\OfficeWorkDay;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OfficeHoursTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'UserRoleSeeder']);

        $this->admin = User::factory()->create();
        $adminRole = \Spatie\Permission\Models\Role::where('name', 'administrator')->first();
        if ($adminRole) {
            $this->admin->assignRole($adminRole);
        }
    }

    public function test_admin_can_enable_office_profile_inline(): void
    {
        $user = User::factory()->create(['name' => 'Anna Biuro']);

        Livewire::actingAs($this->admin)
            ->test(OfficeProfilesTable::class)
            ->set("rows.{$user->id}.enabled", true)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('office_profiles', [
            'user_id' => $user->id,
            'is_active' => 1,
            'contract_type' => 'uop',
        ]);

        Livewire::actingAs($this->admin)
            ->test(OfficeProfilesTable::class)
            ->set("rows.{$user->id}.hourly_rate", '75.5')
            ->set("rows.{$user->id}.currency", 'eur')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('office_profiles', [
            'user_id' => $user->id,
            'hourly_rate' => 75.5,
            'currency' => 'EUR',
        ]);
    }

    public function test_office_profiles_index_lists_all_users(): void
    {
        $with = User::factory()->create(['name' => 'Zosia Office']);
        $without = User::factory()->create(['name' => 'Adam Bez']);
        OfficeProfile::factory()->create(['user_id' => $with->id]);

        $this->actingAs($this->admin)
            ->get(route('office-profiles.index'))
            ->assertOk()
            ->assertSee('Zosia Office', false)
            ->assertSee('Adam Bez', false);
    }

    public function test_office_hours_grid_saves_and_clears_days(): void
    {
        $user = User::factory()->create(['name' => 'Piotr Remote']);
        $profile = OfficeProfile::factory()->create([
            'user_id' => $user->id,
            'contract_type' => OfficeContractType::B2b->value,
        ]);

        $date = now()->startOfMonth()->addDays(2);
        while ($date->isWeekend()) {
            $date->addDay();
        }
        $key = $profile->id.'_'.$date->format('Ymd');
        $month = $date->format('Y-m');

        Livewire::actingAs($this->admin)
            ->test(OfficeHoursGrid::class, ['month' => $month, 'viewMode' => 'month', 'onlyMine' => false])
            ->set("cells.{$key}.starts_at", '09:00')
            ->set("cells.{$key}.ends_at", '16:30')
            ->set("cells.{$key}.location", OfficeWorkLocation::Remote->value)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('office_work_days', [
            'office_profile_id' => $profile->id,
            'work_date' => $date->toDateString(),
            'starts_at' => '09:00:00',
            'ends_at' => '16:30:00',
            'hours' => 7.5,
            'location' => 'remote',
        ]);

        Livewire::actingAs($this->admin)
            ->test(OfficeHoursGrid::class, ['month' => $month, 'viewMode' => 'month', 'onlyMine' => false])
            ->set("cells.{$key}.starts_at", '')
            ->set("cells.{$key}.ends_at", '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('office_work_days', [
            'office_profile_id' => $profile->id,
            'work_date' => $date->toDateString(),
        ]);
    }

    public function test_office_hours_rejects_end_before_start(): void
    {
        $profile = OfficeProfile::factory()->create();
        $date = now()->startOfMonth()->addDays(3);
        while ($date->isWeekend()) {
            $date->addDay();
        }
        $key = $profile->id.'_'.$date->format('Ymd');

        Livewire::actingAs($this->admin)
            ->test(OfficeHoursGrid::class, ['month' => $date->format('Y-m'), 'viewMode' => 'month', 'onlyMine' => false])
            ->set("cells.{$key}.starts_at", '17:00')
            ->set("cells.{$key}.ends_at", '09:00')
            ->call('save')
            ->assertHasErrors(["cells.{$key}.ends_at"]);
    }

    public function test_office_hours_allows_saving_start_only_then_end(): void
    {
        $profile = OfficeProfile::factory()->create();
        $date = now()->startOfMonth()->addDays(4);
        while ($date->isWeekend()) {
            $date->addDay();
        }
        $key = $profile->id.'_'.$date->format('Ymd');
        $month = $date->format('Y-m');

        Livewire::actingAs($this->admin)
            ->test(OfficeHoursGrid::class, ['month' => $month, 'viewMode' => 'month', 'onlyMine' => false])
            ->set("cells.{$key}.starts_at", '09:15')
            ->set("cells.{$key}.ends_at", '')
            ->set("cells.{$key}.location", OfficeWorkLocation::Office->value)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('office_work_days', [
            'office_profile_id' => $profile->id,
            'work_date' => $date->toDateString(),
            'starts_at' => '09:15:00',
            'ends_at' => null,
            'hours' => 0,
        ]);

        Livewire::actingAs($this->admin)
            ->test(OfficeHoursGrid::class, ['month' => $month, 'viewMode' => 'month', 'onlyMine' => false])
            ->set("cells.{$key}.starts_at", '09:15')
            ->set("cells.{$key}.ends_at", '17:00')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('office_work_days', [
            'office_profile_id' => $profile->id,
            'work_date' => $date->toDateString(),
            'starts_at' => '09:15:00',
            'ends_at' => '17:00:00',
            'hours' => 7.75,
        ]);
    }

    public function test_copy_from_first_day_fills_weekdays(): void
    {
        $profile = OfficeProfile::factory()->create();
        $monthStart = now()->startOfMonth();
        $first = $monthStart->copy();
        while ($first->isWeekend()) {
            $first->addDay();
        }
        $key = $profile->id.'_'.$first->format('Ymd');

        $component = Livewire::actingAs($this->admin)
            ->test(OfficeHoursGrid::class, ['month' => $first->format('Y-m'), 'viewMode' => 'month', 'onlyMine' => false])
            ->set("cells.{$key}.starts_at", '08:00')
            ->set("cells.{$key}.ends_at", '16:00')
            ->call('copyFromFirstDay', $profile->id)
            ->assertHasNoErrors();

        $second = $first->copy()->addDay();
        while ($second->isWeekend()) {
            $second->addDay();
        }
        $secondKey = $profile->id.'_'.$second->format('Ymd');
        $cells = $component->get('cells');
        $this->assertSame('08:00', $cells[$secondKey]['starts_at']);
        $this->assertSame('16:00', $cells[$secondKey]['ends_at']);
    }

    public function test_single_digit_hours_normalize_on_save(): void
    {
        $profile = OfficeProfile::factory()->create();
        $date = now()->startOfMonth()->addDays(6);
        while ($date->isWeekend()) {
            $date->addDay();
        }
        $key = $profile->id.'_'.$date->format('Ymd');

        Livewire::actingAs($this->admin)
            ->test(OfficeHoursGrid::class, ['month' => $date->format('Y-m'), 'viewMode' => 'month', 'onlyMine' => false])
            ->set("cells.{$key}.starts_at", '9')
            ->set("cells.{$key}.ends_at", '16')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('office_work_days', [
            'office_profile_id' => $profile->id,
            'work_date' => $date->toDateString(),
            'starts_at' => '09:00:00',
            'ends_at' => '16:00:00',
            'hours' => 7,
        ]);
    }

    public function test_leave_day_saves_without_times(): void
    {
        $profile = OfficeProfile::factory()->create(['fte' => 1]);
        $date = now()->startOfMonth()->addDays(5);
        while ($date->isWeekend()) {
            $date->addDay();
        }
        $key = $profile->id.'_'.$date->format('Ymd');

        Livewire::actingAs($this->admin)
            ->test(OfficeHoursGrid::class, ['month' => $date->format('Y-m'), 'viewMode' => 'month', 'onlyMine' => false])
            ->call('toggleLocation', $key) // office → remote
            ->call('toggleLocation', $key) // remote → leave
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('office_work_days', [
            'office_profile_id' => $profile->id,
            'work_date' => $date->toDateString(),
            'location' => 'leave',
            'hours' => 8,
            'starts_at' => null,
            'ends_at' => null,
        ]);
    }

    public function test_mine_grid_only_allows_own_profile(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();
        $myProfile = OfficeProfile::factory()->create(['user_id' => $me->id]);
        $otherProfile = OfficeProfile::factory()->create(['user_id' => $other->id]);

        $adminRole = \Spatie\Permission\Models\Role::where('name', 'administrator')->first();
        if ($adminRole) {
            $me->assignRole($adminRole);
        }

        $date = now()->startOfMonth()->addDays(4);
        while ($date->isWeekend()) {
            $date->addDay();
        }
        $foreignKey = $otherProfile->id.'_'.$date->format('Ymd');
        $month = $date->format('Y-m');

        $component = Livewire::actingAs($me)
            ->test(OfficeHoursGrid::class, ['month' => $month, 'onlyMine' => true])
            ->assertSet('onlyMine', true);

        $cells = $component->get('cells');
        $this->assertArrayNotHasKey($foreignKey, $cells);
        $this->assertArrayHasKey($myProfile->id.'_'.$date->format('Ymd'), $cells);

        $component->call('save')->assertHasNoErrors();

        $this->assertSame(0, OfficeWorkDay::where('office_profile_id', $otherProfile->id)->count());
        $this->assertTrue($myProfile->is($me->fresh()->officeProfile));
    }

    public function test_office_hours_index_renders(): void
    {
        OfficeProfile::factory()->create();

        $this->actingAs($this->admin)
            ->get(route('office-hours.index'))
            ->assertOk()
            ->assertSee('Czas pracy', false)
            ->assertSee('Tydzień', false)
            ->assertSee('Miesiąc', false)
            ->assertSee('Zapisz', false);
    }

    public function test_can_switch_between_week_and_month_view(): void
    {
        OfficeProfile::factory()->create();

        Livewire::actingAs($this->admin)
            ->test(OfficeHoursGrid::class, ['viewMode' => 'week', 'onlyMine' => false])
            ->assertSet('viewMode', 'week')
            ->call('setViewMode', 'month')
            ->assertSet('viewMode', 'month')
            ->assertSee('widok miesiąca', false)
            ->call('setViewMode', 'week')
            ->assertSet('viewMode', 'week')
            ->assertSee('widok tygodnia', false);
    }
}
