<?php

namespace App\Services;

use App\Enums\WorkItemStatus;
use App\Models\Comment;
use App\Models\CommentMention;
use App\Models\ProjectTask;
use App\Models\TaskSubtask;
use App\Models\User;
use App\Notifications\CommentMentioned;
use App\Notifications\TaskAssigned;

class UserMentionService
{
    /** Wzorce @Nazwa — obsługa emaili jako nazw (znak @ w środku). Opcjonalny `!` tworzy wzmiankę w backlogu. */
    public const MENTION_REGEX = '/@([\w\-\.@]+)(!)?/u';

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
     * @return list<string> unikalne handele z `@nazwa!` (wzmianka w backlogu, nie sama notyfikacja)
     */
    public static function extractTaskHandles(string $text): array
    {
        preg_match_all(self::MENTION_REGEX, $text, $matches, PREG_SET_ORDER);

        $handles = [];
        foreach ($matches as $match) {
            if (($match[2] ?? '') === '!') {
                $handles[] = $match[1];
            }
        }

        return array_values(array_unique($handles));
    }

    /**
     * Usuwa @wzmianki z tekstu (osoba jest już w assigned_to).
     */
    public static function stripMentionTokens(string $text): string
    {
        $stripped = preg_replace(self::MENTION_REGEX, ' ', $text) ?? $text;
        $stripped = preg_replace('/\s+/u', ' ', $stripped) ?? $stripped;

        return trim($stripped);
    }

    /**
     * Podświetla tylko te @wzmianki, które odpowiadają realnym użytkownikom.
     * Tekst musi być już przez e() (HTML-escaped) przed wywołaniem.
     *
     * @param  array<int, array{name: string, initials: string}>  $knownUsers
     */
    public static function highlightMentions(string $escapedText, array $knownUsers): string
    {
        $selfName = auth()->user()?->name;

        return preg_replace_callback(
            self::MENTION_REGEX,
            static function (array $m) use ($knownUsers, $selfName): string {
                $handle = $m[1]; // część po @ (już po e() źródła)
                $bang = ($m[2] ?? '') === '!';
                $suffix = $bang ? '!' : '';

                if (mb_strtolower($handle, 'UTF-8') === 'wszyscy') {
                    return '<strong class="text-warning">@wszyscy'.$suffix.'</strong>';
                }

                foreach ($knownUsers as $u) {
                    if (! isset($u['name'])) {
                        continue;
                    }
                    $canonical = (string) $u['name'];
                    if (mb_strtolower($canonical, 'UTF-8') !== mb_strtolower($handle, 'UTF-8')) {
                        continue;
                    }

                    $isSelf = is_string($selfName)
                        && mb_strtolower($canonical, 'UTF-8') === mb_strtolower($selfName, 'UTF-8');

                    if ($isSelf) {
                        return '<strong class="mention-you text-warning" title="Wzmianka o Tobie"'
                            .' style="background:rgba(245,158,11,.22);border-radius:.3rem;padding:.08em .38em;">'
                            .'@'.e($canonical).$suffix.'</strong>';
                    }

                    $class = $bang ? 'text-warning' : 'text-primary';

                    return '<strong class="'.$class.'">@'.e($canonical).$suffix.'</strong>';
                }

                return '@'.$handle.$suffix;
            },
            $escapedText
        ) ?? $escapedText;
    }

    /** Odwołania #1, #2 … do podzadań zadania (tekst już po e(); może zawierać np. &lt;br /&gt;). */
    public const SUBTASK_REF_REGEX = '/(?<!\w)#(\d+)\b/u';

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
        $notifyEveryone = false;

        $comment->loadMissing('commentable');

        foreach (self::extractHandles((string) ($comment->body ?? '')) as $name) {
            if (mb_strtolower($name, 'UTF-8') === 'wszyscy') {
                $notifyEveryone = true;

                continue;
            }

            $user = self::resolveUserByMentionHandle($name);

            if (! $user) {
                continue;
            }

            $user->notify(new CommentMentioned($comment, $author));
            $notifiedIds[] = $user->id;
        }

        if ($notifyEveryone) {
            User::where('id', '!=', $author->id)
                ->whereNotIn('id', $notifiedIds)
                ->get()
                ->each(function (User $user) use ($comment, $author, &$notifiedIds): void {
                    $user->notify(new CommentMentioned($comment, $author));
                    $notifiedIds[] = $user->id;
                });
        }

        $this->createCommentMentions($comment, $author);

        return $notifiedIds;
    }

    /**
     * `@nazwa!` tworzy wzmiankę w backlogu dla wskazanej osoby (także dla autora). Bez `@wszyscy!`.
     */
    public function createCommentMentions(Comment $comment, User $author): void
    {
        $handles = self::extractTaskHandles((string) ($comment->body ?? ''));
        if ($handles === []) {
            return;
        }

        $comment->loadMissing(['commentable', 'parent']);
        $assignedIds = [];

        foreach ($handles as $handle) {
            if (mb_strtolower($handle, 'UTF-8') === 'wszyscy') {
                continue;
            }

            $user = self::resolveUserByMentionHandle($handle);
            if (! $user) {
                continue;
            }

            if (isset($assignedIds[$user->id])) {
                continue;
            }
            $assignedIds[$user->id] = true;

            if ($comment->mentions()->where('assigned_to', $user->id)->exists()) {
                continue;
            }

            $mention = CommentMention::query()->create([
                'comment_id' => $comment->id,
                'assigned_to' => $user->id,
                'created_by' => $author->id,
                'title' => $this->mentionTitle($comment, $author),
                'status' => WorkItemStatus::Pending,
            ]);

            if ($user->id !== $author->id) {
                $user->notify(new TaskAssigned($mention, $author));
            }
        }
    }

    private function mentionTitle(Comment $comment, User $author): string
    {
        $request = self::stripMentionTokens((string) ($comment->body ?? ''));
        if ($request === '') {
            return 'Wzmianka od '.$author->name;
        }

        return mb_substr($request, 0, 255);
    }

    /**
     * Wysyła powiadomienia o wzmiance w nazwie podzadania i tworzy zadania (zwykłe `@osoba`, bez `!`).
     */
    public function notifySubtaskMentions(ProjectTask $task, TaskSubtask $subtask, string $name, User $author): void
    {
        $this->createSubtaskMentionTasks($task, $subtask, $name, $author);
    }

    /**
     * `@osoba` w podzadaniu = przypisanie tego checkboxa, nie klon zadania.
     */
    public function createSubtaskMentionTasks(ProjectTask $task, TaskSubtask $subtask, string $name, User $author): void
    {
        $handles = self::extractHandles($name);
        if ($handles === []) {
            return;
        }

        $assignee = null;
        foreach ($handles as $handle) {
            if (mb_strtolower($handle, 'UTF-8') === 'wszyscy') {
                continue;
            }

            $user = self::resolveUserByMentionHandle($handle);
            if ($user) {
                $assignee = $user;
                break;
            }
        }

        if (! $assignee) {
            return;
        }

        $work = self::stripMentionTokens($name);
        $subtask->update([
            'name' => $work !== '' ? $work : $name,
            'assigned_to' => $assignee->id,
        ]);

        if ($assignee->id !== $author->id) {
            $assignee->notify(new TaskAssigned($subtask->fresh() ?? $subtask, $author));
        }
    }
}
