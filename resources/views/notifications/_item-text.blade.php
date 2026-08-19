<p class="mb-0 {{ ($compact ?? true) ? 'small' : '' }} lh-sm">
    <span class="{{ $read ? 'text-muted' : 'fw-semibold' }}">
        {{ $data['message'] ?? 'Powiadomienie' }}
    </span>
    @if($linkLabel)
        <span class="text-muted">—</span>
        <span class="fw-semibold" style="color: var(--text-main);">{{ $linkLabel }}</span>
    @endif
</p>
@if(filled($excerpt))
    <p class="mb-0 text-muted text-break" style="font-size:.75rem;">{{ $excerpt }}</p>
@endif
<p class="mb-0 text-muted" style="font-size:{{ ($compact ?? true) ? '.7rem' : '.8rem' }};">
    {{ $n->created_at->diffForHumans() }}
    @if(! $read)
        · <span class="text-warning fw-semibold">nowe</span>
    @endif
</p>
