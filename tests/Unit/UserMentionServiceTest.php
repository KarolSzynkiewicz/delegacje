<?php

namespace Tests\Unit;

use App\Services\UserMentionService;
use Tests\TestCase;

class UserMentionServiceTest extends TestCase
{
    public function test_extract_handles_keeps_bang_mentions_as_plain_handles(): void
    {
        $handles = UserMentionService::extractHandles('Cześć @robert! i @anna oraz @wszyscy!');

        $this->assertSame(['robert', 'anna', 'wszyscy'], $handles);
    }

    public function test_extract_task_handles_only_takes_bang_suffix(): void
    {
        $handles = UserMentionService::extractTaskHandles('Cześć @robert! i @anna oraz @wszyscy! i znowu @robert!');

        $this->assertSame(['robert', 'wszyscy'], $handles);
    }

    public function test_plain_mention_is_not_a_task_handle(): void
    {
        $this->assertSame([], UserMentionService::extractTaskHandles('@robert sprawdź to'));
    }

    public function test_highlight_includes_bang_for_known_users(): void
    {
        $html = UserMentionService::highlightMentions(
            e('@robert! i @anna'),
            [
                ['name' => 'robert', 'initials' => 'R'],
                ['name' => 'anna', 'initials' => 'A'],
            ]
        );

        $this->assertStringContainsString('<strong class="text-warning">@robert!</strong>', $html);
        $this->assertStringContainsString('<strong class="text-primary">@anna</strong>', $html);
    }

    public function test_highlight_everyone_bang_stays_warning(): void
    {
        $html = UserMentionService::highlightMentions(e('@wszyscy!'), []);

        $this->assertStringContainsString('<strong class="text-warning">@wszyscy!</strong>', $html);
    }

    public function test_highlight_marks_mention_of_logged_in_user(): void
    {
        $me = new \App\Models\User(['name' => 'karol']);
        $me->id = 1;
        $this->actingAs($me);

        $html = UserMentionService::highlightMentions(
            e('@karol sprawdź i @robert!'),
            [
                ['name' => 'karol', 'initials' => 'K'],
                ['name' => 'robert', 'initials' => 'R'],
            ]
        );

        $this->assertStringContainsString('class="mention-you text-warning"', $html);
        $this->assertStringContainsString('>@karol</strong>', $html);
        $this->assertStringContainsString('<strong class="text-warning">@robert!</strong>', $html);
    }

    public function test_strip_mention_tokens_leaves_the_actual_work(): void
    {
        $this->assertSame(
            'zrób cośtam',
            UserMentionService::stripMentionTokens('@kiero@kiero.kiero zrób cośtam')
        );
        $this->assertSame(
            'weź klucze',
            UserMentionService::stripMentionTokens('@robert! weź klucze')
        );
        $this->assertSame('', UserMentionService::stripMentionTokens('@robert'));
    }
}
