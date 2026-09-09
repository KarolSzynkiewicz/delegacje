{{-- Zawsze na dole karty: historia procesu i komentarze procesu --}}
<div class="rp-doc-section rp-doc-section--history">
        <div>
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="rp-field-label mb-0">Historia procesu</div>
                <span style="font-size:.76rem;color:var(--text-muted);">proces #{{ $selected->id }}</span>
            </div>
            <div class="rp-timeline" style="max-height:170px;overflow-y:auto;">
                @forelse($processTimeline as $timelineItem)
                    @php
                        $entry = $timelineItem['entry'];
                        $isStatus = $timelineItem['type'] === 'status';
                    @endphp
                    <div class="rp-timeline-item" wire:key="timeline-{{ $timelineItem['type'] }}-{{ $entry->id }}" style="font-size:.85rem;">
                        <span class="rp-status-dot" style="color:{{ $isStatus ? 'var(--primary)' : '#a78bfa' }};box-shadow:0 0 0 3px {{ $isStatus ? 'rgba(59,130,246,.15)' : 'rgba(167,139,250,.15)' }};"></span>
                        <div class="flex-grow-1 d-flex justify-content-between align-items-start gap-2 min-width-0">
                            <div class="min-width-0">
                                @if($isStatus)
                                    @if($entry->from_status)
                                        <span style="color:var(--text-muted);">{{ $entry->from_status->label() }}</span>
                                        <i class="bi bi-arrow-right mx-1" style="color:var(--text-muted);font-size:.72rem;"></i>
                                    @else
                                        <span style="color:var(--text-muted);">Utworzono</span>
                                        <i class="bi bi-arrow-right mx-1" style="color:var(--text-muted);font-size:.72rem;"></i>
                                    @endif
                                    <strong>{{ $entry->to_status->label() }}</strong>
                                @else
                                    <i class="bi bi-person-badge me-1" style="color:#a78bfa;font-size:.78rem;"></i>
                                    <span style="color:var(--text-muted);">{{ $entry->fromRecruiter?->name ?? 'Nieprzypisany' }}</span>
                                    <i class="bi bi-arrow-right mx-1" style="color:var(--text-muted);font-size:.72rem;"></i>
                                    <strong>{{ $entry->toRecruiter?->name ?? 'Nieprzypisany' }}</strong>
                                @endif
                                <div style="color:var(--text-muted);font-size:.72rem;">{{ $entry->changedBy?->name ?? 'System' }}</div>
                            </div>
                            <small style="color:var(--text-muted);white-space:nowrap;text-align:right;line-height:1.25;flex-shrink:0;"
                                   title="{{ $entry->created_at->format('d.m.Y H:i') }}">
                                <div>{{ $entry->created_at->diffForHumans() }}</div>
                                <div style="font-size:.65rem;opacity:.8;">{{ $entry->created_at->format('d.m.Y H:i') }}</div>
                            </small>
                        </div>
                    </div>
                @empty
                    <p style="color:var(--text-muted);font-size:.82rem;margin:0;">Brak historii — wpisy pojawią się po zmianie statusu lub przypisania.</p>
                @endforelse
            </div>
        </div>

        <div class="mt-4">
            <x-comments
                :commentable="$selected"
                label="Komentarze procesu"
            />
        </div>
</div>
