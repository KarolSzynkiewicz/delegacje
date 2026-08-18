<div class="d-flex align-items-center gap-2 min-w-0">
    @if($type->image_url)
        <img
            src="{{ $type->image_url }}"
            alt=""
            class="warehouse-issue-thumb"
        >
    @else
        <span class="warehouse-issue-thumb is-placeholder" aria-hidden="true">
            <i class="bi bi-box-seam"></i>
        </span>
    @endif
    <div class="fw-semibold text-truncate" style="font-size:.85rem;color:var(--text-main);">{{ $type->name }}</div>
    @include('livewire.partials.warehouse-issue-returnable-icon', ['type' => $type])
</div>
