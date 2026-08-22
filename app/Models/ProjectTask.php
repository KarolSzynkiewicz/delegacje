<?php

namespace App\Models;

use App\Contracts\TaskSubject;
use App\Enums\TaskStatus;
use App\Traits\HasComments;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ProjectTask extends Model
{
    use HasComments, HasFactory;

    protected static function booted(): void
    {
        static::deleting(function (ProjectTask $task) {
            $task->attachments->each->delete();
        });
    }

    protected $fillable = [
        'sprint_id',
        'sprint_position',
        'name',
        'description',
        'status',
        'priority',
        'category',
        'assigned_to',
        'due_date',
        'completed_at',
        'created_by',
        'procedure_run_id',
        'recruitment_process_id',
        'subject_type',
        'subject_id',
    ];

    protected $casts = [
        'status' => TaskStatus::class,
        'due_date' => 'date',
        'completed_at' => 'datetime',
    ];

    public function sprint(): BelongsTo
    {
        return $this->belongsTo(Sprint::class);
    }

    /**
     * Get the procedure run linked to this task (if any).
     */
    public function procedureRun(): BelongsTo
    {
        return $this->belongsTo(ProcedureRun::class, 'procedure_run_id');
    }

    public function isProcedure(): bool
    {
        return $this->procedure_run_id !== null;
    }

    public function isMention(): bool
    {
        return $this->subject_type === 'comment';
    }

    public function isCallback(): bool
    {
        if ($this->isProcedure() || $this->isMention()) {
            return false;
        }

        $name = (string) $this->name;
        if ($this->category === 'Rekrutacja' && str_starts_with(mb_strtolower($name), 'oddzwonić')) {
            return true;
        }

        return str_starts_with($name, 'Oddzwonić do ');
    }

    public function mentionSourceComment(): ?Comment
    {
        if (! $this->isMention()) {
            return null;
        }

        $this->loadMissing(['subject.commentable']);

        return $this->subject instanceof Comment ? $this->subject : null;
    }

    /**
     * @return array{
     *     author: string,
     *     assignee: string,
     *     isForYou: bool,
     *     candidate: string,
     *     contextLabel: string,
     *     contextUrl: string|null,
     *     note: string,
     *     due: \Carbon\CarbonInterface|null
     * }|null
     */
    public function callbackStory(): ?array
    {
        if (! $this->isCallback()) {
            return null;
        }

        $this->loadMissing(['createdBy', 'assignedTo', 'recruitmentProcess.candidate']);
        $card = $this->sourceCard();
        $process = $this->recruitmentProcess;
        $candidate = trim((string) ($process?->full_name ?? ''));
        if ($candidate === '' && preg_match('/^oddzwonić do\s+(.+?)(?:\s+#\d+)?$/iu', (string) $this->name, $match)) {
            $candidate = trim($match[1]);
        }

        return [
            'author' => $this->createdBy?->name ?? 'Ktoś',
            'assignee' => $this->assignedTo?->name ?? 'Ciebie',
            'isForYou' => (int) $this->assigned_to === (int) auth()->id(),
            'candidate' => $candidate !== '' ? $candidate : 'kandydata',
            'contextLabel' => $card['label'] ?? 'Karta kandydata',
            'contextUrl' => $card['url'] ?? null,
            'note' => $this->plainDescription(),
            'due' => $this->due_date,
        ];
    }

    /**
     * Get the recruitment process this follow-up task belongs to (if any).
     */
    public function recruitmentProcess(): BelongsTo
    {
        return $this->belongsTo(RecruitmentProcess::class, 'recruitment_process_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Link i etykieta źródła zadania: stary FK rekrutacji albo nowy morph (ZW, …).
     *
     * @return array{url: string, label: string, icon: string}|null
     */
    public function sourceCard(): ?array
    {
        if ($url = $this->recruitmentCardUrl()) {
            return [
                'url' => $url,
                'label' => 'Karta kandydata',
                'icon' => 'bi-person-badge',
            ];
        }

        $this->loadMissing('subject');
        $subject = $this->subject;
        if ($subject instanceof TaskSubject) {
            return [
                'url' => $subject->taskCardUrl(),
                'label' => $subject->taskCardLabel(),
                'icon' => $subject->taskCardIcon(),
            ];
        }

        $this->loadMissing('procedureRun');

        return $this->procedureRun?->sourceCard();
    }

    /**
     * ID procesu rekrutacji powiązanego z zadaniem — bezpośrednio lub przez subject procedury.
     */
    public function linkedRecruitmentProcessId(): ?int
    {
        if ($this->recruitment_process_id) {
            return (int) $this->recruitment_process_id;
        }

        $this->loadMissing('procedureRun');

        if ($this->procedureRun?->subject_type === 'recruitment_process' && $this->procedureRun->subject_id) {
            return (int) $this->procedureRun->subject_id;
        }

        return null;
    }

    /**
     * URL karty kandydata w module rekrutacji (lista z otwartym procesem).
     */
    public function recruitmentCardUrl(): ?string
    {
        $processId = $this->linkedRecruitmentProcessId();

        return $processId
            ? route('recruitment-processes.index', ['process' => $processId])
            : null;
    }

    /**
     * Get the user assigned to the task.
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the user who created the task.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope a query to only include pending tasks.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', TaskStatus::PENDING);
    }

    /**
     * Scope a query to only include in progress tasks.
     */
    public function scopeInProgress(Builder $query): Builder
    {
        return $query->where('status', TaskStatus::IN_PROGRESS);
    }

    /**
     * Scope a query to only include completed tasks.
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', TaskStatus::COMPLETED);
    }

    /**
     * Scope a query to only include cancelled tasks.
     */
    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', TaskStatus::CANCELLED);
    }

    /**
     * Mark task as in progress.
     */
    public function markInProgress(): void
    {
        $this->update([
            'status' => TaskStatus::IN_PROGRESS,
            'completed_at' => null,
        ]);
    }

    /**
     * Mark task as completed.
     */
    public function markCompleted(): void
    {
        $this->update([
            'status' => TaskStatus::COMPLETED,
            'completed_at' => now(),
        ]);

        $this->syncSubjectSubtask(true);
    }

    public function reopen(): void
    {
        $this->update([
            'status' => TaskStatus::PENDING,
            'completed_at' => null,
        ]);

        $this->syncSubjectSubtask(false);
    }

    /**
     * Odhaczenie zadania ze wzmianki w podzadaniu = odhaczenie checkboxa #n.
     */
    private function syncSubjectSubtask(bool $done): void
    {
        $this->loadMissing('subject');
        $subject = $this->subject;
        if (! $subject instanceof TaskSubtask) {
            return;
        }

        if ($done && ! $subject->is_completed) {
            $subject->markCompleted();
        }

        if (! $done && $subject->is_completed) {
            $subject->markIncomplete();
        }
    }

    /**
     * Cancel the task.
     */
    public function cancel(): void
    {
        $this->update([
            'status' => TaskStatus::CANCELLED,
            'completed_at' => null,
        ]);
    }

    /**
     * Reassign the task to a user.
     */
    public function reassign(?User $user): void
    {
        $this->update([
            'assigned_to' => $user?->id,
        ]);
    }

    /**
     * Załączniki do zadania (np. specyfikacja, zdjęcia).
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * Opis do wyświetlenia w UI: dekoduje encje HTML zapisane w bazie (&gt; → >),
     * żeby Blade {{ }} nie dublował ich do widocznego „&gt;”.
     */
    public function plainDescription(): string
    {
        return html_entity_decode((string) ($this->description ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Get the subtasks for this task.
     */
    public function subtasks(): HasMany
    {
        return $this->hasMany(TaskSubtask::class, 'task_id');
    }

    /**
     * Get the progress percentage of completed subtasks.
     */
    public function getSubtasksProgressAttribute(): float
    {
        $total = $this->subtasks()->count();
        if ($total === 0) {
            return 0;
        }

        $completed = $this->subtasks()->where('is_completed', true)->count();

        return round(($completed / $total) * 100, 2);
    }

    /**
     * Mapa id podzadania → numer wyświetlany (#1, #2, …), kolejność: data utworzenia, potem id.
     *
     * @return array<int, int>
     */
    public function subtaskDisplayNumbers(): array
    {
        $this->loadMissing('subtasks');

        $map = [];
        foreach ($this->subtasks->sortBy(['created_at', 'id'])->values() as $index => $subtask) {
            $map[$subtask->id] = $index + 1;
        }

        return $map;
    }
}
