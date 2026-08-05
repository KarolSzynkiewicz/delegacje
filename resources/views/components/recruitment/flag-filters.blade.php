@props(['flag' => '', 'flagCounts' => null])

@foreach([
    \App\Enums\RecruitmentCandidateFlag::Wartosciowy->value => ['Wartościowy', 'bi-star-fill', 'btn-success'],
    \App\Enums\RecruitmentCandidateFlag::CzarnaLista->value => ['Czarna lista', 'bi-flag-fill', 'btn-danger'],
] as $value => [$label, $icon, $activeClass])
    <button type="button" wire:click="toggleFlag('{{ $value }}')"
            class="btn btn-sm rp-topbar-btn {{ $flag === $value ? $activeClass : 'btn-outline-secondary' }}"
            title="{{ $flag === $value ? 'Wyłącz filtr' : 'Pokaż tylko: '.$label }}">
        <i class="bi {{ $icon }} me-1"></i>{{ $label }}
        @if($flagCounts?->has($value))
            <span class="badge ms-1" style="font-size:.6rem;">{{ $flagCounts[$value] }}</span>
        @endif
    </button>
@endforeach
