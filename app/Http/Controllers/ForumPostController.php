<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreForumPostRequest;
use App\Http\Requests\UpdateForumPostRequest;
use App\Models\ForumPost;
use App\Models\ForumTag;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ForumPostController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $tagSlug = trim((string) $request->query('tag', ''));

        $posts = ForumPost::query()
            ->with(['user', 'tags'])
            ->withCount(['comments', 'likes', 'views'])
            ->withExists(['likes as liked_by_me' => fn ($likes) => $likes->where('user_id', auth()->id())])
            ->search($q !== '' ? $q : null)
            ->withTag($tagSlug !== '' ? $tagSlug : null)
            ->orderByDesc('pinned')
            ->orderByDesc('created_at')
            ->paginate(12)
            ->withQueryString();

        $tags = ForumTag::query()
            ->whereHas('posts')
            ->withCount('posts')
            ->orderByDesc('posts_count')
            ->orderBy('name')
            ->get();

        $activeTag = $tagSlug !== ''
            ? $tags->firstWhere('slug', $tagSlug)
            : null;

        return view('dashboard', compact('posts', 'tags', 'q', 'tagSlug', 'activeTag'));
    }

    public function create(): View
    {
        $tags = ForumTag::query()
            ->withCount('posts')
            ->orderByDesc('posts_count')
            ->orderBy('name')
            ->limit(16)
            ->get();

        return view('dashboard.posts.create', compact('tags'));
    }

    public function store(StoreForumPostRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $imagePath = app(ImageService::class)->handleImageUpload(
            $request->file('image'),
            ForumPost::IMAGE_DIR
        );

        $post = ForumPost::query()->create([
            'user_id' => auth()->id(),
            'title' => $validated['title'],
            'body' => ForumPost::normalizeBlocks($validated['blocks']),
            'image_path' => $imagePath,
            'cover_focal_x' => ForumPost::clampFocal($validated['cover_focal_x'] ?? 50),
            'cover_focal_y' => ForumPost::clampFocal($validated['cover_focal_y'] ?? 50),
            'cover_thread_x' => ForumPost::clampFocal($validated['cover_thread_x'] ?? $validated['cover_focal_x'] ?? 50),
            'cover_thread_y' => ForumPost::clampFocal($validated['cover_thread_y'] ?? $validated['cover_focal_y'] ?? 50),
            'pinned' => $request->boolean('pinned'),
        ]);
        $post->syncTagsFromString($validated['tags'] ?? '');

        return redirect()
            ->route('dashboard.posts.show', $post)
            ->with('success', 'Wątek został opublikowany.');
    }

    public function show(ForumPost $forumPost): View
    {
        $forumPost->recordView(auth()->user());
        $forumPost->load(['user', 'tags'])
            ->loadCount(['comments', 'likes', 'views'])
            ->loadExists(['likes as liked_by_me' => fn ($likes) => $likes->where('user_id', auth()->id())]);

        $viewers = $forumPost->views()
            ->with('user')
            ->orderByDesc('viewed_at')
            ->get()
            ->pluck('user')
            ->filter();

        $likers = $forumPost->likes()
            ->with('user')
            ->orderByDesc('created_at')
            ->get()
            ->pluck('user')
            ->filter();

        return view('dashboard.posts.show', [
            'post' => $forumPost,
            'viewers' => $viewers,
            'likers' => $likers,
        ]);
    }

    public function edit(ForumPost $forumPost): View
    {
        abort_unless($forumPost->canBeManagedBy(auth()->user()), 403);
        $forumPost->load('tags');

        $tags = ForumTag::query()
            ->withCount('posts')
            ->orderByDesc('posts_count')
            ->orderBy('name')
            ->limit(16)
            ->get();

        return view('dashboard.posts.edit', [
            'post' => $forumPost,
            'tags' => $tags,
        ]);
    }

    public function update(UpdateForumPostRequest $request, ForumPost $forumPost): RedirectResponse
    {
        abort_unless($forumPost->canBeManagedBy(auth()->user()), 403);

        $validated = $request->validated();
        $previousBlockImages = $forumPost->blockImagePaths();
        $imagePath = $forumPost->image_path;

        if ($request->hasFile('image')) {
            $imagePath = app(ImageService::class)->handleImageUpload(
                $request->file('image'),
                ForumPost::IMAGE_DIR,
                $forumPost->image_path
            );
        } elseif ($request->boolean('remove_image') && $imagePath) {
            app(ImageService::class)->deleteImage($imagePath);
            $imagePath = null;
        }

        $forumPost->update([
            'title' => $validated['title'],
            'body' => ForumPost::normalizeBlocks($validated['blocks']),
            'image_path' => $imagePath,
            'cover_focal_x' => $imagePath
                ? ForumPost::clampFocal($validated['cover_focal_x'] ?? $forumPost->cover_focal_x ?? 50)
                : 50,
            'cover_focal_y' => $imagePath
                ? ForumPost::clampFocal($validated['cover_focal_y'] ?? $forumPost->cover_focal_y ?? 50)
                : 50,
            'cover_thread_x' => $imagePath
                ? ForumPost::clampFocal($validated['cover_thread_x'] ?? $forumPost->cover_thread_x ?? $forumPost->cover_focal_x ?? 50)
                : 50,
            'cover_thread_y' => $imagePath
                ? ForumPost::clampFocal($validated['cover_thread_y'] ?? $forumPost->cover_thread_y ?? $forumPost->cover_focal_y ?? 50)
                : 50,
            'pinned' => $request->boolean('pinned'),
        ]);
        $forumPost->syncTagsFromString($validated['tags'] ?? '');
        $forumPost->pruneRemovedBlockImages($previousBlockImages);

        return redirect()
            ->route('dashboard.posts.show', $forumPost)
            ->with('success', 'Wątek został zapisany.');
    }

    public function destroy(ForumPost $forumPost): RedirectResponse
    {
        abort_unless($forumPost->canBeManagedBy(auth()->user()), 403);
        $forumPost->delete();

        return redirect()
            ->route('dashboard')
            ->with('success', 'Wątek został usunięty.');
    }

    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'max:2048', 'mimes:jpeg,jpg,png,gif,webp'],
        ], [
            'image.required' => 'Wybierz zdjęcie.',
            'image.max' => 'Zdjęcie może mieć najwyżej 2 MB.',
            'image.mimes' => 'Dozwolone formaty: JPEG, PNG, GIF, WEBP.',
        ]);

        $path = app(ImageService::class)->storeImage($request->file('image'), ForumPost::IMAGE_DIR);

        return response()->json([
            'path' => $path,
            'url' => asset('storage/'.$path),
        ]);
    }

    public function like(ForumPost $forumPost): RedirectResponse
    {
        $forumPost->toggleLike(auth()->user());

        return redirect()->back();
    }
}
