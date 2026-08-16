@if ($paginator->hasPages())
    <nav aria-label="Strony listy pracowników">
        <ul class="pagination pagination-sm mb-0">
            <li class="page-item {{ $paginator->onFirstPage() ? 'disabled' : '' }}">
                @if ($paginator->onFirstPage())
                    <span class="page-link" aria-hidden="true">&lsaquo;</span>
                @else
                    <button
                        type="button"
                        class="page-link"
                        wire:click="previousPage('{{ $paginator->getPageName() }}')"
                        wire:loading.attr="disabled"
                        aria-label="Poprzednia"
                    >&lsaquo;</button>
                @endif
            </li>

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="page-item disabled" aria-disabled="true">
                        <span class="page-link">{{ $element }}</span>
                    </li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="page-item active" wire:key="paginator-{{ $paginator->getPageName() }}-page-{{ $page }}" aria-current="page">
                                <span class="page-link">{{ $page }}</span>
                            </li>
                        @else
                            <li class="page-item" wire:key="paginator-{{ $paginator->getPageName() }}-page-{{ $page }}">
                                <button
                                    type="button"
                                    class="page-link"
                                    wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')"
                                >{{ $page }}</button>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            <li class="page-item {{ $paginator->hasMorePages() ? '' : 'disabled' }}">
                @if ($paginator->hasMorePages())
                    <button
                        type="button"
                        class="page-link"
                        wire:click="nextPage('{{ $paginator->getPageName() }}')"
                        wire:loading.attr="disabled"
                        aria-label="Następna"
                    >&rsaquo;</button>
                @else
                    <span class="page-link" aria-hidden="true">&rsaquo;</span>
                @endif
            </li>
        </ul>
    </nav>
@endif
