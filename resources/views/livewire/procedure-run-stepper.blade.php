@php
    $node       = $run->currentNode();
    $nodeType   = $node['type'] ?? 'task';
    $progress   = $run->progress();
    $progressPct = round($progress * 100);
    $stepCount  = count($run->path ?? []);
    $totalNodes = count(array_filter($run->definition_snapshot['nodes'] ?? [], fn($n) => ($n['type'] ?? '') !== 'note'));
    $isFinished = $run->status->value === 'finished';
    $isAbandoned = $run->status->value === 'abandoned';
    $isDone     = $isFinished || $isAbandoned;

    // Outgoing edges for decision
    $outgoing = $node ? $run->outgoingEdges($node['id'] ?? '') : [];
@endphp

<div>
    <x-ui.card>
        {{-- Header --}}
        <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <i class="bi bi-diagram-3 text-primary"></i>
                    <span class="fw-semibold">Procedura: {{ $run->template->name ?? '—' }}</span>
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
            @if(!$isDone)
                <button class="btn btn-sm btn-outline-danger"
                        wire:click="abandon"
                        wire:confirm="Porzucić procedurę? Zadanie zostanie anulowane."
                        title="Porzuć procedurę">
                    <i class="bi bi-x-circle me-1"></i> Porzuć
                </button>
            @endif
        </div>

        {{-- Progress bar --}}
        <div class="mb-3">
            <div class="d-flex justify-content-between small text-muted mb-1">
                <span>Postęp</span>
                <span>{{ $progressPct }}% · krok {{ $stepCount }} z ~{{ $totalNodes }}</span>
            </div>
            <div class="progress" style="height: 6px;">
                <div class="progress-bar bg-primary" style="width: {{ $progressPct }}%"></div>
            </div>
        </div>

        {{-- Breadcrumb path --}}
        @if(count($run->path ?? []) > 1)
            <div class="d-flex flex-wrap gap-1 mb-3 small">
                @foreach($run->path as $i => $nodeId)
                    @php
                        $pathNode = collect($run->definition_snapshot['nodes'] ?? [])->firstWhere('id', $nodeId);
                        $isCurrent = $nodeId === $run->current_node_id;
                    @endphp
                    @if($pathNode)
                        <span class="badge {{ $isCurrent ? 'bg-primary' : 'bg-secondary bg-opacity-50 text-muted' }}">
                            {{ $pathNode['name'] ?? $nodeId }}
                        </span>
                        @if(!$loop->last)
                            <span class="text-muted">›</span>
                        @endif
                    @endif
                @endforeach
            </div>
        @endif

        {{-- ═══ FINISHED ═══ --}}
        @if($isFinished)
            <div class="text-center py-4">
                <div class="mb-3" style="font-size: 3rem;">✅</div>
                <h5 class="fw-bold mb-1">Procedura ukończona</h5>
                <p class="text-muted small mb-0">Wszystkie kroki zostały wykonane.</p>
            </div>

        {{-- ═══ ABANDONED ═══ --}}
        @elseif($isAbandoned)
            <div class="text-center py-4">
                <div class="mb-3" style="font-size: 3rem;">🛑</div>
                <h5 class="fw-bold mb-1">Procedura porzucona</h5>
                <p class="text-muted small mb-0">Przebieg został anulowany.</p>
            </div>

        {{-- ═══ CURRENT STEP ═══ --}}
        @elseif($node)
            <div class="border rounded-3 p-3 mb-3" style="border-color: var(--glass-border) !important;">
                {{-- Step badge --}}
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-secondary bg-opacity-50 text-light small">
                        Krok · {{ \App\Enums\ProcedureRunStatus::IN_PROGRESS->label() }}
                        @if($assigneeName = $this->nodeAssigneeName($node))
                            · {{ $assigneeName }}
                        @endif
                    </span>
                    @if(!empty($node['estimatedDuration']))
                        <span class="badge bg-secondary bg-opacity-25 text-muted small">
                            <i class="bi bi-clock me-1"></i>~{{ $node['estimatedDuration'] }} {{ $node['durationUnit'] ?? 'min' }}
                        </span>
                    @endif
                </div>

                {{-- Node name --}}
                <h5 class="fw-bold mb-1">{{ $node['name'] ?? '—' }}</h5>

                @if(!empty($node['description']))
                    <p class="text-muted small mb-2">{{ $node['description'] }}</p>
                @endif

                @if(!empty($node['instructions']))
                    <div class="alert alert-info py-2 px-3 small mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        {!! nl2br(e($node['instructions'])) !!}
                    </div>
                @endif

                {{-- ─── CHECKLIST ─── --}}
                @if($nodeType === 'checklist')
                    @error('checklist')
                        <div class="alert alert-danger py-2 px-3 small mb-2">{{ $message }}</div>
                    @enderror
                    <div class="d-flex flex-column gap-2 mb-3">
                        @foreach($node['checklist'] ?? [] as $item)
                            <div class="d-flex align-items-start gap-2 p-2 rounded border"
                                 style="border-color: var(--glass-border) !important;">
                                <input type="checkbox"
                                       class="form-check-input mt-1 flex-shrink-0"
                                       wire:model.live="checklistState.{{ $node['id'] }}.{{ $item['id'] }}"
                                       id="ci-{{ $item['id'] }}"
                                       {{ $isDone ? 'disabled' : '' }}>
                                <label class="form-check-label flex-grow-1" for="ci-{{ $item['id'] }}">
                                    <span class="fw-semibold">{{ $item['title'] }}</span>
                                    @if(!empty($item['description']))
                                        <span class="text-muted d-block small">{{ $item['description'] }}</span>
                                    @endif
                                    @if($item['optional'] ?? false)
                                        <span class="badge bg-secondary bg-opacity-25 text-muted" style="font-size:.65rem;">opcjonalne</span>
                                    @endif
                                </label>
                            </div>
                        @endforeach
                    </div>

                {{-- ─── DECISION ─── --}}
                @elseif($nodeType === 'decision')
                    @error('decision')
                        <div class="alert alert-danger py-2 px-3 small mb-2">{{ $message }}</div>
                    @enderror
                    <div class="d-flex flex-column gap-2 mb-3">
                        @foreach($outgoing as $edge)
                            <div class="d-flex align-items-center gap-2 p-2 rounded border {{ $selectedEdgeId === $edge['id'] ? 'border-primary bg-primary bg-opacity-10' : '' }}"
                                 style="{{ $selectedEdgeId !== ($edge['id'] ?? '') ? 'border-color: var(--glass-border) !important;' : '' }}"
                                 wire:click="$set('selectedEdgeId','{{ $edge['id'] }}')"
                                 style="cursor:pointer;">
                                <input type="radio"
                                       class="form-check-input flex-shrink-0"
                                       wire:model.live="selectedEdgeId"
                                       value="{{ $edge['id'] }}"
                                       id="edge-{{ $edge['id'] }}">
                                <label class="form-check-label fw-semibold" for="edge-{{ $edge['id'] }}" style="cursor:pointer;">
                                    {{ $edge['label'] ?: '(bez etykiety)' }}
                                    @if(!empty($edge['condition']))
                                        <span class="text-muted small ms-1">· {{ $edge['condition'] }}</span>
                                    @endif
                                </label>
                            </div>
                        @endforeach
                    </div>

                {{-- ─── WAIT ─── --}}
                @elseif($nodeType === 'wait')
                    <div class="alert alert-warning py-2 px-3 small mb-3">
                        <i class="bi bi-clock-history me-1"></i>
                        Oczekiwanie: {{ $node['wait']['duration'] ?? '?' }} {{ $node['wait']['unit'] ?? 'min' }}.
                        Kontynuuj gdy warunek jest spełniony.
                    </div>

                {{-- ─── END ─── --}}
                @elseif($nodeType === 'end')
                    <div class="text-center py-2">
                        <div class="mb-2" style="font-size: 2rem;">🏁</div>
                        <p class="text-muted small">Dotarłeś do końca procedury.</p>
                    </div>
                @endif
            </div>

            {{-- ─── NAVIGATION BUTTONS ─── --}}
            @if(!$isDone)
                <div class="d-flex gap-2 justify-content-between">
                    <button class="btn btn-outline-secondary btn-sm"
                            wire:click="goBack"
                            {{ count($run->path ?? []) <= 1 ? 'disabled' : '' }}>
                        <i class="bi bi-arrow-left me-1"></i> Wstecz
                    </button>
                    <button class="btn btn-primary btn-sm"
                            wire:click="advance">
                        @if($nodeType === 'end')
                            <i class="bi bi-check2 me-1"></i> Zakończ procedurę
                        @elseif($nodeType === 'decision')
                            <i class="bi bi-arrow-right-circle me-1"></i> Wybierz opcję
                        @elseif($nodeType === 'note')
                            <i class="bi bi-arrow-right me-1"></i> Kontynuuj
                        @else
                            <i class="bi bi-check-circle me-1"></i> Oznacz jako wykonane
                        @endif
                    </button>
                </div>
            @endif
        @endif

        {{-- ─── STEP LOG ─── --}}
        @if($run->steps->isNotEmpty())
            <hr class="my-3">
            <div class="mb-2">
                <span class="small fw-semibold text-muted text-uppercase" style="letter-spacing:.05em;">
                    <i class="bi bi-list-ol me-1"></i>Historia kroków
                </span>
            </div>
            <div class="d-flex flex-column gap-1">
                @foreach($run->steps->whereNotNull('completed_at') as $step)
                    <div class="d-flex align-items-center gap-2 small py-1 border-bottom"
                         style="border-color: var(--glass-border) !important;">
                        <i class="bi bi-check-circle-fill text-success flex-shrink-0"></i>
                        <span class="fw-semibold">{{ $step->node_name }}</span>
                        <span class="badge bg-secondary bg-opacity-25 text-muted" style="font-size:.65rem;">{{ $step->node_type }}</span>
                        <span class="ms-auto text-muted">
                            {{ $step->performed_by ? \App\Models\User::find($step->performed_by)?->name : '—' }}
                            · {{ $step->completed_at->format('d.m H:i') }}
                        </span>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- ─── COMMENTS ─── --}}
        <hr class="my-3">
        <div class="mb-2">
            <span class="small fw-semibold text-muted text-uppercase" style="letter-spacing:.05em;">
                <i class="bi bi-chat me-1"></i>Komentarze
            </span>
        </div>

        @foreach($run->comments as $comment)
            <div class="d-flex gap-2 mb-2">
                <div class="flex-shrink-0">
                    <div class="rounded-circle bg-secondary bg-opacity-25 d-flex align-items-center justify-content-center" style="width:32px;height:32px;font-size:.75rem;font-weight:600;">
                        {{ strtoupper(substr($comment->user?->name ?? '?', 0, 1)) }}
                    </div>
                </div>
                <div class="flex-grow-1">
                    <div class="small fw-semibold">{{ $comment->user?->name }}</div>
                    <div class="small text-muted mb-1">{{ $comment->created_at->format('d.m.Y H:i') }}</div>
                    <p class="small mb-0">{{ $comment->body }}</p>
                </div>
            </div>
        @endforeach

        <div class="mt-2">
            <textarea class="form-control form-control-sm" rows="2"
                      wire:model.defer="newComment"
                      placeholder="Dodaj komentarz…"></textarea>
            @error('newComment') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            <button class="btn btn-sm btn-outline-secondary mt-1" wire:click="addComment">
                <i class="bi bi-chat-dots me-1"></i>Dodaj komentarz
            </button>
        </div>
    </x-ui.card>
</div>
