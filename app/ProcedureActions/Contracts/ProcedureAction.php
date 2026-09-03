<?php

namespace App\ProcedureActions\Contracts;

use App\Models\ProcedureRun;
use App\Models\User;

interface ProcedureAction
{
    public function key(): string;

    public function label(): string;

    /** @return list<string> morph aliases this action can run against */
    public function subjectTypes(): array;

    /**
     * @return list<array{
     *     name: string,
     *     label: string,
     *     type: string,
     *     required?: bool,
     *     options?: list<array{value: string, label: string}>,
     *     step?: string
     * }>
     */
    public function fields(ProcedureRun $run): array;

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function execute(ProcedureRun $run, array $payload, User $actor): array;
}
