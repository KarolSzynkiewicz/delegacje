            @if($showRejectionPrompt)
                <div class="mt-3 pt-3" style="border-top:1px solid var(--glass-border);">
                    <div class="rp-section-title" style="color:var(--danger);">Powód odrzucenia</div>
                    <select wire:model="rejectionReason" class="form-select form-select-sm mb-2">
                        <option value="">— Wybierz powód —</option>
                        @foreach(RecruitmentRejectionReason::options() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('rejectionReason') <div class="small mb-2" style="color:var(--danger);">{{ $message }}</div> @enderror
                    <textarea wire:model="rejectionNote" class="form-control mb-2" rows="2" style="font-size:.82rem;" placeholder="Komentarz (opcjonalnie)…"></textarea>
                    <div class="d-flex gap-2">
                        <button type="button" wire:click="confirmRejection" class="btn btn-danger btn-sm"><i class="bi bi-x-circle me-1"></i>Potwierdź odrzucenie</button>
                        <button type="button" wire:click="cancelRejection" class="btn btn-outline-secondary btn-sm">Anuluj</button>
                    </div>
                </div>
            @endif
