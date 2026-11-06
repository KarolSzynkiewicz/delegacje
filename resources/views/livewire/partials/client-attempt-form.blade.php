{{-- Nowy wpis — ten sam układ co rozwinięty element timeline. --}}
<div class="crm-timeline mb-3">
    <div class="crm-timeline__item crm-timeline__item--draft" wire:key="crm-attempt-draft">
        <div class="crm-timeline__head">
            <div class="crm-timeline__when">
                <input
                    type="datetime-local"
                    class="form-control form-control-sm crm-inline @error('attemptContactedAt') is-invalid @enderror"
                    wire:model="attemptContactedAt"
                >
                @error('attemptContactedAt') <div class="crm-inline-error">{{ $message }}</div> @enderror
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="crm-chip crm-chip--draft">Nowy wpis</span>
                <select
                    class="form-select form-select-sm crm-inline"
                    style="min-width: 10rem;"
                    wire:model="attemptOutcome"
                >
                    @foreach($outcomeOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="crm-timeline__inline-row">
            <div>
                <label class="crm-inline-label">Cel</label>
                <select class="form-select form-select-sm crm-inline" wire:model="attemptPurpose">
                    @foreach($purposeOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="crm-inline-label">Kanał</label>
                <select class="form-select form-select-sm crm-inline" wire:model="attemptChannel">
                    @foreach($channelOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="crm-inline-label">Projekt</label>
                <select class="form-select form-select-sm crm-inline @error('attemptProjectId') is-invalid @enderror" wire:model="attemptProjectId">
                    <option value="">— ogólny —</option>
                    @foreach($allProjects as $projectOption)
                        <option value="{{ $projectOption->id }}">{{ $projectOption->name }}</option>
                    @endforeach
                </select>
                @error('attemptProjectId') <div class="crm-inline-error">{{ $message }}</div> @enderror
            </div>
            <div>
                <label class="crm-inline-label">Osoba</label>
                <select class="form-select form-select-sm crm-inline" wire:model="attemptContactId">
                    <option value="">— nie wskazano —</option>
                    @foreach($contacts as $contactOption)
                        <option value="{{ $contactOption->id }}">{{ $contactOption->label() }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-2">
            <label class="crm-inline-label">Notatka</label>
            <textarea
                class="form-control form-control-sm crm-inline crm-inline--muted @error('attemptSummary') is-invalid @enderror"
                rows="2"
                wire:model="attemptSummary"
                placeholder="Ustalenie / treść rozmowy…"
            ></textarea>
            @error('attemptSummary') <div class="crm-inline-error">{{ $message }}</div> @enderror
        </div>

        <div class="mt-2">
            <label class="crm-inline-label">Oddzwoń</label>
            <input
                type="datetime-local"
                class="form-control form-control-sm crm-inline @error('attemptNextActionAt') is-invalid @enderror"
                style="max-width: 14rem;"
                wire:model="attemptNextActionAt"
            >
            <div class="form-text">Opcjonalnie — zostaw puste, jeśli nie trzeba wracać.</div>
            @error('attemptNextActionAt') <div class="crm-inline-error">{{ $message }}</div> @enderror
        </div>

        <div class="d-flex justify-content-end gap-2 mt-3">
            <x-ui.button variant="ghost" type="button" class="btn-sm" wire:click="cancelAttemptForm">Anuluj</x-ui.button>
            <x-ui.button variant="primary" type="button" class="btn-sm" wire:click="saveAttempt">
                Zapisz wpis
            </x-ui.button>
        </div>
    </div>
</div>
