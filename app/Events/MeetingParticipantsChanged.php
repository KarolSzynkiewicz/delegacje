<?php

namespace App\Events;

use App\Models\ProjectTask;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MeetingParticipantsChanged
{
    use Dispatchable, SerializesModels;

    /**
     * @param  list<int>  $addedUserIds
     */
    public function __construct(
        public ProjectTask $meeting,
        public array $addedUserIds,
        public ?User $actor,
    ) {}
}
