<div class="rp-pipeline-pills {{ $pillsClass }}" role="toolbar" aria-label="Filtruj listę po statusie"
     wire:key="rp-pills-{{ $pillsClass }}"
     @if(! empty($showWhenList)) x-show="listOpen" @endif>
    @foreach(\App\Enums\RecruitmentStatus::pipelineSteps() as $case)
        <button type="button"
                wire:click="toggleStatus('{{ $case->value }}')"
                class="rp-pipeline-pill {{ $status === $case->value ? 'is-active' : '' }}"
                aria-pressed="{{ $status === $case->value ? 'true' : 'false' }}">
            {{ $case->label() }}
            <span class="rp-pipeline-pill__count">{{ $counts[$case->value] ?? 0 }}</span>
        </button>
    @endforeach
</div>
@if(count($activeFilterLabels) > 0)
    <div class="rp-active-filters rp-active-filters--compact {{ $chipsClass ?? '' }}"
         @if(! empty($showWhenList)) x-show="listOpen" @endif>
        @foreach($activeFilterLabels as $filterLabel)
            <span class="rp-active-filters__chip">{{ $filterLabel }}</span>
        @endforeach
    </div>
@endif
