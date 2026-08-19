<?php

namespace Tests\Feature\Pulse;

use App\Livewire\Pulse\UserRouteUsage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class UserRouteUsageTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_show_page_includes_usage_matrix_and_period_switcher(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['name' => 'Anna Kowalska']);

        $this->actingAs($admin)
            ->get(route('users.show', $user))
            ->assertOk()
            ->assertSee('Sposób użycia')
            ->assertSee('1h')
            ->assertSee('6h')
            ->assertSee('24h')
            ->assertSee('7d');
    }

    public function test_matrix_shows_only_the_viewed_users_used_routes(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['name' => 'Karol Test']);
        $other = User::factory()->create(['name' => 'Inny User']);

        $this->insertVisit($user->id, '/projects/{project}', 4);
        $this->insertVisit($other->id, '/vehicles', 9);

        Livewire::actingAs($admin)
            ->test(UserRouteUsage::class, ['userId' => $user->id])
            ->assertSee('Sposób użycia')
            ->assertSee('projects')
            ->assertDontSee('vehicles');
    }

    public function test_period_switcher_updates_selected_period(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(UserRouteUsage::class, ['userId' => $user->id])
            ->assertSet('period', '1_hour')
            ->call('setPeriod', '7_days')
            ->assertSet('period', '7_days')
            ->assertSee('7 dni');
    }

    public function test_user_without_permission_cannot_view_usage_matrix(): void
    {
        $viewer = User::factory()->create();
        $user = User::factory()->create();

        Livewire::actingAs($viewer)
            ->test(UserRouteUsage::class, ['userId' => $user->id])
            ->assertStatus(403);
    }

    private function admin(): User
    {
        $role = \Spatie\Permission\Models\Role::firstOrCreate([
            'name' => 'administrator',
            'guard_name' => 'web',
        ]);

        $admin = User::factory()->create();
        $admin->assignRole($role);

        return $admin;
    }

    private function insertVisit(int $userId, string $path, int $times): void
    {
        $key = json_encode([(string) $userId, 'GET', $path], JSON_THROW_ON_ERROR);
        $now = now()->getTimestamp();
        $period = 60;
        $currentBucket = (int) (floor($now / $period) * $period);
        $oldestBucket = $currentBucket - 3600 + $period;

        foreach (range(1, $times) as $ignored) {
            DB::table('pulse_entries')->insert([
                'timestamp' => $oldestBucket - 1,
                'type' => 'user_route',
                'key' => $key,
                'value' => 1,
            ]);
        }

        DB::table('pulse_aggregates')->insert([
            'bucket' => $currentBucket,
            'period' => $period,
            'type' => 'user_route',
            'key' => $key,
            'aggregate' => 'count',
            'value' => $times,
            'count' => $times,
        ]);
    }
}
