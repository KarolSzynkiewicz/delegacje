@php
    $canManage = $canManage ?? false;
    $milestones = $milestones ?? collect();
    $doneCount = $milestones->where('is_done', true)->count();
    $totalCount = $milestones->count();
@endphp

<div class="pj-panel cl-reveal">
    <div class="pj-panel__head">
        <h4 class="pj-panel__title">Kamienie milowe</h4>
        @if($totalCount > 0)
            <span class="pj-panel__hint font-mono">{{ $doneCount }}/{{ $totalCount }}</span>
        @endif
    </div>

    @if($milestones->isNotEmpty())
        <ul class="list-unstyled mb-3 pm-list">
            @foreach($milestones as $milestone)
                <li class="pm-item" wire:key="milestone-{{ $milestone->id }}">
                    @if($canManage)
                        <label class="pm-check">
                            <input
                                type="checkbox"
                                class="form-check-input"
                                @checked($milestone->is_done)
                                wire:click="toggleMilestone({{ $milestone->id }})"
                            >
                            <span @class(['pm-title', 'pm-title--done' => $milestone->is_done])>
                                {{ $milestone->title }}
                            </span>
                        </label>
                        <button
                            type="button"
                            class="btn btn-link btn-sm text-danger p-0"
                            wire:click="deleteMilestone({{ $milestone->id }})"
                            wire:confirm="Usunąć ten kamień milowy?"
                            title="Usuń"
                        >
                            <i class="bi bi-trash"></i>
                        </button>
                    @else
                        <div class="pm-check">
                            <i class="bi {{ $milestone->is_done ? 'bi-check-square-fill text-success' : 'bi-square' }}"></i>
                            <span @class(['pm-title', 'pm-title--done' => $milestone->is_done])>
                                {{ $milestone->title }}
                            </span>
                        </div>
                    @endif
                </li>
            @endforeach
        </ul>
    @else
        <div class="pm-empty mb-3">
            <i class="bi bi-flag"></i>
            Brak etapów — dodaj kolejność prac.
        </div>
    @endif

    @if($canManage)
        <div class="d-flex gap-2 align-items-start">
            <div class="flex-grow-1">
                <input
                    type="text"
                    class="form-control form-control-sm @error('newMilestoneTitle') is-invalid @enderror"
                    placeholder="Np. Kick-off, dostawa, odbiór…"
                    wire:model="newMilestoneTitle"
                    wire:keydown.enter.prevent="addMilestone"
                >
                @error('newMilestoneTitle') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <x-ui.button variant="primary" type="button" class="btn-sm" wire:click="addMilestone">
                Dodaj
            </x-ui.button>
        </div>
    @endif
</div>
