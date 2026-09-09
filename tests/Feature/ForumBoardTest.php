<?php

namespace Tests\Feature;

use App\Enums\CommentableType;
use App\Models\ForumPost;
use App\Models\ForumTag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ForumBoardTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'UserRoleSeeder']);
        $this->user = User::factory()->create(['name' => 'Marta Wiśniewska']);
        $adminRole = \Spatie\Permission\Models\Role::where('name', 'administrator')->first();
        if ($adminRole) {
            $this->user->assignRole($adminRole);
        }
    }

    public function test_dashboard_shows_board_and_empty_state(): void
    {
        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Tablica')
            ->assertSee('Nowy wątek')
            ->assertSee('Tablica jest pusta');
    }

    public function test_user_can_publish_a_post_with_tags(): void
    {
        $this->actingAs($this->user)
            ->post(route('dashboard.posts.store'), [
                'title' => 'Jak działają nowe wyjazdy',
                'blocks' => [
                    ['type' => 'text', 'content' => "Wyjazd to jeden rekord.\n\nDopisujesz kogoś tylko z bazy."],
                ],
                'tags' => 'logistyka, wyjazdy',
                'pinned' => '1',
            ])
            ->assertRedirect();

        $post = ForumPost::query()->first();
        $this->assertNotNull($post);
        $this->assertSame('Jak działają nowe wyjazdy', $post->title);
        $this->assertTrue($post->pinned);
        $this->assertEqualsCanonicalizing(['logistyka', 'wyjazdy'], $post->tags->pluck('name')->all());

        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Jak działają nowe wyjazdy')
            ->assertSee('#logistyka')
            ->assertSee('Przypięty')
            ->assertSee('forum-post__cover--empty', false)
            ->assertSee('bi-lightbulb', false);
    }

    public function test_create_form_posts_image_uploads_to_a_same_origin_path(): void
    {
        $this->actingAs($this->user)
            ->get(route('dashboard.posts.create'))
            ->assertOk()
            ->assertSee('\/dashboard\/posts\/images', false)
            ->assertSee('forum-sheet', false)
            ->assertSee('forum-sheet__toolbar', false);
    }

    public function test_search_and_tag_filter(): void
    {
        $match = ForumPost::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Noclegi na wyjeździe',
            'body' => [
                ['type' => 'text', 'content' => 'Jak przypisać mieszkanie.'],
            ],
        ]);
        $match->syncTagsFromString('nocleg');

        ForumPost::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Payroll za marzec',
            'body' => [
                ['type' => 'text', 'content' => 'Lista płac.'],
            ],
        ]);

        $this->actingAs($this->user)
            ->get(route('dashboard', ['q' => 'mieszkanie']))
            ->assertOk()
            ->assertSee('Noclegi na wyjeździe')
            ->assertDontSee('Payroll za marzec');

        $tag = ForumTag::query()->where('slug', 'nocleg')->first();
        $this->assertNotNull($tag);

        $this->actingAs($this->user)
            ->get(route('dashboard', ['tag' => $tag->slug]))
            ->assertOk()
            ->assertSee('Noclegi na wyjeździe')
            ->assertDontSee('Payroll za marzec');
    }

    public function test_show_records_view_and_accepts_like_and_comment(): void
    {
        $post = ForumPost::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Wątek do dyskusji',
            'body' => [
                ['type' => 'text', 'content' => 'Treść wątku.'],
            ],
        ]);
        $other = User::factory()->create(['name' => 'Jan Nowak']);
        $this->assignPlainRole($other);

        $this->actingAs($other)
            ->get(route('dashboard.posts.show', $post))
            ->assertOk()
            ->assertSee('Wątek do dyskusji')
            ->assertSee('Jan Nowak');

        $this->assertSame(1, $post->fresh()->views()->count());

        $this->actingAs($other)
            ->post(route('dashboard.posts.like', $post))
            ->assertRedirect();
        $this->assertTrue($post->fresh()->isLikedBy($other));

        $this->actingAs($this->user)
            ->post(route('comments.store'), [
                'commentable_type' => CommentableType::FORUM_POST->value,
                'commentable_id' => $post->id,
                'body' => 'A bilet — waluta zawsze 3 znaki?',
            ])
            ->assertRedirect();

        $this->actingAs($this->user)
            ->get(route('dashboard.posts.show', $post))
            ->assertOk()
            ->assertSee('A bilet — waluta zawsze 3 znaki?')
            ->assertSee('Jan Nowak')
            ->assertSee("startMention('notify')", false)
            ->assertSee("startMention('task')", false)
            ->assertSee("startMention('approval')", false)
            ->assertSee('bi-question-lg', false)
            ->assertSee('comments-composer-editor', false)
            ->assertSee('bi-eye', false);

        $this->assertSame(2, $post->fresh()->views()->count());

        $comment = $post->comments()->first();
        $this->assertNotNull($comment);
        $this->assertSame(1, $comment->views()->count());
        $this->assertTrue($comment->views()->where('user_id', $this->user->id)->exists());

        $this->actingAs($this->user)
            ->get(route('dashboard.posts.show', $post))
            ->assertOk();
        $this->assertSame(1, $comment->fresh()->views()->count());

        $this->actingAs($other)
            ->get(route('dashboard.posts.show', $post))
            ->assertOk()
            ->assertSee('Widzieli:', false);
        $this->assertSame(2, $comment->fresh()->views()->count());
    }

    public function test_stranger_cannot_edit_someone_elses_post(): void
    {
        $post = ForumPost::factory()->create(['user_id' => $this->user->id]);
        $stranger = User::factory()->create();
        $this->assignPlainRole($stranger);

        $this->actingAs($stranger)
            ->get(route('dashboard.posts.edit', $post))
            ->assertForbidden();
    }

    public function test_author_can_upload_cover_image(): void
    {
        $file = UploadedFile::fake()->image('okladka.jpg', 800, 400);

        $this->actingAs($this->user)
            ->post(route('dashboard.posts.store'), [
                'title' => 'Z okładką',
                'blocks' => [
                    ['type' => 'text', 'content' => 'Ma zdjęcie.'],
                ],
                'image' => $file,
            ])
            ->assertRedirect();

        $post = ForumPost::query()->first();
        $this->assertNotNull($post?->image_path);
        $this->assertTrue(
            \Illuminate\Support\Facades\Storage::disk('public')->exists($post->image_path)
        );
        \Illuminate\Support\Facades\Storage::disk('public')->delete($post->image_path);
    }

    public function test_post_can_mix_text_image_and_bold(): void
    {
        $file = UploadedFile::fake()->image('krok.jpg', 640, 360);

        $upload = $this->actingAs($this->user)
            ->post(route('dashboard.posts.images'), ['image' => $file])
            ->assertOk()
            ->json();

        $this->assertNotEmpty($upload['path']);
        $this->assertTrue(
            \Illuminate\Support\Facades\Storage::disk('public')->exists($upload['path'])
        );

        $this->actingAs($this->user)
            ->post(route('dashboard.posts.store'), [
                'title' => 'Kroki planera',
                'blocks' => [
                    ['type' => 'text', 'content' => 'Najpierw **role**.'],
                    ['type' => 'image', 'path' => $upload['path']],
                    ['type' => 'text', 'content' => 'Potem nocleg.'],
                ],
            ])
            ->assertRedirect();

        $post = ForumPost::query()->first();
        $this->assertCount(3, $post->blocks());
        $this->assertSame('image', $post->blocks()[1]['type']);

        $this->actingAs($this->user)
            ->get(route('dashboard.posts.show', $post))
            ->assertOk()
            ->assertSee('Najpierw', false)
            ->assertSee('<strong>role</strong>', false)
            ->assertSee('Potem nocleg.');

        \Illuminate\Support\Facades\Storage::disk('public')->delete($upload['path']);
    }

    public function test_pinning_a_comment_moves_it_to_the_top(): void
    {
        $post = ForumPost::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Wątek z komentarzami',
            'body' => [
                ['type' => 'text', 'content' => 'Treść.'],
            ],
        ]);

        $older = $post->addComment('Starszy komentarz do przypiecia', $this->user);
        $newer = $post->addComment('Nowszy komentarz luzny', $this->user);
        $older->forceFill(['created_at' => now()->subHour()])->save();
        $newer->forceFill(['created_at' => now()])->save();

        $this->actingAs($this->user)
            ->get(route('dashboard.posts.show', $post))
            ->assertOk()
            ->assertSeeInOrder(['Nowszy komentarz luzny', 'Starszy komentarz do przypiecia']);

        $this->actingAs($this->user)
            ->from(route('dashboard.posts.show', $post))
            ->post(route('comments.pin', $older))
            ->assertRedirect();

        $this->assertTrue($older->fresh()->pinned);

        $this->actingAs($this->user)
            ->get(route('dashboard.posts.show', $post))
            ->assertOk()
            ->assertSee('Przypięty')
            ->assertSee('comment-item--pinned', false)
            ->assertSeeInOrder(['Starszy komentarz do przypiecia', 'Nowszy komentarz luzny']);
    }

    public function test_previewable_attachments_open_inline_and_others_download(): void
    {
        Storage::fake('public');

        $post = ForumPost::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Wątek z załącznikiem',
            'body' => [
                ['type' => 'text', 'content' => 'Treść.'],
            ],
        ]);
        $comment = $post->addComment('komentarz z plikami', $this->user);

        Storage::disk('public')->put('attachments/comments/photo.png', 'png-bytes');
        $image = $comment->attachments()->create([
            'file_path' => 'attachments/comments/photo.png',
            'original_name' => 'photo.png',
            'uploaded_by' => $this->user->id,
        ]);

        Storage::disk('public')->put('attachments/comments/pack.zip', 'zip-bytes');
        $zip = $comment->attachments()->create([
            'file_path' => 'attachments/comments/pack.zip',
            'original_name' => 'pack.zip',
            'uploaded_by' => $this->user->id,
        ]);

        $preview = $this->actingAs($this->user)
            ->get(route('attachments.preview', $image));
        $preview->assertOk();
        $this->assertStringContainsString('inline', (string) $preview->headers->get('content-disposition'));
        $this->assertStringContainsString('photo.png', (string) $preview->headers->get('content-disposition'));
        $this->assertStringContainsString('image/png', (string) $preview->headers->get('content-type'));

        $this->actingAs($this->user)
            ->get(route('attachments.preview', $zip))
            ->assertRedirect(route('attachments.download', $zip));

        $this->actingAs($this->user)
            ->get(route('dashboard.posts.show', $post))
            ->assertOk()
            ->assertSee('Podgląd: photo.png', false)
            ->assertSee('Pobierz pack.zip', false)
            ->assertDontSee('Podgląd: pack.zip', false);
    }

    protected function assignPlainRole(User $user): void
    {
        $role = \Spatie\Permission\Models\Role::firstOrCreate(
            ['name' => 'pracownik', 'guard_name' => 'web']
        );
        $user->assignRole($role);
    }
}
