<?php

namespace App\Events;

use App\Models\CommentMention;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MentionWasCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public CommentMention $mention,
        public ?User $actor,
    ) {}
}
