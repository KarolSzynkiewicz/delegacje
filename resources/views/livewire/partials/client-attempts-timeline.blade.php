@php
    $compact = $compact ?? false;
    $canManage = $canManage ?? false;
    $expandedAttemptId = $expandedAttemptId ?? null;
    $showAttemptForm = $showAttemptForm ?? false;
    $allProjects = $allProjects ?? collect();
    $contacts = $contacts ?? collect();
    $channelOptions = $channelOptions ?? \App\Enums\ClientContactChannel::options();
    $purposeOptions = $purposeOptions ?? \App\Enums\ClientContactPurpose::options();
    $outcomeOptions = $outcomeOptions ?? \App\Enums\ClientContactOutcome::options();
@endphp

@if($attempts->isEmpty() && ! ($showAttemptForm && $canManage && ! $compact))
    <x-ui.empty-state
        icon="telephone"
        message="{{ $compact ? 'Brak kontaktów.' : 'Brak wpisów dla wybranego filtra.' }}"
    />
@else
    <div class="crm-timeline">
        @if($showAttemptForm && $canManage && ! $compact)
            @include('livewire.partials.client-attempt-form')
        @endif

        @foreach($attempts as $attempt)
            @php
                $tone = match ($attempt->outcome) {
                    \App\Enums\ClientContactOutcome::Agreed => 'agreed',
                    \App\Enums\ClientContactOutcome::Callback, \App\Enums\ClientContactOutcome::Postponed => 'warning',
                    \App\Enums\ClientContactOutcome::NoAnswer, \App\Enums\ClientContactOutcome::Rejected => 'danger',
                    default => null,
                };
                $followUpOverdue = $attempt->next_action_at && $attempt->next_action_at->isPast();
                $followUpSoon = $attempt->next_action_at
                    && ! $followUpOverdue
                    && $attempt->next_action_at->lte(now()->addDays(3));
                $isExpanded = $canManage && ! $compact && $expandedAttemptId === $attempt->id;
            @endphp
            <div
                @class([
                    'crm-timeline__item',
                    'crm-timeline__item--'.$tone => (bool) $tone && ! $isExpanded,
                    'crm-timeline__item--editing' => $isExpanded,
                    'is-expanded' => $isExpanded,
                ])
                wire:key="crm-attempt-{{ $attempt->id }}"
            >
                @if($isExpanded)
                    {{-- Rozwinięta edycja — ten sam układ i styl co nowy wpis --}}
                    <div class="crm-timeline__head">
                        <div class="crm-timeline__when">
                            <input
                                type="datetime-local"
                                class="form-control form-control-sm crm-inline"
                                value="{{ $attempt->contacted_at->format('Y-m-d\TH:i') }}"
                                wire:change="updateAttemptField({{ $attempt->id }}, 'contacted_at', $event.target.value)"
                            >
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="crm-chip crm-chip--editing">Edycja</span>
                            <select
                                class="form-select form-select-sm crm-inline"
                                style="min-width: 10rem;"
                                wire:change="updateAttemptField({{ $attempt->id }}, 'outcome', $event.target.value)"
                            >
                                @foreach($outcomeOptions as $value => $label)
                                    <option value="{{ $value }}" @selected($attempt->outcome->value === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="toggleAttemptExpanded({{ $attempt->id }})" title="Zwiń">
                                <i class="bi bi-x-lg"></i>
                            </button>
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-danger"
                                wire:click="deleteAttempt({{ $attempt->id }})"
                                wire:confirm="Usunąć ten wpis kontaktu?"
                                title="Usuń"
                            >
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>

                    <div class="crm-timeline__inline-row">
                        <div>
                            <label class="crm-inline-label">Cel</label>
                            <select class="form-select form-select-sm crm-inline" wire:change="updateAttemptField({{ $attempt->id }}, 'purpose', $event.target.value)">
                                @foreach($purposeOptions as $value => $label)
                                    <option value="{{ $value }}" @selected($attempt->purpose->value === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="crm-inline-label">Kanał</label>
                            <select class="form-select form-select-sm crm-inline" wire:change="updateAttemptField({{ $attempt->id }}, 'channel', $event.target.value)">
                                @foreach($channelOptions as $value => $label)
                                    <option value="{{ $value }}" @selected($attempt->channel->value === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="crm-inline-label">Projekt</label>
                            <select class="form-select form-select-sm crm-inline" wire:change="updateAttemptField({{ $attempt->id }}, 'project_id', $event.target.value)">
                                <option value="">— ogólny —</option>
                                @foreach($allProjects as $projectOption)
                                    <option value="{{ $projectOption->id }}" @selected((int) $attempt->project_id === (int) $projectOption->id)>
                                        {{ $projectOption->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="crm-inline-label">Osoba</label>
                            <select class="form-select form-select-sm crm-inline" wire:change="updateAttemptField({{ $attempt->id }}, 'client_contact_id', $event.target.value)">
                                <option value="">— nie wskazano —</option>
                                @foreach($contacts as $contactOption)
                                    <option value="{{ $contactOption->id }}" @selected((int) $attempt->client_contact_id === (int) $contactOption->id)>
                                        {{ $contactOption->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mt-2">
                        <label class="crm-inline-label">Notatka</label>
                        <textarea
                            class="form-control form-control-sm crm-inline crm-inline--muted"
                            rows="2"
                            wire:blur="updateAttemptField({{ $attempt->id }}, 'summary', $event.target.value)"
                            placeholder="Ustalenie / treść rozmowy…"
                        >{{ $attempt->summary }}</textarea>
                    </div>

                    <div class="mt-2">
                        <label class="crm-inline-label @if($followUpOverdue) text-danger @elseif($followUpSoon) text-warning @endif">
                            Oddzwoń
                        </label>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <input
                                type="datetime-local"
                                class="form-control form-control-sm crm-inline"
                                style="max-width: 14rem;"
                                value="{{ $attempt->next_action_at?->format('Y-m-d\TH:i') }}"
                                wire:change="updateAttemptField({{ $attempt->id }}, 'next_action_at', $event.target.value)"
                            >
                            @if($attempt->next_action_at)
                                <button type="button" class="btn btn-sm btn-outline-success" wire:click="completeFollowUp({{ $attempt->id }})">Gotowe</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="snoozeFollowUp({{ $attempt->id }}, 1)">+1d</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="snoozeFollowUp({{ $attempt->id }}, 3)">+3d</button>
                                <button type="button" class="btn btn-sm btn-outline-primary" wire:click="continueFollowUp({{ $attempt->id }})">
                                    <i class="bi bi-telephone-outbound"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                @else
                    {{-- Zwinięty podgląd — kliknij, by edytować --}}
                    <button
                        type="button"
                        class="crm-timeline__summary"
                        @if($canManage && ! $compact)
                            wire:click="toggleAttemptExpanded({{ $attempt->id }})"
                        @else
                            disabled
                        @endif
                    >
                        <div class="crm-timeline__head">
                            <div>
                                <div class="fw-semibold">{{ $attempt->purpose->label() }}</div>
                                <div class="crm-timeline__when">{{ $attempt->contacted_at->format('d.m.Y H:i') }}</div>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <x-ui.badge variant="{{ $attempt->outcome->variant() }}">{{ $attempt->outcome->label() }}</x-ui.badge>
                                @if($canManage && ! $compact)
                                    <i class="bi bi-chevron-down text-muted" aria-hidden="true"></i>
                                @endif
                            </div>
                        </div>

                        <div class="crm-timeline__chips">
                            <span class="crm-chip"><i class="bi {{ $attempt->channel->icon() }}"></i> {{ $attempt->channel->label() }}</span>
                            @if($attempt->project)
                                <span class="crm-chip"><i class="bi bi-folder"></i> {{ $attempt->project->name }}</span>
                            @else
                                <span class="crm-chip"><i class="bi bi-building"></i> Kontakt ogólny</span>
                            @endif
                            @if($attempt->contact)
                                <span class="crm-chip"><i class="bi bi-person"></i> {{ $attempt->contact->name }}</span>
                            @endif
                            @if($attempt->next_action_at)
                                <span @class(['crm-chip', 'crm-chip--overdue' => $followUpOverdue, 'crm-chip--due' => $followUpSoon])>
                                    <i class="bi bi-alarm"></i>
                                    Oddzwoń{{ $followUpOverdue ? ' (zaległe)' : '' }}:
                                    {{ $attempt->next_action_at->format('d.m.Y H:i') }}
                                </span>
                            @endif
                        </div>

                        @if($attempt->summary)
                            <p class="mb-0 mt-2 text-start">{{ \Illuminate\Support\Str::limit($attempt->summary, 160) }}</p>
                        @endif
                    </button>
                @endif
            </div>
        @endforeach
    </div>
@endif
