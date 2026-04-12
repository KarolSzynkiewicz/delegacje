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
     * Dopasowanie użytkownika po fragmencie po @ — bez rozróżniania wielkości liter (user1 = User1).
     */
    public static function resolveUserByMentionHandle(string $handle): ?User
    {
        return User::query()
            ->whereRaw('LOWER(name) = LOWER(?)', [$handle])
            ->first();
    }

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
        return preg_replace_callback(
            self::MENTION_REGEX,
            static function (array $m) use ($knownUsers): string {
                $handle = $m[1]; // część po @ (już po e() źródła)
                foreach ($knownUsers as $u) {
                    if (! isset($u['name'])) {
                        continue;
                    }
                    $canonical = (string) $u['name'];
                    if (mb_strtolower($canonical, 'UTF-8') === mb_strtolower($handle, 'UTF-8')) {
                        return '<strong class="text-primary">@'.e($canonical).'</strong>';
                    }
                }

                return '@'.$handle;
            },
            $escapedText
        ) ?? $escapedText;
    }

    /** Odwołania #1, #2 … do podzadań zadania (tekst już po e(); może zawierać np. &lt;br /&gt;). */
    private const SUBTASK_REF_REGEX = '/(?<!\w)#(\d+)\b/u';

    /**
     * Zamienia #n na kartę z odznaką #n i nazwą podzadania (jak na liście / widoku podzadań).
     */
    public static function highlightSubtaskRefs(string $escapedText, ProjectTask $task): string
    {
        $task->loadMissing('subtasks');
        $nameByDisplayNum = [];
        foreach ($task->subtasks->sortBy(['created_at', 'id'])->values() as $i => $subtask) {
            $nameByDisplayNum[$i + 1] = (string) ($subtask->name ?? '');
        }
        if ($nameByDisplayNum === []) {
            return $escapedText;
        }

        return preg_replace_callback(
            self::SUBTASK_REF_REGEX,
            static function (array $m) use ($nameByDisplayNum): string {
                $n = (int) $m[1];
                if (! isset($nameByDisplayNum[$n])) {
                    return $m[0];
                }

                $rawName = $nameByDisplayNum[$n];
                $title = e($rawName !== '' ? $rawName : '—');

                return '<div class="card subtask-ref-card mb-2">'
                    .'<div class="subtask-ref-card-inner d-flex align-items-start gap-2">'
                    .'<span class="badge bg-secondary bg-opacity-25 text-body-secondary fw-semibold flex-shrink-0" title="Podzadanie #'.$n.' w tym zadaniu">#'
                    .$n
                    .'</span>'
                    .'<span class="small text-break flex-grow-1 subtask-ref-card-title" style="min-width:0">'.$title.'</span>'
                    .'</div></div><br />';
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

        foreach (self::extractHandles((string) ($comment->body ?? '')) as $name) {
            $user = self::resolveUserByMentionHandle($name);

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
            $user = self::resolveUserByMentionHandle($handle);

            if (! $user) {
                continue;
            }

            $user->notify(new SubtaskMentioned($task, $subtask, $author));
        }
    }
}
