<?php

namespace App\Observers;

use App\Services\WorkItemSync;
use Illuminate\Database\Eloquent\Model;

class SyncsWorkItems
{
    public function __construct(private WorkItemSync $sync) {}

    public function created(Model $model): void
    {
        $this->sync->sync($model);
    }

    public function updated(Model $model): void
    {
        $this->sync->sync($model);
    }

    public function deleted(Model $model): void
    {
        $this->sync->forget($model);
    }
}
