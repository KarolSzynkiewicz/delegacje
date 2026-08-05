@props(['active' => 'candidates'])

<ul class="nav nav-tabs-ui mb-4">
    <li class="nav-item-ui">
        <a href="{{ route('recruitment-processes.index') }}"
           class="nav-link-ui {{ $active === 'candidates' ? 'active' : '' }}">
            <i class="bi bi-person-lines-fill me-1"></i>Kandydaci
        </a>
    </li>
    <li class="nav-item-ui">
        <a href="{{ route('recruitment-analytics.index') }}"
           class="nav-link-ui {{ $active === 'analytics' ? 'active' : '' }}">
            <i class="bi bi-graph-up-arrow me-1"></i>Analityka
        </a>
    </li>
</ul>
