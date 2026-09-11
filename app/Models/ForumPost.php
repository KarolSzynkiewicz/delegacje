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
        'cover_focal_x',
        'cover_focal_y',
        'cover_thread_x',
        'cover_thread_y',
        'pinned',
    ];

    public const IMAGE_DIR = 'forum-posts';

    public const RICH_TEXT_CLASSES = [
        'forum-size-sm',
        'forum-size-md',
        'forum-size-lg',
        'forum-size-xl',
        'forum-color-muted',
        'forum-color-main',
        'forum-color-primary',
        'forum-color-accent',
        'forum-color-warning',
        'forum-color-danger',
        'forum-color-success',
    ];

    protected $casts = [
        'pinned' => 'boolean',
        'body' => 'array',
        'cover_focal_x' => 'integer',
        'cover_focal_y' => 'integer',
        'cover_thread_x' => 'integer',
        'cover_thread_y' => 'integer',
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

    public static function clampFocal(mixed $value): int
    {
        return max(0, min(100, (int) $value));
    }

    public function coverObjectPosition(string $frame = 'list'): string
    {
        if ($frame === 'thread') {
            return self::clampFocal($this->cover_thread_x ?? $this->cover_focal_x ?? 50).'% '
                .self::clampFocal($this->cover_thread_y ?? $this->cover_focal_y ?? 50).'%';
        }

        return self::clampFocal($this->cover_focal_x ?? 50).'% '.self::clampFocal($this->cover_focal_y ?? 50).'%';
    }

    public function coverPositionStyle(string $frame = 'list'): string
    {
        return '--forum-cover-pos: '.$this->coverObjectPosition($frame);
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
                if ($content !== '' && self::looksLikeHtml($content)) {
                    $content = self::sanitizeRichText($content);
                }
                if (self::isBlankRichText($content)) {
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
            } elseif (($block['type'] ?? '') === 'text') {
                $block['content'] = $this->editorHtml((string) ($block['content'] ?? ''));
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
        if (self::looksLikeHtml($content)) {
            return self::sanitizeRichText($content);
        }

        return Str::markdown($content, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }

    public function editorHtml(string $content): string
    {
        $content = trim($content);
        if ($content === '') {
            return '';
        }

        return $this->renderTextBlock($content);
    }

    public function excerpt(int $max = 220): string
    {
        $text = '';
        foreach ($this->blocks() as $block) {
            if (($block['type'] ?? '') !== 'text') {
                continue;
            }
            $rendered = $this->renderTextBlock((string) ($block['content'] ?? ''));
            $text = trim(preg_replace(
                '/\s+/u',
                ' ',
                html_entity_decode(strip_tags($rendered), ENT_QUOTES | ENT_HTML5, 'UTF-8')
            ) ?? '');
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

    public static function looksLikeHtml(string $content): bool
    {
        return (bool) preg_match('/<\/?(p|br|strong|b|em|i|u|ul|ol|li|span|div|h1|h2|h3)\b/i', $content);
    }

    public static function isBlankRichText(string $content): bool
    {
        $plain = html_entity_decode(strip_tags(str_replace("\xc2\xa0", ' ', $content)), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim($plain) === '';
    }

    public static function sanitizeRichText(string $html): string
    {
        $html = trim($html);
        if ($html === '' || self::isBlankRichText($html)) {
            return '';
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $wrapped = '<div id="forum-rich-root">'.$html.'</div>';
        $loaded = $dom->loadHTML('<?xml encoding="UTF-8">'.$wrapped, LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        if (! $loaded) {
            return htmlspecialchars(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        $root = $dom->getElementById('forum-rich-root');
        if (! $root) {
            return '';
        }

        self::scrubNode($root);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $dom->saveHTML($child);
        }
        $out = trim($out);

        return self::isBlankRichText($out) ? '' : $out;
    }

    private static function scrubNode(\DOMNode $node): void
    {
        $allowedTags = ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 'ul', 'ol', 'li', 'span', 'h1', 'h2', 'h3'];
        $blocked = ['script', 'style', 'iframe', 'object', 'embed', 'link', 'meta', 'form', 'input', 'button', 'textarea'];

        $children = [];
        foreach ($node->childNodes as $child) {
            $children[] = $child;
        }

        foreach ($children as $child) {
            if ($child instanceof \DOMText) {
                continue;
            }
            if (! $child instanceof \DOMElement) {
                $child->parentNode?->removeChild($child);

                continue;
            }

            $tag = strtolower($child->tagName);
            if (in_array($tag, $blocked, true)) {
                $child->parentNode?->removeChild($child);

                continue;
            }

            self::scrubNode($child);

            if ($tag === 'div') {
                $p = $child->ownerDocument->createElement('p');
                while ($child->firstChild) {
                    $p->appendChild($child->firstChild);
                }
                $child->parentNode?->replaceChild($p, $child);
                $child = $p;
                $tag = 'p';
            }

            if (! in_array($tag, $allowedTags, true)) {
                $parent = $child->parentNode;
                if ($parent) {
                    while ($child->firstChild) {
                        $parent->insertBefore($child->firstChild, $child);
                    }
                    $parent->removeChild($child);
                }

                continue;
            }

            self::scrubAttributes($child, $tag);
        }
    }

    private static function scrubAttributes(\DOMElement $el, string $tag): void
    {
        $class = $el->getAttribute('class');
        $names = [];
        foreach ($el->attributes as $attr) {
            $names[] = $attr->name;
        }
        foreach ($names as $name) {
            $el->removeAttribute($name);
        }

        if ($tag !== 'span') {
            return;
        }

        $kept = [];
        foreach (preg_split('/\s+/', $class) ?: [] as $part) {
            if (in_array($part, self::RICH_TEXT_CLASSES, true)) {
                $kept[] = $part;
            }
        }
        $kept = array_values(array_unique($kept));
        if ($kept === []) {
            $parent = $el->parentNode;
            if ($parent) {
                while ($el->firstChild) {
                    $parent->insertBefore($el->firstChild, $el);
                }
                $parent->removeChild($el);
            }

            return;
        }

        $el->setAttribute('class', implode(' ', $kept));
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
