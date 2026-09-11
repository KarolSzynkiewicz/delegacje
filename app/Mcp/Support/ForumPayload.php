<?php

namespace App\Mcp\Support;

use App\Models\ForumPost;
use App\Models\ForumTag;

class ForumPayload
{
    /**
     * Karta wątku bez obrazków i bez pełnego body.
     *
     * @return array<string, mixed>
     */
    public static function listItem(ForumPost $post): array
    {
        $post->loadMissing(['user:id,name', 'tags:id,name,slug']);

        return [
            'id' => $post->id,
            'title' => $post->title,
            'excerpt' => $post->excerpt(),
            'pinned' => (bool) $post->pinned,
            'comments_count' => (int) ($post->comments_count ?? $post->comments()->count()),
            'created_at' => $post->created_at?->toIso8601String(),
            'updated_at' => $post->updated_at?->toIso8601String(),
            'author' => TaskPayload::user($post->user),
            'tags' => self::tags($post),
            'url' => route('dashboard.posts.show', $post),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function detail(ForumPost $post): array
    {
        $post->loadMissing([
            'user:id,name',
            'tags:id,name,slug',
            'comments' => fn ($q) => $q->with('user:id,name')->orderByDesc('id')->limit(3),
        ]);

        $recent = $post->comments->sortByDesc('id')->take(3)->values();

        return [
            ...self::listItem($post),
            'body' => $post->plainBody(),
            'recent_comments' => $recent
                ->map(fn ($comment) => TaskPayload::comment($comment, true, 200))
                ->all(),
        ];
    }

    /**
     * @return list<array{id: int, name: string, slug: string}>
     */
    public static function tags(ForumPost $post): array
    {
        return $post->tags
            ->map(fn (ForumTag $tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
            ])
            ->values()
            ->all();
    }
}
