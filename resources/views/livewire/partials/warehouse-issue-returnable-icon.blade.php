@if($type->returnable)
    <span
        class="warehouse-issue-returnable"
        x-data="{ tip: false }"
        x-on:mouseenter="tip = true"
        x-on:mouseleave="tip = false"
        x-on:click.stop
        title="Do zwrotu"
        aria-label="Do zwrotu"
    >
        <i class="bi bi-recycle" aria-hidden="true"></i>
        <span class="warehouse-issue-tip" x-show="tip" x-cloak>Do zwrotu</span>
    </span>
@endif
