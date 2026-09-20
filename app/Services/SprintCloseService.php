<?php

namespace App\Services;

use App\Enums\TaskStatus;
use App\Models\ProjectTask;
use App\Models\Sprint;
use App\Models\User;
use App\WorkItems\ProjectTaskFields;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SprintCloseService
{
    public function __construct(private ProjectTaskFields $fields) {}

    /**
     * @return array{done: int, unpinned: int}
     */
    public function close(Sprint $sprint, User $user, bool $unpinOpen = true, ?string $note = null): array
    {
        if ($sprint->isClosed()) {
            throw new InvalidArgumentException('Sprint „'.$sprint->name.'” jest już zakończony.');
        }

        return DB::transaction(function () use ($sprint, $user, $unpinOpen, $note) {
            $sprint->load('tasks');
            $done = $sprint->tasks
                ->filter(fn (ProjectTask $task) => $task->status === TaskStatus::COMPLETED)
                ->count();

            $unpinned = 0;
            if ($unpinOpen) {
                $open = $sprint->tasks->filter(fn (ProjectTask $task) => in_array(
                    $task->status,
                    [TaskStatus::PENDING, TaskStatus::IN_PROGRESS],
                    true
                ));

                foreach ($open as $task) {
                    $this->fields->writeSprint($task, '');
                    $task->addComment('Wypadło ze sprintu '.$sprint->name.' → backlog', $user);
                    $unpinned++;
                }
            }

            $sprint->update([
                'closed_at' => now(),
                'parked_at' => null,
            ]);

            $body = 'Zamknięto · '.$done.' zrobione · '.$unpinned.' odpięte';
            $note = $note !== null ? trim($note) : '';
            if ($note !== '') {
                $body .= "\n\n".$note;
            }
            $sprint->addComment($body, $user);

            return ['done' => $done, 'unpinned' => $unpinned];
        });
    }

    public function park(Sprint $sprint): void
    {
        if ($sprint->isClosed()) {
            throw new InvalidArgumentException('Zakończonego sprintu nie odstawia się na później.');
        }

        if ($sprint->isParked()) {
            return;
        }

        $sprint->update(['parked_at' => now()]);
    }

    public function unpark(Sprint $sprint): void
    {
        if ($sprint->isClosed() || $sprint->parked_at === null) {
            return;
        }

        $sprint->update(['parked_at' => null]);
    }
}
