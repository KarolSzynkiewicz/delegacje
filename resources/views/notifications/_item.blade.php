@php
    $data = $n->data ?? [];
    $url = $data['resource_url'] ?? $data['task_url'] ?? $data['url'] ?? null;
    $linkLabel = $data['task_name'] ?? $data['context_name'] ?? $data['subtask_name'] ?? null;
    $excerpt = $data['excerpt'] ?? $data['comment_excerpt'] ?? null;
    $read = $n->read_at !== null;
    $type = $data['type'] ?? '';
@endphp

<div class="d-flex align-items-start gap-2">
    <i @class([
        'bi flex-shrink-0 mt-1',
        'fs-5' => $largeIcon ?? false,
        'bi-person-check-fill text-primary' => $type === 'task_assigned',
        'bi-check2-circle text-warning' => $type === 'approval_requested',
        'bi-clipboard-check text-success' => $type === 'approval_decided',
        'bi-check2-square-fill text-success' => $type === 'mention_completed',
        'bi-chat-quote-fill text-info' => $type === 'comment_mentioned',
        'bi-chat-left-text-fill text-success' => $type === 'task_comment_added',
        'bi-list-check text-info' => $type === 'subtask_mentioned',
        'bi-heart-fill text-danger' => $type === 'comment_liked',
        'bi-bell-fill text-secondary' => ! in_array($type, ['task_assigned', 'approval_requested', 'approval_decided', 'mention_completed', 'comment_mentioned', 'task_comment_added', 'subtask_mentioned', 'comment_liked'], true),
    ])></i>
    <div class="min-w-0 flex-grow-1">
        @if($url)
            <a href="{{ $url }}" class="text-decoration-none d-block" style="color: inherit;">
                @include('notifications._item-text', ['data' => $data, 'url' => $url, 'linkLabel' => $linkLabel, 'excerpt' => $excerpt, 'read' => $read, 'n' => $n])
            </a>
        @else
            @include('notifications._item-text', ['data' => $data, 'url' => $url, 'linkLabel' => $linkLabel, 'excerpt' => $excerpt, 'read' => $read, 'n' => $n])
        @endif
    </div>
</div>
