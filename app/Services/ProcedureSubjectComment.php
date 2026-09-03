<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\ProcedureRun;
use App\Models\User;
use App\Traits\HasComments;
use RuntimeException;

class ProcedureSubjectComment
{
    /**
     * @param  array<string, mixed>  $node
     */
    public function write(ProcedureRun $run, array $node, string $body, User $actor): Comment
    {
        $run->loadMissing(['subject', 'template']);
        $subject = $run->subject;

        if ($subject === null || ! in_array(HasComments::class, class_uses_recursive($subject), true)) {
            throw new RuntimeException('Ta procedura nie jest powiązana z encją, na której można zostawić komentarz.');
        }

        $body = trim($body);
        if ($body === '') {
            throw new RuntimeException('Wpisz komentarz przed kontynuacją.');
        }

        return $subject->comments()->create([
            'user_id' => $actor->id,
            'body' => $body,
            'procedure_run_id' => $run->id,
        ]);
    }
}
