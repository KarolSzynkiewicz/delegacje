<?php

namespace App\Services;

use App\Enums\TaskStatus;
use App\Models\Comment;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\TaskSubtask;
use App\Models\User;
use App\Notifications\CommentMentioned;
use App\Notifications\TaskAssigned;

class UserMentionService
{
    /** Wzorce @Nazwa — obsługa emaili jako nazw (znak @ w środku). Opcjonalny `!` tworzy zadanie. */
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
     * @return list<string> unikalne handele z `@nazwa!` (zadanie, nie sama notyfikacja)
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

        $this->createCommentMentionTasks($comment, $author);

        return $notifiedIds;
    }

    /**
     * `@nazwa!` tworzy zadanie dla wskazanej osoby (także dla autora). Bez `@wszyscy!`.
     */
    public function createCommentMentionTasks(Comment $comment, User $author): void
    {
        $handles = self::extractTaskHandles((string) ($comment->body ?? ''));
        if ($handles === []) {
            return;
        }

        $comment->loadMissing(['commentable', 'parent']);
        $projectId = $this->projectIdForComment($comment);
        $description = $this->mentionTaskDescription($comment);
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

            if ($comment->tasks()->where('assigned_to', $user->id)->exists()) {
                continue;
            }

            $task = ProjectTask::query()->create([
                'name' => 'Wzmianka od '.$author->name,
                'description' => $description,
                'category' => 'Komentarz',
                'status' => TaskStatus::PENDING,
                'assigned_to' => $user->id,
                'project_id' => $projectId,
                'created_by' => $author->id,
                'subject_type' => $comment->getMorphClass(),
                'subject_id' => $comment->id,
            ]);

            if ($user->id !== $author->id) {
                $user->notify(new TaskAssigned($task, $author));
            }
        }
    }

    private function projectIdForComment(Comment $comment): ?int
    {
        $morph = $comment->commentable;

        if ($morph instanceof Project) {
            return $morph->id;
        }

        if ($morph instanceof ProjectTask) {
            return $morph->project_id ? (int) $morph->project_id : null;
        }

        return null;
    }

    private function mentionTaskDescription(Comment $comment): string
    {
        $parts = [];
        $body = trim((string) ($comment->body ?? ''));
        if ($body !== '') {
            $parts[] = $body;
        }

        $parentBody = trim((string) ($comment->parent?->body ?? ''));
        if ($parentBody !== '') {
            $excerpt = mb_strlen($parentBody) > 200
                ? mb_substr($parentBody, 0, 197).'…'
                : $parentBody;
            $parts[] = 'Odpowiedź na: '.$excerpt;
        }

        $parts[] = $comment->urlWithCommentAnchor();

        return implode("\n\n", $parts);
    }

    /**
     * Wysyła powiadomienia o wzmiance w nazwie podzadania i tworzy zadania (zwykłe `@osoba`, bez `!`).
     */
    public function notifySubtaskMentions(ProjectTask $task, TaskSubtask $subtask, string $name, User $author): void
    {
        $this->createSubtaskMentionTasks($task, $subtask, $name, $author);
    }

    /**
     * `@osoba` w podzadaniu = zadanie u tej osoby (kategoria Podzadanie). Bez `@wszyscy`.
     */
    public function createSubtaskMentionTasks(ProjectTask $task, TaskSubtask $subtask, string $name, User $author): void
    {
        $handles = self::extractHandles($name);
        if ($handles === []) {
            return;
        }

        $subtask->loadMissing('task');
        $work = self::stripMentionTokens($name);
        $description = $this->subtaskMentionTaskDescription($task, $author, $work);
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

            if ($subtask->tasks()->where('assigned_to', $user->id)->exists()) {
                continue;
            }

            $mentionTask = ProjectTask::query()->create([
                'name' => 'Wzmianka od '.$author->name,
                'description' => $description,
                'category' => 'Podzadanie',
                'status' => TaskStatus::PENDING,
                'assigned_to' => $user->id,
                'project_id' => $task->project_id,
                'due_date' => $task->due_date,
                'created_by' => $author->id,
                'subject_type' => $subtask->getMorphClass(),
                'subject_id' => $subtask->id,
            ]);

            if ($user->id !== $author->id) {
                $user->notify(new TaskAssigned($mentionTask, $author));
            }
        }
    }

    private function subtaskMentionTaskDescription(ProjectTask $task, User $author, string $work): string
    {
        $label = 'Zadanie „'.$task->name.'” ('.$author->name.') z podzadaniem dla Ciebie';

        return $work !== '' ? $label."\n\n".$work : $label;
    }
}
