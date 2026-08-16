@php
    $filterId = 'employee-picker-filter-'.$this->getId();
@endphp
<div>
    @if(session()->has('warning'))
        <div class="alert alert-warning py-2 small mb-3">{{ session('warning') }}</div>
    @endif

    @if($showCard)
        <x-ui.card :label="$label" class="mb-4">
            <div class="px-2 pb-2">
                @include('livewire.partials.employee-picker-body', ['filterId' => $filterId])
            </div>
        </x-ui.card>
    @else
        <div class="mb-3">
            @if(filled($label))
                <label class="form-label" for="{{ $filterId }}">
                    {{ $label }}
                    @if($required)
                        <span class="text-danger">*</span>
                    @endif
                </label>
            @endif
            @include('livewire.partials.employee-picker-body', ['filterId' => $filterId])
        </div>
    @endif
</div>
