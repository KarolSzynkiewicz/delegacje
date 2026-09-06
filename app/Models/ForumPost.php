<?php

namespace App\Models;

use App\Services\ImageService;
use App\Traits\HasComments;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ForumPost extends Model
{
    use HasComments, HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'body',
        'image_path',
        'pinned',
    ];

    public const IMAGE_DIR = 'forum-posts';

    protected $casts = [
        'pinned' => 'boolean',
        'body' => 'array',
    ];

    protected static function booted(): void
    {
        static::deleting(function (ForumPost $post) {
            $post->likes()->delete();
            $post->views()->delete();
            $post->tags()->detach();
            $post->comments()->get()->each->delete();
            $images = app(ImageService::class);
            $images->deleteImage($post->image_path);
            foreach ($post->blockImagePaths() as $path) {
                $images->deleteImage($path);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ForumTag::class, 'forum_post_tag');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(ForumPostLike::class);
    }

    public function views(): HasMany
    {
        return $this->hasMany(ForumPostView::class);
    }

    public function getCoverUrlAttribute(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        return asset('storage/'.$this->image_path);
    }

    /**
     * @return list<array{type: string, content?: string, path?: string}>
     */
    public static function normalizeBlocks(mixed $raw): array
    {
        if (is_array($raw)) {
            $items = array_is_list($raw) ? $raw : array_values($raw);
        } else {
            $decoded = json_decode((string) $raw, true);
            if (is_array($decoded) && array_is_list($decoded)) {
                $items = $decoded;
            } else {
                $text = trim((string) $raw);
                $items = $text === '' ? [] : [['type' => 'text', 'content' => $text]];
            }
        }

        $out = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $type = (string) ($item['type'] ?? '');
            if ($type === 'text') {
                $content = trim((string) ($item['content'] ?? ''));
                if ($content === '') {
                    continue;
                }
                if (mb_strlen($content) > 20000) {
                    $content = mb_substr($content, 0, 20000);
                }
                $out[] = ['type' => 'text', 'content' => $content];
            } elseif ($type === 'image') {
                $path = (string) ($item['path'] ?? '');
                if (! self::isSafeImagePath($path)) {
                    continue;
                }
                $out[] = ['type' => 'image', 'path' => $path];
            }
            if (count($out) >= 30) {
                break;
            }
        }

        return $out;
    }

    public static function isSafeImagePath(?string $path): bool
    {
        if (! is_string($path) || $path === '') {
            return false;
        }
        $path = str_replace('\\', '/', $path);

        return str_starts_with($path, self::IMAGE_DIR.'/')
            && ! str_contains($path, '..')
            && strlen($path) < 255;
    }

    /**
     * @return list<array{type: string, content?: string, path?: string}>
     */
    public function blocks(): array
    {
        return self::normalizeBlocks($this->body);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function blocksForEditor(): array
    {
        $blocks = collect($this->blocks())->map(function (array $block) {
            if (($block['type'] ?? '') === 'image') {
                $block['url'] = asset('storage/'.$block['path']);
            }

            return $block;
        })->values()->all();

        return $blocks !== [] ? $blocks : [['type' => 'text', 'content' => '']];
    }

    /**
     * @return list<string>
     */
    public function blockImagePaths(): array
    {
        return collect($this->blocks())
            ->where('type', 'image')
            ->pluck('path')
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  list<string>  $previousPaths
     */
    public function pruneRemovedBlockImages(array $previousPaths): void
    {
        $keep = $this->blockImagePaths();
        $images = app(ImageService::class);
        foreach ($previousPaths as $path) {
            if (! in_array($path, $keep, true)) {
                $images->deleteImage($path);
            }
        }
    }

    public function renderTextBlock(string $content): string
    {
        return Str::markdown($content, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }

    public function excerpt(int $max = 220): string
    {
        $text = '';
        foreach ($this->blocks() as $block) {
            if (($block['type'] ?? '') !== 'text') {
                continue;
            }
            $text = trim(preg_replace('/\s+/u', ' ', (string) ($block['content'] ?? '')) ?? '');
            if ($text !== '') {
                break;
            }
        }
        if ($text === '') {
            return '';
        }
        if (mb_strlen($text) <= $max) {
            return $text;
        }

        return mb_substr($text, 0, $max - 1).'…';
    }

    public function tagsInput(): string
    {
        return $this->tags->pluck('name')->implode(', ');
    }

    /**
     * @return Collection<int, string>
     */
    public static function parseTagNames(?string $raw): Collection
    {
        return collect(preg_split('/[,;]+/u', (string) $raw) ?: [])
            ->map(fn (string $name) => trim($name))
            ->filter()
            ->unique(fn (string $name) => mb_strtolower($name))
            ->take(8)
            ->map(fn (string $name) => mb_substr($name, 0, 24))
            ->values();
    }

    public function syncTagsFromString(?string $raw): void
    {
        $ids = self::parseTagNames($raw)->map(function (string $name) {
            $slug = Str::slug($name);
            if ($slug === '') {
                $slug = 'tag-'.substr(sha1(mb_strtolower($name)), 0, 8);
            }

            return ForumTag::query()->firstOrCreate(
                ['slug' => $slug],
                ['name' => $name]
            )->id;
        });

        $this->tags()->sync($ids->all());
    }

    public function recordView(User $user): void
    {
        $view = $this->views()->firstOrNew(['user_id' => $user->id]);
        $view->viewed_at = now();
        $view->save();
    }

    public function toggleLike(User $user): bool
    {
        $existing = $this->likes()->where('user_id', $user->id)->first();
        if ($existing) {
            $existing->delete();

            return false;
        }

        $this->likes()->create(['user_id' => $user->id]);

        return true;
    }

    public function isLikedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }
        if (array_key_exists('liked_by_me', $this->attributes)) {
            return (bool) $this->attributes['liked_by_me'];
        }
        if ($this->relationLoaded('likes')) {
            return $this->likes->contains('user_id', $user->id);
        }

        return $this->likes()->where('user_id', $user->id)->exists();
    }

    public function canBeManagedBy(User $user): bool
    {
        return $this->user_id === $user->id || $user->isAdmin();
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $query;
        }

        $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $term).'%';

        return $query->where(function (Builder $inner) use ($like) {
            $inner->where('title', 'like', $like)
                ->orWhere('body', 'like', $like)
                ->orWhereHas('tags', fn (Builder $tags) => $tags->where('name', 'like', $like));
        });
    }

    public function scopeWithTag(Builder $query, ?string $slug): Builder
    {
        $slug = trim((string) $slug);
        if ($slug === '') {
            return $query;
        }

        return $query->whereHas('tags', fn (Builder $tags) => $tags->where('slug', $slug));
    }
}
