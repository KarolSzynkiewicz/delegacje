@php
    use App\Enums\RecruitmentCandidateFlag;

    $showAvatar = $showAvatar ?? true;
@endphp
<div class="rp-identity-bar {{ $barClass ?? '' }}">
    <div class="rp-identity-bar__who">
        @if($showAvatar)
            <div class="position-relative flex-shrink-0">
                <x-ui.avatar
                    :image-url="$candidate->photo_url"
                    :initials="mb_strtoupper(mb_substr($candidate->first_name, 0, 1).mb_substr($candidate->last_name, 0, 1))"
                    size="{{ $avatarSize ?? '36px' }}"
                    shape="rounded"
                    :border="false"
                />
                @if($candidate->rating === RecruitmentCandidateFlag::Wartosciowy)
                    <i class="bi bi-star-fill position-absolute" style="font-size:.6rem;color:#f59e0b;bottom:-2px;right:-2px;"></i>
                @elseif($candidate->rating === RecruitmentCandidateFlag::CzarnaLista)
                    <i class="bi bi-flag-fill position-absolute" style="font-size:.6rem;color:var(--danger);bottom:-2px;right:-2px;"></i>
                @endif
            </div>
        @endif
        <div class="min-width-0">
            <span class="rp-identity-bar__name">{{ $candidate->full_name }}</span>
            @if($candidate->rating)
                <span class="badge badge-{{ $candidate->rating->variant() }} ms-1" style="font-size:.58rem;">{{ $candidate->rating->label() }}</span>
            @endif
            <div class="rp-identity-bar__contact">
                @if($candidate->phone)<a href="tel:{{ $candidate->phone }}" class="text-decoration-none" style="color:inherit;">{{ $candidate->phone }}</a>@endif
                @if($candidate->phone && $candidate->email) · @endif
                @if($candidate->email)<span>{{ $candidate->email }}</span>@endif
                @if($candidate->city) · <i class="bi bi-geo-alt"></i> {{ $candidate->city }} @endif
            </div>
        </div>
    </div>
    @if(! empty($recordNav ?? null))
        <nav class="rp-record-nav"
             aria-label="Nawigacja po liście"
             x-data="{ prev: @js($recordNav['prev']), next: @js($recordNav['next']) }"
             @keydown.window="
                const el = $event.target;
                if (el && el.closest && el.closest('input, textarea, select, [contenteditable=true]')) return;
                if ($event.altKey || $event.ctrlKey || $event.metaKey || $event.shiftKey) return;
                if ($event.key === 'ArrowLeft' && prev) { $event.preventDefault(); $wire.openListNeighbor('prev'); }
                if ($event.key === 'ArrowRight' && next) { $event.preventDefault(); $wire.openListNeighbor('next'); }
             ">
            <button type="button"
                    class="rp-record-nav__btn {{ $recordNav['prev'] ? '' : 'is-disabled' }}"
                    @disabled(! $recordNav['prev'])
                    wire:click="openListNeighbor('prev')"
                    title="Poprzedni z filtra">
                <i class="bi bi-chevron-left" aria-hidden="true"></i>
                <span class="rp-record-nav__label">Poprzednie</span>
            </button>
            <span class="rp-record-nav__meta font-mono">{{ $recordNav['index'] }} / {{ $recordNav['total'] }}</span>
            <button type="button"
                    class="rp-record-nav__btn {{ $recordNav['next'] ? '' : 'is-disabled' }}"
                    @disabled(! $recordNav['next'])
                    wire:click="openListNeighbor('next')"
                    title="Następny z filtra">
                <span class="rp-record-nav__label">Następne</span>
                <i class="bi bi-chevron-right" aria-hidden="true"></i>
            </button>
        </nav>
    @endif
    <div class="rp-identity-bar__chips">
        @foreach($candidate->roles as $candidateRole)
            <span class="badge badge-info" style="font-size:.65rem;">{{ $candidateRole->name }}</span>
        @endforeach
        @if($candidate->shipyard_experience)
            <span class="badge badge-secondary" style="font-size:.65rem;"><i class="bi bi-tools me-1"></i>{{ $candidate->shipyard_experience->label() }}</span>
        @endif
        @if($candidate->expected_rate_eur !== null)
            <span class="badge badge-secondary" style="font-size:.65rem;">{{ number_format((float) $candidate->expected_rate_eur, 0) }} €/h</span>
        @endif
        @if($candidate->available_from)
            <span class="badge badge-success" style="font-size:.65rem;"><i class="bi bi-calendar-check me-1"></i>Od {{ \Carbon\Carbon::parse($candidate->available_from)->format('d.m.Y') }}</span>
        @endif
    </div>
</div>
