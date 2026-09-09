{{-- Etap prowadzony procedurą: weryfikacja / onboarding / zatrudnienie --}}
<div class="rp-doc-section rp-doc-section--procedure">
    <div class="rp-field-label">
        <i class="bi bi-diagram-3 me-1"></i>{{ $reviewStatus->procedureSlotLabel() }}
    </div>
    <livewire:procedure-slot
        :slot-key="$reviewSlotKey"
        :subject="$selected"
        :variables="['candidate_name' => $selected->candidate?->full_name, 'recruitment_process_id' => $selected->id]"
        :subject-label="($selected->candidate?->full_name ?? 'Kandydat').' #'.$selected->id"
        wire:key="proc-slot-{{ $reviewSlotKey }}-{{ $selected->id }}"
    />
</div>
