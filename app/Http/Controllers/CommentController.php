<?php

namespace App\Http\Controllers;

use App\Enums\CommentableType;
use App\Enums\TaskStatus;
use App\Http\Requests\StoreCommentRequest;
use App\Models\Attachment;
use App\Models\Comment;
use App\Models\ProjectTask;
use App\Models\User;
use App\Notifications\CommentLiked;
use App\Notifications\TaskCommentAdded;
use App\Services\UserMentionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    /**
     * Store a newly created comment.
     * Resolves commentable type safely on the server side.
     */
    public function store(StoreCommentRequest $request): RedirectResponse
    {
        // Resolve type safely - don't trust the request
        $type = CommentableType::from($request->input('commentable_type'));
        $modelClass = $type->modelClass();

        // Find the commentable model
        $commentable = $modelClass::findOrFail($request->input('commentable_id'));

        $bodyRaw = $request->input('body');
        $body = is_string($bodyRaw) && trim($bodyRaw) !== '' ? $bodyRaw : null;

        $parent = null;
        if ($request->filled('parent_id')) {
            $parent = Comment::query()->findOrFail((int) $request->input('parent_id'));
        }

        // Add comment using domain method
        $comment = $commentable->addComment($body, auth()->user(), $parent);

        $files = $request->file('attachments', []);
        if (! is_array($files)) {
            $files = $files ? [$files] : [];
        }
        Attachment::storeManyFor($comment, $files, auth()->id(), 'comments');

        if ($comment instanceof Comment) {
            $mentionNotifiedIds = app(UserMentionService::class)->notifyCommentMentions($comment, auth()->user());
            $this->notifyTaskAssigneeOfNewComment($comment, $mentionNotifiedIds);
        }

        return redirect()->back()->with('success', 'Komentarz został dodany.');
    }

    /**
     * Update the specified comment.
     */
    public function update(Request $request, \App\Models\Comment $comment): RedirectResponse
    {
        // Only allow editing own comments
        if ($comment->user_id !== auth()->id()) {
            abort(403);
        }

        $validated = $request->validate([
            'body' => ['nullable', 'string', 'max:5000'],
            'attachments' => ['nullable', 'array', 'max:15'],
            'attachments.*' => ['file', 'max:15360', 'mimes:pdf,jpg,jpeg,png,gif,webp,doc,docx,xls,xlsx,txt,zip'],
        ]);

        $bodyRaw = $validated['body'] ?? null;
        $body = is_string($bodyRaw) && trim($bodyRaw) !== '' ? $bodyRaw : null;

        $files = $request->file('attachments', []);
        if (! is_array($files)) {
            $files = $files ? [$files] : [];
        }
        $hasNewFiles = collect($files)->filter(fn ($f) => $f && $f->isValid())->isNotEmpty();

        if ($body === null && $comment->attachments()->count() === 0 && ! $hasNewFiles) {
            return redirect()->back()->withErrors(['body' => 'Dodaj treść albo załącznik.'])->withInput();
        }

        $comment->update([
            'body' => $body,
        ]);

        Attachment::storeManyFor($comment, $files, auth()->id(), 'comments');

        return redirect()->back()->with('success', 'Komentarz został zaktualizowany.');
    }

    /**
     * Powiadamia przypisanego do zadania o nowym komentarzu (bez potrzeby @wzmianki).
     * Pomija autora komentarza oraz osoby już powiadomione przez @wzmiankę.
     *
     * @param  list<int>  $mentionNotifiedIds
     */
    private function notifyTaskAssigneeOfNewComment(Comment $comment, array $mentionNotifiedIds): void
    {
        $commentable = $comment->commentable;
        if (! $commentable instanceof ProjectTask) {
            return;
        }

        $assigneeId = $commentable->assigned_to;
        if (! $assigneeId || $assigneeId === auth()->id()) {
            return;
        }

        if (in_array($assigneeId, $mentionNotifiedIds, true)) {
            return;
        }

        $assignee = User::find($assigneeId);
        $assignee?->notify(new TaskCommentAdded($commentable, $comment, auth()->user()));
    }

    /**
     * Remove the specified comment.
     */
    public function destroy(\App\Models\Comment $comment): RedirectResponse
    {
        // Allow deletion of own comments or by admin
        if ($comment->user_id !== auth()->id() && ! auth()->user()->isAdmin()) {
            abort(403);
        }

        $comment->delete();

        return redirect()->back()->with('success', 'Komentarz został usunięty.');
    }

    /**
     * Polub / cofnij polubienie komentarza.
     */
    public function toggleLike(Comment $comment): RedirectResponse
    {
        $user = auth()->user();
        $existing = $comment->likes()->where('user_id', $user->id)->first();
        if ($existing) {
            $existing->delete();
        } else {
            $comment->likes()->create(['user_id' => $user->id]);
            if ($comment->user_id !== $user->id) {
                $author = User::query()->find($comment->user_id);
                $author?->notify(new CommentLiked($comment, $user));
            }
        }

        return redirect()->back();
    }

    /**
     * Odhacza / otwiera zadanie z `@nazwa!` przypisane zalogowanemu.
     */
    public function toggleMentionTask(Comment $comment): RedirectResponse
    {
        $task = $comment->mentionTaskFor(auth()->id());
        abort_if(! $task, 404);

        if ($task->status === TaskStatus::COMPLETED) {
            $task->reopen();
            $message = 'Zadanie z komentarza jest znowu otwarte.';
        } else {
            $task->markCompleted();
            $message = 'Zadanie z komentarza oznaczone jako zrobione.';
        }

        return redirect()->back()->with('success', $message)->withFragment('comment-'.$comment->id);
    }
}
