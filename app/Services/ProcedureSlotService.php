<?php

namespace App\Services;

use App\Enums\ProcedureRunStatus;
use App\Models\ProcedureRun;
use App\Models\ProcedureSlotBinding;
use App\Models\ProcedureTemplate;
use App\Models\RecruitmentProcess;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

/**
 * Resolves and drives "procedure slots" — named UI integration points that
 * map to a ProcedureTemplate via a persistent, DB-editable binding, and run
 * through the existing ProcedureRunService without duplicating any runner
 * logic.
 */
class ProcedureSlotService
{
    public function __construct(private ProcedureRunService $runs) {}

    public function binding(string $slotKey): ?ProcedureSlotBinding
    {
        return ProcedureSlotBinding::with('template')->where('key', $slotKey)->first();
    }

    /** Assign (or reassign) the template a slot key points to. */
    public function bind(string $slotKey, int $templateId): ProcedureSlotBinding
    {
        ProcedureTemplate::findOrFail($templateId);

        $binding = ProcedureSlotBinding::firstOrNew(['key' => $slotKey]);
        $binding->procedure_template_id = $templateId;
        $binding->updated_by = Auth::id();
        if (! $binding->exists) {
            $binding->created_by = Auth::id();
        }
        $binding->save();

        return $binding;
    }

    /** The currently in-progress run for this slot + subject, if any. */
    public function findActiveRun(string $slotKey, Model $subject): ?ProcedureRun
    {
        return $this->slotRunsQuery($slotKey, $subject)
            ->where('status', ProcedureRunStatus::IN_PROGRESS)
            ->first();
    }

    /**
     * Most recent run for this slot + subject regardless of status — so the
     * slot can remember "already finished"/"abandoned" instead of offering
     * to start again as if nothing had happened.
     */
    public function lastRun(string $slotKey, Model $subject): ?ProcedureRun
    {
        return $this->slotRunsQuery($slotKey, $subject)
            ->latest('started_at')
            ->first();
    }

    private function slotRunsQuery(string $slotKey, Model $subject)
    {
        $query = ProcedureRun::query()->where('slot_key', $slotKey);

        if ($subject instanceof RecruitmentProcess) {
            $candidateId = $subject->candidate_id;

            return $query->where(function ($q) use ($subject, $candidateId) {
                $q->where(function ($q) use ($subject) {
                    $q->where('subject_type', $subject->getMorphClass())
                        ->where('subject_id', $subject->getKey());
                });
                if ($candidateId) {
                    $q->orWhere(function ($q) use ($candidateId) {
                        $q->where('subject_type', 'recruitment_candidate')
                            ->where('subject_id', $candidateId);
                    });
                }
            });
        }

        return $query
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey());
    }

    /**
     * Start the slot bound to this process's current status, but only when the
     * slot has a template and has never been run for this process.
     */
    public function startIfNeverRunForProcess(RecruitmentProcess $process): ?ProcedureRun
    {
        $slotKey = $process->status?->procedureSlotKey();
        if ($slotKey === null) {
            return null;
        }

        if ($this->lastRun($slotKey, $process)) {
            return null;
        }

        $binding = $this->binding($slotKey);
        if (! $binding?->procedure_template_id) {
            return null;
        }

        $process->loadMissing('candidate');
        $name = $process->candidate?->full_name ?? 'Kandydat';

        return $this->startOrGetRun(
            $slotKey,
            $process,
            [
                'candidate_name' => $process->candidate?->full_name,
                'recruitment_process_id' => $process->id,
            ],
            ($binding->template?->name ?? 'Procedura').' — '.$name.' #'.$process->id,
        );
    }

    /**
     * Return the active run for this (slot, subject), or start a new one from
     * the slot's bound template. Never creates a duplicate in-progress run:
     * a unique DB constraint on procedure_runs.slot_lock_key is the ultimate
     * guard against race conditions between the check and the insert.
     */
    public function startOrGetRun(string $slotKey, Model $subject, array $variables = [], ?string $taskName = null): ProcedureRun
    {
        $existing = $this->findActiveRun($slotKey, $subject);
        if ($existing) {
            return $existing;
        }

        $binding = $this->binding($slotKey);
        if (! $binding || ! $binding->procedure_template_id) {
            throw new RuntimeException("Slot '{$slotKey}' nie ma przypisanego szablonu procedury.");
        }

        try {
            return $this->runs->startRun($binding->template, array_merge([
                'task_name' => $taskName ?: $binding->template->name,
                'assigned_to' => null,
                'due_date' => null,
                'subject_type' => $subject->getMorphClass(),
                'subject_id' => $subject->getKey(),
                'slot_key' => $slotKey,
                'variables' => $variables ?: null,
            ], $this->taskContextFromSubject($subject)));
        } catch (QueryException $e) {
            // Lost a race against a concurrent request for the same (slot, subject) —
            // the unique index on slot_lock_key rejected our insert. Return the run
            // that won instead of failing.
            if ($this->isDuplicateSlotLockError($e)) {
                return $this->findActiveRun($slotKey, $subject) ?? throw $e;
            }

            throw $e;
        }
    }

    /**
     * Extra ProjectTask fields derived from the slot subject (e.g. candidate card link).
     *
     * @return array{description?: string, category?: string, recruitment_process_id?: int, assigned_to?: int|null}
     */
    private function taskContextFromSubject(Model $subject): array
    {
        if (! $subject instanceof RecruitmentProcess) {
            return [];
        }

        $subject->loadMissing('candidate');

        return [
            'description' => $this->recruitmentTaskDescription($subject),
            'category' => 'Rekrutacja',
            'recruitment_process_id' => $subject->id,
            'assigned_to' => $subject->assigned_recruiter_id,
        ];
    }

    private function recruitmentTaskDescription(RecruitmentProcess $process): string
    {
        $lines = [];

        $name = $process->candidate?->full_name;
        if ($name) {
            $lines[] = 'Kandydat: '.$name;
        }

        $status = $process->status?->label();
        $lines[] = 'Proces rekrutacji #'.$process->id.($status ? ' — '.$status : '');

        if ($phone = $process->candidate?->phone) {
            $lines[] = 'Telefon: '.$phone;
        }

        if ($email = $process->candidate?->email) {
            $lines[] = 'E-mail: '.$email;
        }

        $lines[] = 'Karta kandydata: '.route('recruitment-processes.show', $process);

        return implode("\n", $lines);
    }

    private function isDuplicateSlotLockError(QueryException $e): bool
    {
        return str_contains($e->getMessage(), 'slot_lock_key');
    }
}
