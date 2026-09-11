<?php

namespace Tests\Unit;

use App\Models\ForumPost;
use Tests\TestCase;

class ForumPostRichTextTest extends TestCase
{
    public function test_sanitize_keeps_allowed_markup_and_strips_scripts(): void
    {
        $html = ForumPost::sanitizeRichText(
            '<p>Jakiś <strong>ważny</strong> <span class="forum-color-accent forum-hack">akcent</span><script>alert(1)</script></p>'
        );

        $this->assertStringContainsString('Jakiś', $html);
        $this->assertStringContainsString('<strong>ważny</strong>', $html);
        $this->assertStringContainsString('forum-color-accent', $html);
        $this->assertStringNotContainsString('forum-hack', $html);
        $this->assertStringNotContainsString('script', $html);
        $this->assertStringNotContainsString('alert', $html);
    }

    public function test_markdown_posts_still_render_strong(): void
    {
        $html = (new ForumPost)->renderTextBlock('Najpierw **role**.');

        $this->assertStringContainsString('<strong>role</strong>', $html);
    }

    public function test_html_posts_render_headings_and_color(): void
    {
        $html = (new ForumPost)->renderTextBlock(
            '<h1>Duży</h1><p>Hello <span class="forum-size-lg forum-color-primary">big</span></p>'
        );

        $this->assertStringContainsString('<h1>Duży</h1>', $html);
        $this->assertStringContainsString('forum-size-lg', $html);
        $this->assertStringContainsString('forum-color-primary', $html);
        $this->assertStringContainsString('big', $html);
    }

    public function test_excerpt_strips_tags_and_markdown(): void
    {
        $htmlPost = new ForumPost([
            'body' => [
                ['type' => 'text', 'content' => '<p>Hello <strong>world</strong></p>'],
            ],
        ]);
        $markdownPost = new ForumPost([
            'body' => [
                ['type' => 'text', 'content' => 'Najpierw **role**.'],
            ],
        ]);

        $this->assertSame('Hello world', $htmlPost->excerpt());
        $this->assertSame('Najpierw role.', $markdownPost->excerpt());
    }
}
