@php
    $currentId = (int) request()->query(\App\Support\WorkItemListNavigator::QUERY_KEY, 0);
    $nav = request()->routeIs('tasks.grid', 'tasks.grid.alias')
        ? null
        : \App\Support\WorkItemListNavigator::neighbors($currentId);
@endphp

@if($nav)
    <nav class="wi-list-nav"
         aria-label="Nawigacja po liście backlogu"
         x-data="{
            prev: @js($nav['prev']['url'] ?? null),
            next: @js($nav['next']['url'] ?? null),
            x0: null,
            y0: null,
            go(href) { if (href) window.location.href = href; },
            start(e) {
                const el = e.target;
                if (el && el.closest && el.closest('input, textarea, select, button, a, [contenteditable=true]')) {
                    this.x0 = null;
                    return;
                }
                const t = e.changedTouches[0];
                if (t.clientX < 24) {
                    this.x0 = null;
                    return;
                }
                this.x0 = t.clientX;
                this.y0 = t.clientY;
            },
            end(e) {
                if (this.x0 === null) return;
                const t = e.changedTouches[0];
                const dx = t.clientX - this.x0;
                const dy = t.clientY - this.y0;
                this.x0 = null;
                if (Math.abs(dx) < 72 || Math.abs(dx) < Math.abs(dy) * 1.25) return;
                this.go(dx < 0 ? this.next : this.prev);
            },
            key(e) {
                const el = e.target;
                if (el && (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA' || el.tagName === 'SELECT' || el.isContentEditable)) return;
                if (e.key === 'ArrowRight') this.go(this.next);
                if (e.key === 'ArrowLeft') this.go(this.prev);
            }
         }"
         @touchstart.document="start($event)"
         @touchend.document="end($event)"
         @keydown.window="key($event)">
        <a @if($nav['prev']) href="{{ $nav['prev']['url'] }}" @endif
           class="wi-list-nav__btn {{ $nav['prev'] ? '' : 'is-disabled' }}"
           aria-label="Poprzednie{{ $nav['prev'] ? ': '.$nav['prev']['title'] : '' }}"
           @if($nav['prev']) title="{{ $nav['prev']['title'] }}" @endif
           @if(! $nav['prev']) aria-disabled="true" tabindex="-1" @endif>
            <i class="bi bi-chevron-left" aria-hidden="true"></i>
            <span class="wi-list-nav__btn-label">Poprzednie</span>
        </a>

        <span class="wi-list-nav__meta font-mono">{{ $nav['index'] }} / {{ $nav['total'] }}</span>

        <a @if($nav['next']) href="{{ $nav['next']['url'] }}" @endif
           class="wi-list-nav__btn {{ $nav['next'] ? '' : 'is-disabled' }}"
           aria-label="Następne{{ $nav['next'] ? ': '.$nav['next']['title'] : '' }}"
           @if($nav['next']) title="{{ $nav['next']['title'] }}" @endif
           @if(! $nav['next']) aria-disabled="true" tabindex="-1" @endif>
            <span class="wi-list-nav__btn-label">Następne</span>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
        </a>
    </nav>
@endif
