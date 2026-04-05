<?php

namespace App\Http\Controllers;

use App\Enums\CommentableType;
use App\Http\Requests\StoreCommentRequest;
use App\Models\Comment;
use App\Models\User;
use App\Notifications\CommentMentioned;
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

        // Wyslij powiadomienia do wspomnianych użytkowników (@username)
        if ($comment instanceof Comment) {
            $this->notifyMentions($comment);
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
     * Parsuje @wzmianki w treści komentarza i wysyła powiadomienia.
     * Format: @NazwaUzytkownika (bez spacji — spacja kończy wzmiankę).
     */
    private function notifyMentions(Comment $comment): void
    {
        // Przechwytuje @wzmiankę do pierwszej spacji lub końca tekstu.
        // Obsługuje też nazwy będące emailami (zawierające @ wewnątrz).
        preg_match_all('/@([\w\-\.@]+)/u', $comment->body, $matches);

        if (empty($matches[1])) {
            return;
        }

        $mentionedNames = array_unique($matches[1]);
        $author = auth()->user();

        foreach ($mentionedNames as $name) {
            $user = User::where('name', $name)->first();

            if (! $user || $user->id === $author->id) {
                continue;
            }

            $user->notify(new CommentMentioned($comment->load('commentable'), $author));
        }
    }

    /**
     * Remove the specified comment.
     */
    public function destroy(\App\Models\Comment $comment): RedirectResponse
    {
        // Allow deletion of own comments or by admin
        if ($comment->user_id !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403);
        }

        $comment->delete();

        return redirect()->back()->with('success', 'Komentarz został usunięty.');
    }
}
