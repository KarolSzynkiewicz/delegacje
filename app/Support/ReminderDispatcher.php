<?php

namespace App\Support;

use App\Enums\WorkItemStatus;
use App\Enums\WorkItemTimeBlockKind;
use App\Enums\WorkItemType;
use App\Models\ProjectTask;
use App\Models\User;
use App\Models\WorkItem;
use App\Models\WorkItemTimeBlock;
use App\Notifications\BlockReminder;
use App\Notifications\ChronoNotification;
use App\Notifications\DeadlineReminder;
use App\Notifications\Notifier;
use App\Services\WorkItemSync;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class ReminderDispatcher
{
    public function dispatch(): int
    {
        return $this->timedBlocks()
            + $this->allDayBlocks()
            + $this->meetings()
            + $this->deadlines();
    }

    private function timedBlocks(): int
    {
        $sent = 0;

        foreach ($this->openItemBlocks()
            ->where('all_day', false)
            ->where('starts_at', '>', now()->copy()->subMinutes(2))
            ->where('starts_at', '<=', now()->copy()->addMinutes(15))
            ->get() as $block) {
            $sent += $this->sendBlock($block, false);
        }

        return $sent;
    }

    private function allDayBlocks(): int
    {
        $sent = 0;

        foreach ($this->openItemBlocks()
            ->where('all_day', true)
            ->whereDate('starts_at', now()->toDateString())
            ->get() as $block) {
            $user = $block->user;
            if (! $user || now()->format('H:i') < $user->reminderClock()) {
                continue;
            }
            $sent += $this->sendBlock($block, true);
        }

        return $sent;
    }

    private function meetings(): int
    {
        $sent = 0;
        $meetings = ProjectTask::query()
            ->where('remind', true)
            ->whereNotNull('starts_at')
            ->where('starts_at', '>', now()->copy()->subMinutes(2))
            ->where('starts_at', '<=', now()->copy()->addMinutes(15))
            ->where(fn (Builder $query) => $this->notYetSent($query, 'reminder_sent_for', 'starts_at'))
            ->whereHas('workItem', function (Builder $query): void {
                $query->where('type', WorkItemType::Meeting->value)
                    ->whereIn('status', $this->openStatuses());
            })
            ->with('workItem')
            ->get();

        foreach ($meetings as $meeting) {
            $ids = array_values(array_unique(array_filter(array_map(
                'intval',
                array_merge([$meeting->assigned_to], $meeting->participant_ids ?? []),
            ))));
            $delivered = false;
            $when = trim($meeting->starts_at->format('H:i').($meeting->ends_at ? '–'.$meeting->ends_at->format('H:i') : ''));
            $url = route('tasks.show', $meeting);

            foreach (User::query()->whereIn('id', $ids)->get() as $user) {
                if ($this->ping($user, new BlockReminder($meeting->name, $when, $url, false))) {
                    $delivered = true;
                    $sent++;
                }
            }

            if ($delivered) {
                $this->markSent($meeting, 'reminder_sent_for', 'starts_at');
            }
        }

        return $sent;
    }

    private function deadlines(): int
    {
        $sent = 0;
        $items = WorkItem::query()
            ->whereNotNull('due_at')
            ->whereNotNull('assignee_id')
            ->whereDate('due_at', now()->toDateString())
            ->where('type', '!=', WorkItemType::Meeting->value)
            ->whereIn('status', $this->openStatuses())
            ->where(fn (Builder $query) => $this->notYetSent($query, 'deadline_reminded_on', 'due_at'))
            ->with(['assignee', 'source'])
            ->get();

        foreach ($items as $item) {
            $user = $item->assignee;
            if (! $user || now()->format('H:i') < $user->reminderClock()) {
                continue;
            }
            if (! $this->ping($user, new DeadlineReminder($item->title, app(WorkItemSync::class)->url($item)))) {
                continue;
            }
            $this->markSent($item, 'deadline_reminded_on', 'due_at');
            $sent++;
        }

        return $sent;
    }

    private function openItemBlocks(): Builder
    {
        return WorkItemTimeBlock::query()
            ->where('remind', true)
            ->where('kind', WorkItemTimeBlockKind::Item->value)
            ->where(fn (Builder $query) => $this->notYetSent($query, 'reminder_sent_for', 'starts_at'))
            ->whereHas('workItem', fn (Builder $query) => $query->whereIn('status', $this->openStatuses()))
            ->with(['user', 'workItem.source']);
    }

    /**
     * @return list<string>
     */
    private function openStatuses(): array
    {
        return [
            WorkItemStatus::Pending->value,
            WorkItemStatus::InProgress->value,
        ];
    }

    private function notYetSent(Builder $query, string $sentColumn, string $atColumn): void
    {
        $query->where(function (Builder $inner) use ($sentColumn, $atColumn): void {
            $inner->whereNull($sentColumn)
                ->orWhereColumn($sentColumn, '!=', $atColumn);
        });
    }

    private function sendBlock(WorkItemTimeBlock $block, bool $allDay): int
    {
        $user = $block->user;
        $item = $block->workItem;
        if (! $user || ! $item) {
            return 0;
        }

        $url = app(WorkItemSync::class)->url($item);
        $when = $allDay ? 'cały dzień' : $block->starts_at->format('H:i').'–'.$block->ends_at->format('H:i');
        if (! $this->ping($user, new BlockReminder($item->title, $when, $url, $allDay))) {
            return 0;
        }

        $this->markSent($block, 'reminder_sent_for', 'starts_at');

        return 1;
    }

    private function ping(User $user, ChronoNotification $notification): bool
    {
        if ($notification->via($user) === []) {
            return false;
        }

        Notifier::send($user, $notification);

        return true;
    }

    private function markSent(Model $model, string $column, string $sourceColumn): void
    {
        $model->newQuery()->whereKey($model->getKey())->update([
            $column => $model->getRawOriginal($sourceColumn),
        ]);
    }
}
