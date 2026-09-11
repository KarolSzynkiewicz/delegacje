<?php

namespace Tests\Feature;

use App\Enums\CommentableType;
use App\Mcp\Servers\TasksServer;
use App\Mcp\Tools\AddCommentTool;
use App\Mcp\Tools\CreatePostTool;
use App\Mcp\Tools\GetPostCommentsTool;
use App\Mcp\Tools\GetPostTool;
use App\Mcp\Tools\ListPostTagsTool;
use App\Mcp\Tools\SearchPostsTool;
use App\Mcp\Tools\UpdatePostTool;
use App\Models\ForumPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class McpForumToolsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $anna;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'UserRoleSeeder']);

        $this->admin = User::factory()->create(['name' => 'Karol']);
        $this->admin->assignRole(Role::where('name', 'administrator')->first());

        $this->anna = User::factory()->create(['name' => 'Anna']);
    }

    public function test_create_search_and_update_post_are_text_only(): void
    {
        TasksServer::actingAs($this->admin)
            ->tool(CreatePostTool::class, [
                'title' => 'Nowe wyjazdy',
                'body' => 'Wyjazd to jeden rekord.',
                'tags' => ['logistyka'],
                'confirmed_by_user' => false,
            ])
            ->assertHasErrors(['potwierdzenia']);

        $created = $this->toolJson(CreatePostTool::class, [
            'title' => 'Nowe wyjazdy',
            'body' => 'Wyjazd to jeden rekord.',
            'tags' => ['logistyka'],
            'confirmed_by_user' => true,
        ]);

        $this->assertSame('Nowe wyjazdy', $created['post']['title']);
        $this->assertSame('Wyjazd to jeden rekord.', $created['post']['body']);
        $this->assertSame('logistyka', $created['post']['tags'][0]['name']);
        $this->assertArrayNotHasKey('image_path', $created['post']);
        $this->assertArrayNotHasKey('cover_url', $created['post']);

        $postId = $created['post']['id'];

        $search = $this->toolJson(SearchPostsTool::class, ['tag' => 'logistyka']);
        $this->assertSame(1, $search['meta']['total_matching']);
        $this->assertSame($postId, $search['posts'][0]['id']);
        $this->assertArrayNotHasKey('body', $search['posts'][0]);

        $tags = $this->toolJson(ListPostTagsTool::class, ['q' => 'logi']);
        $this->assertSame('logistyka', $tags['tags'][0]['name']);
        $this->assertSame(1, $tags['tags'][0]['posts']);

        TasksServer::actingAs($this->admin)
            ->tool(UpdatePostTool::class, [
                'post_id' => '#'.$postId,
                'title' => 'Wyjazdy v2',
                'confirmed_by_user' => false,
            ])
            ->assertHasErrors(['potwierdzenia']);

        $updated = $this->toolJson(UpdatePostTool::class, [
            'post_id' => '#'.$postId,
            'title' => 'Wyjazdy v2',
            'body' => 'Dopisujesz kogoś tylko z bazy.',
            'tags' => ['wyjazdy'],
            'confirmed_by_user' => true,
        ]);

        $this->assertSame('Wyjazdy v2', $updated['post']['title']);
        $this->assertSame('Dopisujesz kogoś tylko z bazy.', $updated['post']['body']);
        $this->assertSame('wyjazdy', $updated['post']['tags'][0]['name']);
        $this->assertSame(['title', 'body', 'tags'], $updated['meta']['changed']);
    }

    public function test_get_post_and_comment_with_task_mention(): void
    {
        $post = ForumPost::factory()->create([
            'user_id' => $this->admin->id,
            'title' => 'Grafiki sierpnia',
            'body' => [['type' => 'text', 'content' => 'Trzeba ułożyć rotacje.']],
        ]);
        $post->syncTagsFromString('obsada');

        $card = $this->toolJson(GetPostTool::class, ['post_id' => '#'.$post->id]);
        $this->assertSame('Trzeba ułożyć rotacje.', $card['post']['body']);

        TasksServer::actingAs($this->admin)
            ->tool(AddCommentTool::class, [
                'post_id' => $post->id,
                'body' => 'Weź to na siebie @Anna!',
                'confirmed_by_user' => false,
            ])
            ->assertHasErrors(['potwierdzenia']);

        $payload = $this->toolJson(AddCommentTool::class, [
            'post_id' => $post->id,
            'body' => 'Weź to na siebie @Anna!',
            'confirmed_by_user' => true,
        ]);

        $this->assertSame('post', $payload['meta']['target']);
        $this->assertSame('task', $payload['comment']['mentions'][0]['kind']);
        $this->assertSame('Anna', $payload['effects']['task_mentions'][0]['assigned_to']['name']);
        $this->assertDatabaseHas('comments', [
            'commentable_type' => CommentableType::FORUM_POST->value,
            'commentable_id' => $post->id,
            'user_id' => $this->admin->id,
        ]);
        $this->assertDatabaseHas('comment_mentions', [
            'assigned_to' => $this->anna->id,
        ]);

        $thread = $this->toolJson(GetPostCommentsTool::class, ['post_id' => $post->id]);
        $this->assertSame(1, $thread['meta']['total']);
        $this->assertSame('task', $thread['comments'][0]['mentions'][0]['kind']);
    }

    public function test_add_comment_on_post_creates_approval_request(): void
    {
        $post = ForumPost::factory()->create([
            'user_id' => $this->admin->id,
            'title' => 'Cennik',
        ]);

        $payload = $this->toolJson(AddCommentTool::class, [
            'post_id' => '#'.$post->id,
            'body' => 'Akceptacja stawek',
            'mentions' => [
                ['user_id' => $this->anna->id, 'kind' => 'approval'],
            ],
            'confirmed_by_user' => true,
        ]);

        $this->assertStringContainsString('@Anna?', $payload['comment']['body']);
        $this->assertSame('approval', $payload['comment']['mentions'][0]['kind']);
        $this->assertSame('Akceptacja stawek', $payload['effects']['approval_requests'][0]['name']);
        $this->assertDatabaseHas('approval_requests', [
            'approver_id' => $this->anna->id,
            'name' => 'Akceptacja stawek',
        ]);
    }

    /**
     * @param  class-string  $tool
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    private function toolJson(string $tool, array $arguments): array
    {
        $response = TasksServer::actingAs($this->admin)->tool($tool, $arguments);
        $response->assertOk();

        $text = (fn (): array => $this->content())->call($response)[0] ?? '';
        $decoded = json_decode($text, true);

        $this->assertIsArray($decoded, $text);

        return $decoded;
    }
}
