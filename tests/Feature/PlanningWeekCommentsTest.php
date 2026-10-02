<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\PlanningWeek;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanningWeekCommentsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'UserRoleSeeder']);

        $this->user = User::factory()->create(['name' => 'karol']);
        $adminRole = \Spatie\Permission\Models\Role::where('name', 'administrator')->first();
        if ($adminRole) {
            $this->user->assignRole($adminRole);
        }
    }

    public function test_weekly_overview_creates_planning_week_and_shows_comments(): void
    {
        $monday = Carbon::parse('2026-09-28')->startOfWeek();

        $this->actingAs($this->user)
            ->get(route('weekly-overview.index', ['start_date' => $monday->format('Y-m-d')]))
            ->assertOk()
            ->assertSee('Komentarze tygodnia', false);

        $this->assertDatabaseHas('planning_weeks', [
            'week_start' => $monday->toDateString(),
        ]);
    }

    public function test_planning_week_accepts_comments(): void
    {
        $week = PlanningWeek::forDate(Carbon::parse('2026-09-28'));

        $this->actingAs($this->user)
            ->post(route('comments.store'), [
                'commentable_type' => 'planning_week',
                'commentable_id' => $week->id,
                'body' => 'W czwartek pilnujcie zjazdów.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('comments', [
            'commentable_type' => 'planning_week',
            'commentable_id' => $week->id,
            'body' => 'W czwartek pilnujcie zjazdów.',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_comment_url_points_to_weekly_overview(): void
    {
        $week = PlanningWeek::forDate(Carbon::parse('2026-09-28'));
        $comment = new Comment(['body' => 'Test']);
        $comment->id = 99;
        $comment->setRelation('commentable', $week);

        $this->assertSame(
            route('weekly-overview.index', ['start_date' => '2026-09-28']).'#comment-99',
            $comment->urlWithCommentAnchor()
        );
        $this->assertStringContainsString('Tydzień', $comment->notificationContextLabel());
    }
}
