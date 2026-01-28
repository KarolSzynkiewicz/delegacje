@props([
    'paginator',
])

@php
    // Dla zwykłej paginacji Laravel, użyj getUrlRange jeśli dostępne
    if (method_exists($paginator, 'getUrlRange')) {
        $pages = $paginator->getUrlRange(1, $paginator->lastPage());
    } else {
        $pages = null;
    }
@endphp

@if ($paginator->hasPages())
    <nav class="d-flex align-items-center justify-content-between">
        <div>
            <p class="small text-muted mb-0">
                Pokazano <span class="fw-semibold">{{ $paginator->firstItem() }}</span> do <span class="fw-semibold">{{ $paginator->lastItem() }}</span> z <span class="fw-semibold">{{ $paginator->total() }}</span> wyników
            </p>
        </div>
        <div>
            <ul class="pagination mb-0">
                @if($pages)
                    @foreach ($pages as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="page-item active" aria-current="page">
                                <span class="page-link">{{ $page }}</span>
                            </li>
                        @else
                            <li class="page-item">
                                <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                            </li>
                        @endif
                    @endforeach
                @endif
            </ul>
        </div>
    </nav>
@elseif($paginator->total() > 0)
    <div>
        <p class="small text-muted mb-0">
            Pokazano <span class="fw-semibold">{{ $paginator->total() }}</span> wyników
        </p>
    </div>
@endif
