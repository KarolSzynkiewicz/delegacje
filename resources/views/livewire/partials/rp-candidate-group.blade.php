@php
    use App\Enums\RecruitmentCandidateFlag;

    $candHasActive = $cand->processes->contains(fn ($p) => $selectedId === $p->id);
    $isPinned = $isPinned ?? false;
@endphp
<div class="rp-cand-group {{ $candHasActive ? 'rp-cand-group--active' : '' }}" wire:key="lc-{{ $cand->id }}">
    <div class="rp-cand-group__head">
        <div class="position-relative flex-shrink-0">
            <x-ui.avatar :image-url="$cand->photo_url" :initials="mb_strtoupper(mb_substr($cand->first_name,0,1).mb_substr($cand->last_name,0,1))" size="28px" shape="rounded" :border="false" />
            @if($cand->rating === RecruitmentCandidateFlag::Wartosciowy)
                <i class="bi bi-star-fill position-absolute" style="font-size:.55rem;color:#f59e0b;bottom:-2px;right:-2px;"></i>
            @elseif($cand->rating === RecruitmentCandidateFlag::CzarnaLista)
                <i class="bi bi-flag-fill position-absolute" style="font-size:.55rem;color:var(--danger);bottom:-2px;right:-2px;"></i>
            @endif
        </div>
        <div class="flex-grow-1 min-width-0">
            <div class="rp-cand-group__name">{{ $cand->full_name }}</div>
            <div class="rp-cand-group__meta">{{ $cand->phone ?? $cand->email }}</div>
        </div>
        @if($isPinned)
            <i class="bi bi-pin-angle-fill flex-shrink-0" style="font-size:.62rem;color:var(--primary);" title="Aktualnie otwarty"></i>
        @endif
        @if($cand->processes->count() > 1)
            <span class="badge badge-secondary flex-shrink-0" style="font-size:.55rem;">{{ $cand->processes->count() }}</span>
        @endif
    </div>
    @foreach($cand->processes as $proc)
        @php
            $matchesFilter = ! $status || $proc->status?->value === $status;
            $variant = $proc->status?->variant() ?? 'secondary';
        @endphp
        <button type="button"
                wire:click="selectProcess({{ $proc->id }})"
                wire:key="li-{{ $proc->id }}"
                class="rp-cand-proc {{ $selectedId===$proc->id ? 'rp-cand-proc--active' : '' }} {{ $matchesFilter ? '' : 'rp-cand-proc--muted' }}">
            <span class="rp-cand-proc__id"><i class="bi bi-arrow-return-right"></i>#{{ $proc->id }}</span>
            <span class="rp-cand-proc__status is-{{ $variant }}">
                <span class="rp-status-dot"></span>{{ $proc->status?->label() ?? '—' }}
            </span>
        </button>
    @endforeach
</div>
