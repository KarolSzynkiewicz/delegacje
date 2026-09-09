@php
    $allAttempts = $candidate
        ? $candidate->allContactAttempts->sortByDesc('created_at')
        : collect();
@endphp
<div class="rp-doc-section rp-doc-section--contact">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div class="rp-field-label mb-0">
            <i class="bi bi-telephone me-1"></i>Historia kontaktu
            <span style="font-size:.74rem;font-weight:400;text-transform:none;letter-spacing:0;color:var(--text-muted);"> (cały kandydat)</span>
            <span class="rp-plain-tag" style="cursor:default;">{{ $allAttempts->count() }}</span>
        </div>
        <button type="button" wire:click="openContactModal" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-plus me-1"></i>Zarejestruj
        </button>
    </div>
    @if($allAttempts->isNotEmpty())
        <div class="rp-timeline">
        @foreach($allAttempts as $attempt)
            @php
                $variant = $attempt->outcome->variant();
                $canManageAttempt = $attempt->user_id === auth()->id();
            @endphp
            <div class="rp-timeline-item" wire:key="att-{{ $attempt->id }}">
                <span class="rp-status-dot rp-outcome is-{{ $variant }}"></span>
                <div class="flex-grow-1 min-width-0">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <span style="font-size:.9rem;font-weight:650;">{{ $attempt->user?->name ?? '—' }}</span>
                        <div class="d-flex align-items-start gap-1 flex-shrink-0">
                            <small style="color:var(--text-muted);font-size:.75rem;white-space:nowrap;text-align:right;line-height:1.3;"
                                   title="{{ $attempt->created_at->format('d.m.Y H:i') }}">
                                <div>{{ $attempt->created_at->diffForHumans() }}</div>
                                <div style="font-size:.68rem;opacity:.8;">{{ $attempt->created_at->format('d.m.Y H:i') }}</div>
                            </small>
                            @if($canManageAttempt)
                                <div class="rp-comment__actions">
                                    <button type="button" class="comments-icon-btn" title="Edytuj komentarz" wire:click="startEditAttempt({{ $attempt->id }})"><i class="bi bi-pencil"></i></button>
                                    <button type="button" class="comments-icon-btn is-danger" title="Usuń próbę kontaktu" wire:click="deleteAttempt({{ $attempt->id }})" onclick="return confirm('Usunąć tę próbę kontaktu?')"><i class="bi bi-trash"></i></button>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap mt-1">
                        <span class="rp-outcome is-{{ $variant }}">{{ $attempt->outcome->label() }}</span>
                        @if($attempt->recruitment_process_id === $selected->id)
                            <span class="rp-plain-tag" style="cursor:default;">bieżący proces</span>
                        @elseif($attempt->recruitmentProcess)
                            <button type="button"
                                    wire:click="selectProcess({{ $attempt->recruitment_process_id }})"
                                    class="rp-plain-tag"
                                    title="Przejdź do proc. #{{ $attempt->recruitment_process_id }}">
                                proc.&nbsp;#{{ $attempt->recruitment_process_id }}
                                @if($attempt->recruitmentProcess->status) · {{ $attempt->recruitmentProcess->status->label() }} @endif
                            </button>
                        @endif
                    </div>
                    @if($editingAttemptId === $attempt->id)
                        <div class="mt-2">
                            <textarea wire:model="editAttemptComment" class="form-control form-control-sm mb-2" rows="2" placeholder="Komentarz…"></textarea>
                            <div class="d-flex gap-2">
                                <button type="button" wire:click="saveEditAttempt" class="btn btn-sm btn-primary">Zapisz</button>
                                <button type="button" wire:click="cancelEditAttempt" class="btn btn-sm btn-outline-secondary">Anuluj</button>
                            </div>
                        </div>
                    @elseif($attempt->comment)
                        <div style="font-size:.84rem;color:var(--text-muted);margin-top:.35rem;line-height:1.45;">{{ $attempt->comment }}</div>
                    @endif
                </div>
            </div>
        @endforeach
        </div>
    @else
        <p style="color:var(--text-muted);font-size:.85rem;margin:0;">Brak prób kontaktu.</p>
    @endif
    </div>
</div>
