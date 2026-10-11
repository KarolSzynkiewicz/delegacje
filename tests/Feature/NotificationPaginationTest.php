<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NotificationPaginationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_notifications_pager_keeps_arrows_beside_the_page_numbers(): void
    {
        $this->artisan('db:seed', ['--class' => 'UserRoleSeeder']);

        $user = User::factory()->create();
        $adminRole = Role::where('name', 'administrator')->first();
        if ($adminRole) {
            $user->assignRole($adminRole);
        }

        foreach (range(1, 21) as $i) {
            $user->notifications()->create([
                'id' => (string) Str::uuid(),
                'type' => 'App\\Notifications\\Generic',
                'data' => ['message' => 'Powiadomienie '.$i],
            ]);
        }

        $html = $this->actingAs($user)->get(route('notifications.index'))->assertOk()->getContent();

        $this->assertStringContainsString('notifications-pagination', $html);
        $this->assertStringContainsString('page-link', $html);
        $this->assertStringContainsString('Pokazano', $html);
        $this->assertStringNotContainsString('w-5 h-5', $html);
    }
}
