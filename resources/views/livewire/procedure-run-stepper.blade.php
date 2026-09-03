@php
    $activeNodes = $run->activeNodes();
    $metrics     = $run->progressMetrics();
    $progressPct = $metrics['percent'];
    $isFinished  = $run->status->value === 'finished';
    $isAbandoned = $run->status->value === 'abandoned';
    $isDone      = $isFinished || $isAbandoned;
    $definition  = $run->definition();
    $hasActiveWait = collect($activeNodes)->contains(fn ($n) => ($n['type'] ?? '') === 'wait');
@endphp

<div @if($hasActiveWait && ! $isDone) wire:poll.5s="catchUpWaits" @endif>
    <x-ui.card>
        @unless($compact)
            <div class="d-flex align-items-start justify-content-between gap-3 mb-3 flex-wrap">
                <div class="min-w-0">
                    <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                        <i class="bi bi-diagram-3 text-primary"></i>
                        <span class="fw-semibold">{{ $run->template->name ?? 'Procedura' }}</span>
                        @if($run->version)
                            <x-ui.badge variant="secondary">{{ $run->version->label() }}</x-ui.badge>
                        @endif
                        <x-ui.badge variant="{{ $run->status->badgeVariant() }}">
                            {{ $run->status->label() }}
                        </x-ui.badge>
                        @if($subjectCard = $run->sourceCard())
                            <a href="{{ $subjectCard['url'] }}"
                               class="badge bg-secondary bg-opacity-50 text-light text-decoration-none"
                               title="{{ $subjectCard['label'] }}">
                                <i class="bi {{ $subjectCard['icon'] }} me-1"></i>{{ $subjectCard['label'] }}
                            </a>
                        @endif
                    </div>
                    <div class="small text-muted">
                        Uruchomiono przez {{ $run->startedBy?->name ?? '—' }}
                        · {{ $run->started_at->format('d.m.Y H:i') }}
                        @if($run->finished_at)
                            · Zakończono {{ $run->finished_at->format('d.m.Y H:i') }}
                        @endif
                    </div>
                </div>
                @if(! $isDone)
                    <button class="btn btn-sm btn-outline-danger flex-shrink-0"
                            wire:click="abandon"
                            wire:confirm="Porzucić procedurę? Zadanie zostanie anulowane."
                            title="Porzuć procedurę">
                        <i class="bi bi-x-circle me-1"></i> Porzuć
                    </button>
                @endif
            </div>
        @endunless

        <div class="mb-3">
            <x-procedure-run-flow :run="$run" />
        </div>

        <div class="mb-3">
            <div class="d-flex justify-content-between small text-muted mb-1">
                <span>Postęp</span>
                <span>{{ $progressPct }}% · {{ $metrics['label'] }}</span>
            </div>
            <x-ui.progress :value="$progressPct" />
        </div>

        @if(count($run->path ?? []) > 1)
            <div class="d-flex flex-wrap align-items-center gap-1 mb-3 small">
                @foreach($run->path as $nodeId)
                    @php
                        $pathNode = collect($definition['nodes'] ?? [])->firstWhere('id', $nodeId);
                        $isCurrent = in_array($nodeId, $run->activeNodeIds(), true);
                    @endphp
                    @if($pathNode)
                        <span class="badge {{ $isCurrent ? 'bg-primary' : 'bg-secondary bg-opacity-50 text-muted' }}">
                            {{ $pathNode['name'] ?? $nodeId }}
                        </span>
                        @if(! $loop->last)
                            <span class="text-muted">›</span>
                        @endif
                    @endif
                @endforeach
            </div>
        @endif

        @if($isFinished)
            <div class="text-center py-4">
                <i class="bi bi-check-circle-fill text-success d-block mb-2" style="font-size:2.25rem;"></i>
                <h5 class="fw-bold mb-1">Procedura ukończona</h5>
                <p class="text-muted small mb-0">Wszystkie kroki zostały wykonane.</p>
            </div>
        @elseif($isAbandoned)
            <div class="text-center py-4">
                <i class="bi bi-stop-circle-fill text-secondary d-block mb-2" style="font-size:2.25rem;"></i>
                <h5 class="fw-bold mb-1">Procedura porzucona</h5>
                <p class="text-muted small mb-0">Przebieg został anulowany.</p>
            </div>
        @elseif($activeNodes !== [])
            @if(count($activeNodes) > 1)
                <div class="alert alert-info py-2 px-3 small mb-3">
                    <i class="bi bi-diagram-2 me-1"></i>
                    {{ count($activeNodes) }} równoległe gałęzie — każdy krok kończysz osobno.
                </div>
            @endif

            <div class="d-flex flex-column gap-3">
                @foreach($activeNodes as $node)
                    @php
                        $nodeId   = $node['id'] ?? '';
                        $nodeType = $node['type'] ?? 'task';
                        $outgoing = $run->outgoingEdges($nodeId);
                    @endphp
                    <div class="rounded-3 p-3"
                         style="background: rgba(59, 130, 246, 0.08); border: 1px solid rgba(59, 130, 246, 0.28);"
                         wire:key="active-node-{{ $run->id }}-{{ $nodeId }}">
                        <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                            <span class="badge bg-primary bg-opacity-75">
                                {{ \App\Models\ProcedureRun::nodeTypeLabel($nodeType) }}
                            </span>
                            @if($assigneeName = $this->nodeAssigneeName($node))
                                <span class="small text-muted">
                                    <i class="bi bi-person me-1"></i>{{ $assigneeName }}
                                </span>
                            @endif
                            @if(! empty($node['estimatedDuration']))
                                <span class="small text-muted">
                                    <i class="bi bi-clock me-1"></i>~{{ $node['estimatedDuration'] }} {{ $node['durationUnit'] ?? 'min' }}
                                </span>
                            @endif
                        </div>

                        <h5 class="fw-bold mb-1">{{ $node['name'] ?? '—' }}</h5>

                        @if(! empty($node['description']))
                            <p class="text-muted small mb-2">{{ $node['description'] }}</p>
                        @endif

                        @if(! empty($node['instructions']))
                            <div class="alert alert-info py-2 px-3 small mb-3">
                                <i class="bi bi-info-circle me-1"></i>
                                {!! nl2br(e($node['instructions'])) !!}
                            </div>
                        @endif

                        @if($nodeType === 'checklist')
                            @error('checklist.'.$nodeId)
                                <div class="alert alert-danger py-2 px-3 small mb-2">{{ $message }}</div>
                            @enderror
                            <div class="d-flex flex-column gap-2 mb-3">
                                @foreach($node['checklist'] ?? [] as $item)
                                    <div class="d-flex align-items-start gap-2 p-2 rounded border"
                                         style="border-color: var(--glass-border) !important;">
                                        <input type="checkbox"
                                               class="form-check-input mt-1 flex-shrink-0"
                                               wire:model.live="checklistState.{{ $nodeId }}.{{ $item['id'] }}"
                                               id="ci-{{ $nodeId }}-{{ $item['id'] }}">
                                        <label class="form-check-label flex-grow-1" for="ci-{{ $nodeId }}-{{ $item['id'] }}">
                                            <span class="fw-semibold">{{ $item['title'] }}</span>
                                            @if(! empty($item['description']))
                                                <span class="text-muted d-block small">{{ $item['description'] }}</span>
                                            @endif
                                            @if($item['optional'] ?? false)
                                                <span class="badge bg-secondary bg-opacity-25 text-muted" style="font-size:.65rem;">opcjonalne</span>
                                            @endif
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        @elseif($nodeType === 'decision')
                            @error('decision.'.$nodeId)
                                <div class="alert alert-danger py-2 px-3 small mb-2">{{ $message }}</div>
                            @enderror
                            <div class="d-flex flex-column gap-2 mb-3">
                                @foreach($outgoing as $edge)
                                    <div class="d-flex align-items-center gap-2 p-2 rounded border {{ ($selectedEdgeIds[$nodeId] ?? null) === $edge['id'] ? 'border-primary bg-primary bg-opacity-10' : '' }}"
                                         style="cursor:pointer; {{ ($selectedEdgeIds[$nodeId] ?? null) !== ($edge['id'] ?? '') ? 'border-color: var(--glass-border) !important;' : '' }}"
                                         wire:click="$set('selectedEdgeIds.{{ $nodeId }}','{{ $edge['id'] }}')">
                                        <input type="radio"
                                               class="form-check-input flex-shrink-0"
                                               wire:model.live="selectedEdgeIds.{{ $nodeId }}"
                                               value="{{ $edge['id'] }}"
                                               id="edge-{{ $nodeId }}-{{ $edge['id'] }}">
                                        <label class="form-check-label fw-semibold" for="edge-{{ $nodeId }}-{{ $edge['id'] }}" style="cursor:pointer;">
                                            {{ $edge['label'] ?: '(bez etykiety)' }}
                                            @if(! empty($edge['condition']))
                                                <span class="text-muted small ms-1">· {{ $edge['condition'] }}</span>
                                            @endif
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        @elseif($nodeType === 'wait')
                            @php
                                $waitStep = $run->steps->first(fn ($s) => $s->node_id === $nodeId && $s->completed_at === null);
                            @endphp
                            <div class="alert alert-warning py-2 px-3 small mb-3">
                                <i class="bi bi-clock-history me-1"></i>
                                Oczekiwanie: {{ $node['wait']['duration'] ?? '?' }} {{ $node['wait']['unit'] ?? 'min' }}.
                                @if($waitStep?->resume_at)
                                    Wznawia się {{ $waitStep->resume_at->format('d.m H:i') }}.
                                @endif
                                Strona sama ruszy, gdy minie czas — albo kontynuuj wcześniej.
                            </div>
                        @elseif($nodeType === 'comment')
                            @error('comment.'.$nodeId)
                                <div class="alert alert-danger py-2 px-3 small mb-2">{{ $message }}</div>
                            @enderror
                            <label class="form-label small text-muted">Komentarz do {{ $run->sourceCard()['label'] ?? 'encji' }}</label>
                            <textarea class="form-control mb-3" rows="3"
                                      wire:model="commentBodies.{{ $nodeId }}"
                                      placeholder="Co zostało ustalone / sprawdzone…"></textarea>
                        @elseif($nodeType === 'action')
                            @php $actionLabel = $this->actionLabel($node); $fields = $this->actionFields($node); @endphp
                            @error('action.'.$nodeId)
                                <div class="alert alert-danger py-2 px-3 small mb-2">{{ $message }}</div>
                            @enderror
                            @if($actionLabel)
                                <div class="small text-muted mb-2">{{ $actionLabel }}</div>
                            @else
                                <div class="alert alert-warning py-2 px-3 small mb-3">W węźle nie wybrano akcji.</div>
                            @endif
                            <div class="d-flex flex-column gap-2 mb-3">
                                @foreach($fields as $field)
                                    @php $fname = $field['name']; @endphp
                                    <label class="form-label small mb-0">{{ $field['label'] }}</label>
                                    @if(($field['type'] ?? '') === 'textarea')
                                        <textarea class="form-control form-control-sm" rows="2"
                                                  wire:model="actionPayload.{{ $nodeId }}.{{ $fname }}"></textarea>
                                    @elseif(($field['type'] ?? '') === 'select')
                                        <select class="form-select form-select-sm" wire:model="actionPayload.{{ $nodeId }}.{{ $fname }}">
                                            <option value="">— wybierz —</option>
                                            @foreach($field['options'] ?? [] as $opt)
                                                <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                                            @endforeach
                                        </select>
                                    @elseif(($field['type'] ?? '') === 'multiselect')
                                        <select class="form-select form-select-sm" multiple
                                                wire:model="actionPayload.{{ $nodeId }}.{{ $fname }}">
                                            @foreach($field['options'] ?? [] as $opt)
                                                <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                                            @endforeach
                                        </select>
                                    @else
                                        <input class="form-control form-control-sm"
                                               type="{{ $field['type'] === 'date' ? 'date' : ($field['type'] === 'number' ? 'number' : 'text') }}"
                                               @if(! empty($field['step'])) step="{{ $field['step'] }}" @endif
                                               @if(isset($field['min'])) min="{{ $field['min'] }}" @endif
                                               @if(isset($field['max'])) max="{{ $field['max'] }}" @endif
                                               wire:model="actionPayload.{{ $nodeId }}.{{ $fname }}">
                                    @endif
                                @endforeach
                            </div>
                        @elseif($nodeType === 'approval')
                            @php $approval = $this->openApprovalStep($nodeId); @endphp
                            @error('approval.'.$nodeId)
                                <div class="alert alert-danger py-2 px-3 small mb-2">{{ $message }}</div>
                            @enderror
                            @if($approval)
                                <div class="alert alert-info py-2 px-3 small mb-3">
                                    Czeka na decyzję: <strong>{{ $approval->approver?->name ?? '—' }}</strong>
                                    · <a href="{{ route('approval-requests.show', $approval) }}">otwórz wniosek</a>
                                </div>
                                @if($approval->isApprover(auth()->user()) && ! $approval->isDecided())
                                    <div class="d-flex gap-2 mb-3">
                                        <button type="button" class="btn btn-success btn-sm"
                                                wire:click="decideApproval('{{ $nodeId }}', 'approved')">
                                            Zatwierdź
                                        </button>
                                        <button type="button" class="btn btn-outline-danger btn-sm"
                                                wire:click="decideApproval('{{ $nodeId }}', 'rejected')">
                                            Odrzuć
                                        </button>
                                    </div>
                                @endif
                            @endif
                        @endif

                        @if(! $isDone && $nodeType !== 'approval')
                            <div class="d-flex gap-2 justify-content-between flex-wrap">
                                <button type="button"
                                        class="btn btn-outline-secondary btn-sm flex-grow-1 flex-sm-grow-0"
                                        wire:click="goBackNode('{{ $nodeId }}')">
                                    <i class="bi bi-arrow-left me-1"></i> Wstecz
                                </button>
                                <button type="button"
                                        class="btn btn-primary btn-sm flex-grow-1 flex-sm-grow-0"
                                        wire:click="advanceNode('{{ $nodeId }}')">
                                    @if($nodeType === 'decision')
                                        <i class="bi bi-arrow-right-circle me-1"></i> Wybierz opcję
                                    @elseif($nodeType === 'comment')
                                        <i class="bi bi-chat-text me-1"></i> Zapisz komentarz
                                    @elseif($nodeType === 'action')
                                        <i class="bi bi-lightning me-1"></i> Wykonaj i dalej
                                    @elseif($nodeType === 'wait')
                                        <i class="bi bi-skip-forward me-1"></i> Kontynuuj wcześniej
                                    @else
                                        <i class="bi bi-check-circle me-1"></i> Oznacz jako wykonane
                                    @endif
                                </button>
                            </div>
                        @elseif(! $isDone)
                            <button type="button"
                                    class="btn btn-outline-secondary btn-sm"
                                    wire:click="goBackNode('{{ $nodeId }}')">
                                <i class="bi bi-arrow-left me-1"></i> Wstecz
                            </button>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        @if($run->steps->whereNotNull('completed_at')->isNotEmpty())
            <hr class="my-3">
            <div class="mb-2">
                <span class="small fw-semibold text-muted text-uppercase" style="letter-spacing:.05em;">
                    <i class="bi bi-list-ol me-1"></i>Historia kroków
                </span>
            </div>
            <div class="procedure-step-history-list">
                @foreach($run->steps->whereNotNull('completed_at') as $step)
                    @php
                        $node = $run->findNodeById($step->node_id);
                        $frame = $step->historyFrame($node);
                        $outcome = $step->historyOutcome();
                        $assigneeName = $frame['assignee_id'] > 0
                            ? (($historyAssignees ?? [])[$frame['assignee_id']] ?? null)
                            : null;
                        $performerName = $step->performedBy?->name;
                        $whenName = $assigneeName
                            ? (($performerName && $performerName !== $assigneeName) ? $performerName : null)
                            : $performerName;
                    @endphp
                    <div class="procedure-step-history" style="--step-color: {{ $frame['color'] }}">
                        <span class="procedure-step-history__icon" title="{{ $frame['type_label'] }}">
                            <i class="bi {{ $frame['bi'] }}"></i>
                        </span>
                        <div class="procedure-step-history__body min-w-0">
                            <div class="procedure-step-history__head">
                                <div class="min-w-0">
                                    <div class="procedure-step-history__name">{{ $frame['name'] }}</div>
                                    @if($frame['show_type'])
                                        <div class="procedure-step-history__type">{{ $frame['type_label'] }}</div>
                                    @endif
                                </div>
                                <div class="procedure-step-history__when">
                                    @if($whenName)
                                        <span>{{ $whenName }}</span>
                                    @endif
                                    <span>{{ $step->completed_at->format('d.m H:i') }}</span>
                                </div>
                            </div>
                            @if($frame['description'])
                                <p class="procedure-step-history__desc">{!! nl2br(e($frame['description'])) !!}</p>
                            @endif
                            @if($assigneeName)
                                <div class="procedure-step-history__assignee">
                                    <i class="bi bi-person"></i>
                                    {{ $assigneeName }}
                                </div>
                            @endif
                            @if($outcome)
                                <div @class([
                                    'procedure-step-history__outcome',
                                    'is-ok' => ($outcome['tone'] ?? null) === 'ok',
                                    'is-no' => ($outcome['tone'] ?? null) === 'no',
                                ])>
                                    <span>{{ $outcome['text'] }}</span>
                                    @if(! empty($outcome['url']))
                                        <a href="{{ $outcome['url'] }}" class="procedure-step-history__link">zobacz</a>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-ui.card>
</div>
