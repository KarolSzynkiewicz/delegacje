<?php

namespace App\ProcedureActions;

use App\Models\ProcedureRun;
use App\Models\User;
use App\ProcedureActions\Contracts\ProcedureAction;
use App\ProcedureActions\Employee\AddAdvance;
use App\ProcedureActions\Employee\AddBonus;
use App\ProcedureActions\Employee\AddPenalty;
use App\ProcedureActions\Employee\AssignCompany;
use App\ProcedureActions\Employee\CreateEvaluation;
use App\ProcedureActions\Employee\SetRate;
use App\ProcedureActions\Recruitment\HireCandidate;
use App\ProcedureActions\Recruitment\SetCandidateFlag;
use App\ProcedureActions\Recruitment\StartOnboarding;
use App\ProcedureActions\Recruitment\VerifyCandidate;
use RuntimeException;

class ActionCatalog
{
    /** @var list<class-string<ProcedureAction>> */
    private const ACTIONS = [
        CreateEvaluation::class,
        SetRate::class,
        AssignCompany::class,
        AddAdvance::class,
        AddPenalty::class,
        AddBonus::class,
        VerifyCandidate::class,
        StartOnboarding::class,
        SetCandidateFlag::class,
        HireCandidate::class,
    ];

    /** @return list<ProcedureAction> */
    public function all(): array
    {
        return array_map(fn (string $class) => app($class), self::ACTIONS);
    }

    public function find(string $key): ProcedureAction
    {
        foreach ($this->all() as $action) {
            if ($action->key() === $key) {
                return $action;
            }
        }

        throw new RuntimeException('Nieznana akcja procedury: '.$key);
    }

    /**
     * @return list<array{key: string, label: string, subject_types: list<string>}>
     */
    public function editorOptions(): array
    {
        return array_map(fn (ProcedureAction $action) => [
            'key' => $action->key(),
            'label' => $action->label(),
            'subject_types' => $action->subjectTypes(),
        ], $this->all());
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function execute(string $key, ProcedureRun $run, array $payload, User $actor): array
    {
        $action = $this->find($key);
        $subjectType = (string) $run->subject_type;

        if (! in_array($subjectType, $action->subjectTypes(), true)) {
            throw new RuntimeException('Akcja „'.$action->label().'” nie pasuje do tego, kogo dotyczy ta procedura.');
        }

        return $action->execute($run, $payload, $actor);
    }
}
