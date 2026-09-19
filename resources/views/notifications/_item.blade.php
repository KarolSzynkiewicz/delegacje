@php
    $data = $n->data ?? [];
    $url = $data['resource_url'] ?? $data['task_url'] ?? $data['url'] ?? null;
    $linkLabel = $data['task_name'] ?? $data['context_name'] ?? $data['subtask_name'] ?? null;
    $excerpt = $data['excerpt'] ?? $data['comment_excerpt'] ?? null;
    $read = $n->read_at !== null;
    $type = $data['type'] ?? '';
    $event = \App\Enums\NotificationEvent::tryFrom($type);
    $icon = $event?->icon() ?? 'bi-bell-fill text-secondary';
    $href = route('notifications.open', $n->id);
@endphp

<div class="d-flex align-items-start gap-2">
    <i @class([
        'bi flex-shrink-0 mt-1',
        'fs-5' => $largeIcon ?? false,
        $icon,
    ])></i>
    <div class="min-w-0 flex-grow-1">
        <a href="{{ $href }}" class="text-decoration-none d-block" style="color: inherit;">
            @include('notifications._item-text', ['data' => $data, 'url' => $url, 'linkLabel' => $linkLabel, 'excerpt' => $excerpt, 'read' => $read, 'n' => $n])
        </a>
    </div>
</div>
