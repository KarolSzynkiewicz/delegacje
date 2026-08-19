<?php

namespace Tests\Unit;

use App\Models\Comment;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\TaskSubtask;
use App\Models\WarehouseDispatch;
use Tests\TestCase;

class TaskSourceCardTest extends TestCase
{
    public function test_recruitment_fk_still_wins_on_source_card(): void
    {
        $task = new ProjectTask(['recruitment_process_id' => 7]);

        $card = $task->sourceCard();

        $this->assertSame(route('recruitment-processes.index', ['process' => 7]), $card['url']);
        $this->assertSame('Karta kandydata', $card['label']);
        $this->assertSame('bi-person-badge', $card['icon']);
    }

    public function test_warehouse_dispatch_morph_builds_source_card(): void
    {
        $dispatch = new WarehouseDispatch(['number' => 'ZW-2026-0007']);
        $dispatch->id = 44;

        $task = new ProjectTask;
        $task->setRelation('subject', $dispatch);

        $card = $task->sourceCard();

        $this->assertSame(route('warehouse-dispatches.show', $dispatch), $card['url']);
        $this->assertSame('Dokument ZW-2026-0007', $card['label']);
        $this->assertSame('bi-box-seam', $card['icon']);
    }

    public function test_comment_morph_builds_source_card(): void
    {
        $project = new Project;
        $project->id = 9;

        $comment = new Comment;
        $comment->id = 21;
        $comment->setRelation('commentable', $project);

        $task = new ProjectTask;
        $task->setRelation('subject', $comment);

        $card = $task->sourceCard();

        $this->assertSame(route('projects.show', $project).'#comment-21', $card['url']);
        $this->assertSame('Komentarz', $card['label']);
        $this->assertSame('bi-chat-dots', $card['icon']);
    }

    public function test_subtask_morph_builds_source_card(): void
    {
        $parent = new ProjectTask(['name' => 'Rodzic']);
        $parent->id = 90;

        $subtask = new TaskSubtask(['name' => '@robert klucze']);
        $subtask->id = 3;
        $subtask->setRelation('task', $parent);

        $task = new ProjectTask;
        $task->setRelation('subject', $subtask);

        $card = $task->sourceCard();

        $this->assertSame(route('tasks.show', $parent), $card['url']);
        $this->assertSame('Podzadanie', $card['label']);
        $this->assertSame('bi-check2-square', $card['icon']);
    }

    public function test_procedure_run_employee_subject_builds_source_card(): void
    {
        $employee = new \App\Models\Employee([
            'first_name' => 'Jan',
            'last_name' => 'Adamczyk',
        ]);
        $employee->id = 15;

        $run = new \App\Models\ProcedureRun([
            'subject_type' => 'employee',
            'subject_id' => 15,
        ]);
        $run->setRelation('subject', $employee);

        $task = new ProjectTask(['recruitment_process_id' => null]);
        $task->setRelation('procedureRun', $run);
        $task->setRelation('subject', null);

        $card = $task->sourceCard();

        $this->assertSame(route('employees.show', $employee), $card['url']);
        $this->assertSame('Pracownik: Jan Adamczyk', $card['label']);
        $this->assertSame('bi-person-badge', $card['icon']);
    }
}
