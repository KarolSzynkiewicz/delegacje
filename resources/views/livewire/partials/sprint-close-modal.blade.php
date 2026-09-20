@if($showCloseModal)
    <div class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true" style="background:rgba(0,0,0,.65);" wire:click.self="cancelClose">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Zakończ sprint „{{ $closingSprintName }}”</h5>
                    <button type="button" class="btn-close" wire:click="cancelClose"></button>
                </div>
                <div class="modal-body">
                    @error('close')
                        <div class="text-danger small mb-2">{{ $message }}</div>
                    @enderror

                    <div class="form-check mb-3">
                        <input id="unpin-open-{{ $this->getId() }}" type="checkbox" class="form-check-input" wire:model="unpinOpen">
                        <label class="form-check-label" for="unpin-open-{{ $this->getId() }}">
                            Odpnij otwarte zadania do backlogu
                            @if($closeOpenCount > 0)
                                <span class="text-muted">({{ $closeOpenCount }})</span>
                            @endif
                        </label>
                        <div class="small text-muted">Zrobione i anulowane zostają w sprincie.</div>
                    </div>

                    <label class="form-label" for="close-note-{{ $this->getId() }}">Komentarz zamknięcia (opcjonalnie)</label>
                    <textarea
                        id="close-note-{{ $this->getId() }}"
                        class="form-control"
                        rows="3"
                        wire:model="closeNote"
                        placeholder="Co weszło, co wypadło, czego nie robić następnym razem."
                    ></textarea>
                    @error('closeNote')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>
                <div class="modal-footer">
                    <x-ui.button variant="ghost" type="button" wire:click="cancelClose">Anuluj</x-ui.button>
                    <x-ui.button variant="primary" type="button" wire:click="confirmClose">Zakończ</x-ui.button>
                </div>
            </div>
        </div>
    </div>
@endif
