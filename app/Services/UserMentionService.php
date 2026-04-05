<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\ProjectTask;
use App\Models\TaskSubtask;
use App\Models\User;
use App\Notifications\CommentMentioned;
use App\Notifications\SubtaskMentioned;

class UserMentionService
{
    /** Wzorce @Nazwa — obsługa emaili jako nazw (znak @ w środku). */
    public const MENTION_REGEX = '/@([\w\-\.@]+)/u';

    /**
     * @return list<string> unikalne dopasowane ciągi po @
     */
    public static function extractHandles(string $text): array
    {
        preg_match_all(self::MENTION_REGEX, $text, $matches);

        return array_values(array_unique($matches[1] ?? []));
    }

    /**
     * Podświetla tylko te @wzmianki, które odpowiadają realnym użytkownikom.
     * Tekst musi być już przez e() (HTML-escaped) przed wywołaniem.
     *
     * @param  array<int, array{name: string, initials: string}>  $knownUsers
     */
    public static function highlightMentions(string $escapedText, array $knownUsers): string
    {
        $names = array_column($knownUsers, 'name');

        return preg_replace_callback(
            self::MENTION_REGEX,
            static function (array $m) use ($names): string {
                $handle = $m[1]; // część po @
                if (in_array($handle, $names, true)) {
                    return '<strong class="text-primary">@' . $handle . '</strong>';
                }

                return '@' . $handle;
            },
            $escapedText
        ) ?? $escapedText;
    }

    /**
     * Wysyła powiadomienia o wzmiance w komentarzu.
     *
     * @return list<int> ID użytkowników, którzy dostali powiadomienie
     */
    public function notifyCommentMentions(Comment $comment, User $author): array
    {
        $notifiedIds = [];

        $comment->loadMissing('commentable');

        foreach (self::extractHandles($comment->body) as $name) {
            $user = User::where('name', $name)->first();

            if (! $user) {
                continue;
            }

            $user->notify(new CommentMentioned($comment, $author));
            $notifiedIds[] = $user->id;
        }

        return $notifiedIds;
    }

    /**
     * Wysyła powiadomienia o wzmiance w nazwie podzadania (tworzenie / edycja).
     */
    public function notifySubtaskMentions(ProjectTask $task, TaskSubtask $subtask, string $name, User $author): void
    {
        foreach (self::extractHandles($name) as $handle) {
            $user = User::where('name', $handle)->first();

            if (! $user) {
                continue;
            }

            $user->notify(new SubtaskMentioned($task, $subtask, $author));
        }
    }
}
