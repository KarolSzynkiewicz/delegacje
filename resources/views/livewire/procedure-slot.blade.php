<div>
    @if(!$binding || !$binding->procedure_template_id)
        {{-- Unbound: nothing decided which procedure runs here yet --}}
        <div class="border rounded-3 p-3 d-flex align-items-center justify-content-between gap-3 flex-wrap"
             style="border-color: var(--glass-border) !important; background: var(--bg-card);">
            <div>
                @if($label)
                    <div class="fw-semibold small mb-1">{{ $label }}</div>
                @endif
                <div class="small text-muted">Ten punkt nie ma jeszcze przypisanej procedury.</div>
            </div>
            <x-ui.button variant="primary" class="btn-sm" wire:click="openBindModal">
                <i class="bi bi-link-45deg me-1"></i> Przypisz procedurę
            </x-ui.button>
        </div>
    @elseif($activeRun || $lastRun)
        {{-- Bound + (running, or already finished/abandoned before): show the full runner
             inline right here — no separate tab, no state lost when navigating away and back. --}}
        @php($displayRun = $activeRun ?? $lastRun)
        @if($label)
            <div class="fw-semibold small mb-2">{{ $label }}</div>
        @endif
        <livewire:procedure-run-stepper :run="$displayRun" :key="'procedure-slot-run-'.$displayRun->id" />
        @if(!$activeRun)
            <div class="d-flex align-items-center gap-2 mt-2">
                <x-ui.button variant="primary" class="btn-sm" wire:click="start">
                    <i class="bi bi-arrow-repeat me-1"></i> Uruchom ponownie
                </x-ui.button>
                <button type="button" class="btn btn-sm btn-link text-muted" wire:click="openBindModal">
                    Zmień szablon
                </button>
            </div>
        @endif
    @else
        {{-- Bound, never run for this subject yet --}}
        <div class="border rounded-3 p-3 d-flex align-items-center justify-content-between gap-3 flex-wrap"
             style="border-color: var(--glass-border) !important; background: var(--bg-card);">
            <div class="min-w-0">
                @if($label)
                    <div class="fw-semibold small mb-1">{{ $label }}</div>
                @endif
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-diagram-3 text-primary"></i>
                    <span class="fw-semibold">{{ $binding->template->name }}</span>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                <x-ui.button variant="primary" class="btn-sm" wire:click="start">
                    <i class="bi bi-play-fill me-1"></i> Uruchom
                </x-ui.button>
                <button type="button" class="btn btn-sm btn-link text-muted" wire:click="openBindModal">
                    Zmień szablon
                </button>
            </div>
        </div>
    @endif

    @if($showBindModal)
        <div class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.55);"
             wire:click.self="closeBindModal">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable mx-2">
                <div class="modal-content" style="background:var(--bg-card);border:1px solid var(--glass-border);border-radius:20px;">
                    <div class="modal-header" style="border-color:var(--glass-border)!important;">
                        <h5 class="modal-title"><i class="bi bi-link-45deg me-2"></i>Przypisz procedurę</h5>
                        <button type="button" class="btn-close" wire:click="closeBindModal"></button>
                    </div>
                    <div class="modal-body">
                        @if($label)
                            <p class="small text-muted mb-3">{{ $label }}</p>
                        @endif

                        @if($availableTemplates->isEmpty())
                            <p class="small text-muted mb-0">Nie ma jeszcze żadnych szablonów procedur.</p>
                        @else
                            <label class="form-label small fw-semibold">Szablon procedury</label>
                            <select class="form-select" wire:model="bindTemplateId">
                                <option value="">— wybierz —</option>
                                @foreach($availableTemplates as $template)
                                    <option value="{{ $template->id }}">
                                        {{ $template->name }}@if($template->category) ({{ $template->category }}) @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('bindTemplateId')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        @endif

                        <div class="mt-3">
                            <a href="{{ route('procedure-templates.index') }}" target="_blank" class="small">
                                <i class="bi bi-plus-circle me-1"></i> Stwórz nową procedurę w Kreatorze
                            </a>
                            <div class="form-text small">
                                Po utworzeniu procedury wróć tutaj i wybierz ją z listy powyżej.
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-color:var(--glass-border)!important;">
                        <x-ui.button variant="ghost" class="btn-sm" wire:click="closeBindModal">Anuluj</x-ui.button>
                        <x-ui.button variant="primary" class="btn-sm" wire:click="saveBinding">
                            <i class="bi bi-check-lg me-1"></i> Przypisz
                        </x-ui.button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
