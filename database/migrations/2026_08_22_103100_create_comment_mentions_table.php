<?php

use App\Enums\WorkItemStatus;
use App\Models\Comment;
use App\Models\CommentMention;
use App\Models\ProcedureRun;
use App\Models\ProjectTask;
use App\Models\WorkItem;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comment_mentions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_to')->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('status', 32)->default(WorkItemStatus::Pending->value);
            $table->timestamps();

            $table->unique(['comment_id', 'assigned_to']);
        });

        $taskIdToMentionId = [];

        ProjectTask::query()
            ->where('subject_type', 'comment')
            ->orderBy('id')
            ->chunkById(100, function ($tasks) use (&$taskIdToMentionId): void {
                foreach ($tasks as $task) {
                    $comment = Comment::query()->find($task->subject_id);
                    if (! $comment || ! $task->assigned_to) {
                        $task->delete();

                        continue;
                    }

                    $status = WorkItemStatus::fromTaskStatus($task->status);
                    if ($status === WorkItemStatus::Cancelled) {
                        $task->delete();

                        continue;
                    }

                    $mention = CommentMention::query()->firstOrCreate(
                        [
                            'comment_id' => $comment->id,
                            'assigned_to' => $task->assigned_to,
                        ],
                        [
                            'created_by' => $task->created_by,
                            'title' => $task->name,
                            'status' => $status,
                        ],
                    );

                    WorkItem::query()
                        ->where('source_type', $mention->getMorphClass())
                        ->where('source_id', $mention->id)
                        ->update([
                            'sprint_id' => $task->sprint_id,
                            'due_at' => $task->due_date,
                            'priority' => $task->priority,
                        ]);

                    $taskIdToMentionId[$task->id] = $mention->id;
                    $task->delete();
                }
            });

        if ($taskIdToMentionId === []) {
            return;
        }

        ProcedureRun::query()
            ->whereNotNull('variables')
            ->each(function (ProcedureRun $run) use ($taskIdToMentionId): void {
                $variables = $run->variables ?? [];
                $old = $variables['step_mention_tasks'] ?? null;
                if (! is_array($old) || $old === []) {
                    return;
                }

                $mapped = [];
                foreach ($old as $userId => $ids) {
                    $mentionIds = [];
                    foreach ((array) $ids as $id) {
                        if (isset($taskIdToMentionId[(int) $id])) {
                            $mentionIds[] = $taskIdToMentionId[(int) $id];
                        }
                    }
                    if ($mentionIds !== []) {
                        $mapped[(string) $userId] = array_values(array_unique($mentionIds));
                    }
                }

                unset($variables['step_mention_tasks']);
                if ($mapped !== []) {
                    $variables['step_mentions'] = $mapped;
                }

                $run->update(['variables' => $variables === [] ? null : $variables]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('comment_mentions');
    }
};
