<?php

namespace App\Services;

use App\Models\Sprint;
use App\Models\User;

class SprintCreationService
{
    /**
     * @param  array{
     *     name: string,
     *     goal?: string|null,
     *     definition_of_done?: string|null,
     *     start_date: string,
     *     end_date: string,
     * }  $data
     */
    public function create(array $data, User $creator): Sprint
    {
        return Sprint::query()->create([
            'name' => $data['name'],
            'goal' => $data['goal'] ?? null,
            'definition_of_done' => $data['definition_of_done'] ?? null,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'created_by' => $creator->id,
        ]);
    }
}
