<?php

namespace App\Http\Controllers;

use App\Enums\CommentableType;
use App\Http\Requests\StoreCommentRequest;
use App\Models\Comment;
use App\Models\ProjectTask;
use App\Models\User;
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

        // Add comment using domain method
        $comment = $commentable->addComment($request->input('body'), auth()->user());

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

        $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:5000'],
        ]);

        $comment->update([
            'body' => $request->input('body'),
        ]);

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
}
