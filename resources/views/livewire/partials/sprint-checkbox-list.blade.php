@php
    $items = $items ?? collect();
    $canMutate = $canMutate ?? false;
    $empty = $empty ?? 'Brak pozycji.';
    $placeholder = $placeholder ?? 'Nowa pozycja…';
    $addMethod = $addMethod ?? 'addItem';
    $toggleMethod = $toggleMethod ?? 'toggleItem';
    $deleteMethod = $deleteMethod ?? 'deleteItem';
    $newName = $newName ?? 'newName';
    $keyPrefix = $keyPrefix ?? 'item';
@endphp

<div class="sb-stack">
    <div class="sb-stack-body">
        @forelse($items as $item)
            <div class="d-flex align-items-start gap-1" wire:key="{{ $keyPrefix }}-{{ $item->id }}-{{ $item->isCompleted() ? '1' : '0' }}">
                <label class="sb-check flex-grow-1 {{ $item->isCompleted() ? 'is-done' : '' }}">
                    @if($canMutate)
                        <input type="checkbox" @checked($item->isCompleted()) wire:click.prevent="{{ $toggleMethod }}({{ $item->id }})">
                    @else
                        <input type="checkbox" @checked($item->isCompleted()) disabled>
                    @endif
                    <span>{{ $item->name }}</span>
                </label>
                @if($canMutate)
                    <button type="button" class="btn btn-sm btn-link sb-ghost p-0" wire:click="{{ $deleteMethod }}({{ $item->id }})">
                        <i class="bi bi-x"></i>
                    </button>
                @endif
            </div>
        @empty
            <div class="text-muted small">{{ $empty }}</div>
        @endforelse
    </div>

    @if($canMutate)
        <div class="sb-add-wrap">
            <div class="sb-add">
                <input type="text" class="form-control form-control-sm" placeholder="{{ $placeholder }}"
                       wire:model="{{ $newName }}" wire:keydown.enter="{{ $addMethod }}">
                <button type="button" class="btn btn-sm btn-outline-primary flex-shrink-0" wire:click="{{ $addMethod }}">Dodaj</button>
            </div>
            @error($newName) <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        </div>
    @endif
</div>
