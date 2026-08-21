<?php

namespace App\Enums;

enum WorkItemType: string
{
    case Task = 'task';
    case Subtask = 'subtask';
    case ProcedureRun = 'procedure_run';
    case Dispatch = 'dispatch';
    case FollowUp = 'follow_up';
    case Callback = 'callback';

    public function label(): string
    {
        return match ($this) {
            self::Task => 'Zadanie',
            self::Subtask => 'Podzadanie',
            self::ProcedureRun => 'Procedura',
            self::Dispatch => 'Kompletacja',
            self::FollowUp => 'Wzmianka',
            self::Callback => 'Oddzwonienie',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Task => 'bi-check2-square',
            self::Subtask => 'bi-list-check',
            self::ProcedureRun => 'bi-diagram-3',
            self::Dispatch => 'bi-box-seam',
            self::FollowUp => 'bi-at',
            self::Callback => 'bi-telephone',
        };
    }

    public function isOperationalNoise(): bool
    {
        return $this === self::Callback;
    }
}
