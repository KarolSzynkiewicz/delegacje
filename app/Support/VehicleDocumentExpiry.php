<?php

namespace App\Support;

use App\Models\Vehicle;

final class VehicleDocumentExpiry
{
    /**
     * Mapa vehicle_id => ['oc' => bool, 'przeglad' => bool] do confirm() w JS.
     *
     * @param  iterable<Vehicle>  $vehicles
     */
    public static function confirmPayload(iterable $vehicles): array
    {
        $payload = [];
        foreach ($vehicles as $v) {
            if (! $v instanceof Vehicle) {
                continue;
            }
            $payload[$v->id] = [
                'oc' => $v->hasExpiredInsurance(),
                'przeglad' => $v->hasExpiredInspection(),
            ];
        }

        return $payload;
    }
}
