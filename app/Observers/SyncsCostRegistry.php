<?php

namespace App\Observers;

use App\Services\CostRegistryService;
use Illuminate\Database\Eloquent\Model;

/**
 * Każdy zapis kosztu źródłowego dopisuje brakującą pozycję rejestru kosztów,
 * a usunięcie źródła zdejmuje jego otoczkę. Rejestrowane w AppServiceProvider
 * dla FixedCostEntry, ProjectVariableCost, TransportCost i VehicleRepair.
 */
class SyncsCostRegistry
{
    public function __construct(private readonly CostRegistryService $registry) {}

    public function saved(Model $model): void
    {
        $this->registry->syncFor($model);
    }

    public function deleted(Model $model): void
    {
        $this->registry->deleteFor($model);
    }
}
