@include('tasks.partials.task-body', [
    'task' => $task,
    'showSubtasks' => true,
    'showComments' => true,
    'showActivity' => true,
])
