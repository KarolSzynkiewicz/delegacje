<?php

namespace App\Observers;

use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;

class AuditableModelObserver
{
    /** @var array<int, array<string, mixed>> */
    private static array $before = [];

    public function created(Model $model): void
    {
        AuditLogger::log($model, 'created', null, AuditLogger::snapshot($model));
    }

    public function updating(Model $model): void
    {
        self::$before[spl_object_id($model)] = AuditLogger::snapshotBeforeUpdate($model);
    }

    public function updated(Model $model): void
    {
        $id = spl_object_id($model);
        $old = self::$before[$id] ?? [];
        unset(self::$before[$id]);

        $new = AuditLogger::snapshot($model);
        if ($old === $new) {
            return;
        }

        AuditLogger::log($model, 'updated', $old, $new);
    }

    public function deleting(Model $model): void
    {
        AuditLogger::log($model, 'deleted', AuditLogger::snapshot($model), null);
    }
}
