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
            go(href, dir) {
                if (!href) return;
                try { sessionStorage.setItem('wiNavDir', dir); } catch (e) {}
                const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                if (reduce) {
                    window.location.href = href;
                    return;
                }
                document.documentElement.classList.add('wi-nav-leave', 'wi-nav-leave--' + dir);
                window.setTimeout(() => { window.location.href = href; }, 280);
            },
            start(e) {
                const el = e.target;
                if (el && el.closest && el.closest('input, textarea, select, button, a, [contenteditable=true], .pe-shell')) {
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
                this.go(dx < 0 ? this.next : this.prev, dx < 0 ? 'next' : 'prev');
            },
            key(e) {
                const el = e.target;
                if (el && (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA' || el.tagName === 'SELECT' || el.isContentEditable)) return;
                if (e.key === 'ArrowRight') this.go(this.next, 'next');
                if (e.key === 'ArrowLeft') this.go(this.prev, 'prev');
            }
         }"
         x-init="
            const dir = sessionStorage.getItem('wiNavDir');
            if (dir && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                sessionStorage.removeItem('wiNavDir');
                document.documentElement.classList.add('wi-nav-enter', 'wi-nav-enter--' + dir);
                requestAnimationFrame(() => document.documentElement.classList.add('wi-nav-enter-run'));
                setTimeout(() => {
                    document.documentElement.classList.remove('wi-nav-enter', 'wi-nav-enter--' + dir, 'wi-nav-enter-run');
                }, 420);
            }
         "
         @touchstart.document="start($event)"
         @touchend.document="end($event)"
         @keydown.window="key($event)">
        <a @if($nav['prev']) href="{{ $nav['prev']['url'] }}" @endif
           class="wi-list-nav__btn {{ $nav['prev'] ? '' : 'is-disabled' }}"
           aria-label="Poprzednie{{ $nav['prev'] ? ': '.$nav['prev']['title'] : '' }}"
           @if($nav['prev']) title="{{ $nav['prev']['title'] }}" @click.prevent="go(prev, 'prev')" @endif
           @if(! $nav['prev']) aria-disabled="true" tabindex="-1" @endif>
            <i class="bi bi-chevron-left" aria-hidden="true"></i>
            <span class="wi-list-nav__btn-label">Poprzednie</span>
        </a>

        <span class="wi-list-nav__meta font-mono">{{ $nav['index'] }} / {{ $nav['total'] }}</span>

        <a @if($nav['next']) href="{{ $nav['next']['url'] }}" @endif
           class="wi-list-nav__btn {{ $nav['next'] ? '' : 'is-disabled' }}"
           aria-label="Następne{{ $nav['next'] ? ': '.$nav['next']['title'] : '' }}"
           @if($nav['next']) title="{{ $nav['next']['title'] }}" @click.prevent="go(next, 'next')" @endif
           @if(! $nav['next']) aria-disabled="true" tabindex="-1" @endif>
            <span class="wi-list-nav__btn-label">Następne</span>
            <i class="bi bi-chevron-right" aria-hidden="true"></i>
        </a>
    </nav>
    <style>
        html.wi-nav-leave .app-background,
        html.wi-nav-enter .app-background {
            perspective: 1600px;
        }
        html.wi-nav-leave .app-content-wrapper,
        html.wi-nav-enter .app-content-wrapper {
            transform-origin: 50% 42%;
            transform-style: preserve-3d;
            will-change: transform, opacity, filter;
            backface-visibility: hidden;
        }
        html.wi-nav-leave--next .app-content-wrapper {
            animation: wi-flip-out-next .28s cubic-bezier(.4, 0, 1, 1) forwards;
        }
        html.wi-nav-leave--prev .app-content-wrapper {
            animation: wi-flip-out-prev .28s cubic-bezier(.4, 0, 1, 1) forwards;
        }
        html.wi-nav-enter--next .app-content-wrapper {
            animation: wi-flip-in-next .38s cubic-bezier(.16, 1, .3, 1) both;
        }
        html.wi-nav-enter--prev .app-content-wrapper {
            animation: wi-flip-in-prev .38s cubic-bezier(.16, 1, .3, 1) both;
        }
        @keyframes wi-flip-out-next {
            to {
                transform: rotateY(-78deg) translateX(-8%) scale(0.94);
                opacity: 0;
                filter: brightness(0.7);
            }
        }
        @keyframes wi-flip-out-prev {
            to {
                transform: rotateY(78deg) translateX(8%) scale(0.94);
                opacity: 0;
                filter: brightness(0.7);
            }
        }
        @keyframes wi-flip-in-next {
            from {
                transform: rotateY(78deg) translateX(10%) scale(0.94);
                opacity: 0;
                filter: brightness(0.7);
            }
            to {
                transform: none;
                opacity: 1;
                filter: none;
            }
        }
        @keyframes wi-flip-in-prev {
            from {
                transform: rotateY(-78deg) translateX(-10%) scale(0.94);
                opacity: 0;
                filter: brightness(0.7);
            }
            to {
                transform: none;
                opacity: 1;
                filter: none;
            }
        }
        @media (prefers-reduced-motion: reduce) {
            html.wi-nav-leave .app-background,
            html.wi-nav-enter .app-background { perspective: none; }
            html.wi-nav-leave .app-content-wrapper,
            html.wi-nav-enter .app-content-wrapper { animation: none !important; }
        }
    </style>
@endif
