@php $subjectType = $this->procedureStartSubjectType(); @endphp
<select wire:model.live="newProcedureTemplateId"
        class="form-select form-select-sm @error('newProcedureTemplateId') is-invalid @enderror">
    <option value="">Szablon procedury *</option>
    @foreach($procedureTemplates as $tpl)
        <option value="{{ $tpl->id }}">{{ $tpl->name }}</option>
    @endforeach
</select>
@error('newProcedureTemplateId')
    <div class="invalid-feedback d-block" style="font-size:0.72rem">{{ $message }}</div>
@enderror

@if($subjectType)
    <select wire:model.live="newProcedureSubjectId"
            class="form-select form-select-sm @error('newProcedureSubjectId') is-invalid @enderror">
        <option value="">{{ $subjectType->label() }} *</option>
        @foreach($this->procedureStartSubjectOptions() as $option)
            <option value="{{ $option['id'] }}">{{ $option['label'] }}</option>
        @endforeach
    </select>
    @error('newProcedureSubjectId')
        <div class="invalid-feedback d-block" style="font-size:0.72rem">{{ $message }}</div>
    @enderror
@elseif($newProcedureTemplateId !== '')
    <input type="text"
           wire:model.live.debounce.300ms="newProcedureNameSuffix"
           class="form-control form-control-sm @error('newProcedureNameSuffix') is-invalid @enderror"
           maxlength="80"
           placeholder="Dopisek (opcjonalnie)"
           wire:keydown.enter="submitAdd"
           wire:keydown.escape="cancelAdd">
    @error('newProcedureNameSuffix')
        <div class="invalid-feedback d-block" style="font-size:0.72rem">{{ $message }}</div>
    @enderror
@endif

@if($this->newProcedureTaskNamePreview !== '')
    <div class="small text-muted" style="font-size:0.72rem">{{ $this->newProcedureTaskNamePreview }}</div>
@endif
