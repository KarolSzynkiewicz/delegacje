{{-- Inne procesy tego kandydata (bez bieżącego) --}}
@php
    $siblingProcesses = $candidate
        ? $candidate->processes
            ->where('id', '!=', $selected->id)
            ->filter(fn ($proc) => $proc->lead?->referral_source !== \App\Enums\RecruitmentReferralSource::EmployeeLifecycle)
            ->sortByDesc('created_at')
        : collect();
@endphp
@if($siblingProcesses->isNotEmpty())
    <div class="rp-doc-section rp-doc-section--siblings">
        <div class="rp-kicker">
            <i class="bi bi-diagram-2 me-1"></i>Inne procesy tego kandydata
            <span class="rp-plain-tag" style="cursor:default;">{{ $siblingProcesses->count() }}</span>
        </div>
        <div class="d-flex flex-column gap-2">
        @foreach($siblingProcesses as $proc)
            @php $procStatus = $proc->status; @endphp
            <div class="d-flex align-items-center gap-2 p-3"
                 style="border-radius:10px;background:rgba(255,255,255,.025);"
                 wire:key="sibling-{{ $proc->id }}">
                <div class="flex-grow-1 min-width-0">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        @if($procStatus)
                            <span class="badge badge-{{ $procStatus->variant() }}" style="font-size:.68rem;">{{ $procStatus->label() }}</span>
                        @endif
                        <span style="font-size:.8rem;color:var(--text-muted);"
                              title="{{ ($proc->lead?->created_at ?? $proc->created_at)->format('d.m.Y H:i') }}">
                            {{ ($proc->lead?->created_at ?? $proc->created_at)->diffForHumans() }}
                            · {{ ($proc->lead?->created_at ?? $proc->created_at)->format('d.m.Y') }}
                        </span>
                        @if($proc->assignedRecruiter)
                            <span style="font-size:.78rem;color:var(--text-muted);">· {{ $proc->assignedRecruiter->name }}</span>
                        @endif
                    </div>
                </div>
                <div class="d-flex gap-1 flex-shrink-0">
                    <button type="button"
                            wire:click="selectProcess({{ $proc->id }})"
                            class="btn btn-sm btn-outline-secondary"
                            title="Przejdź do tego procesu">
                        <i class="bi bi-arrow-right"></i>
                    </button>
                    @if($procStatus !== \App\Enums\RecruitmentStatus::Odrzucony && $procStatus !== \App\Enums\RecruitmentStatus::Zatrudniony)
                        <button type="button"
                                wire:click="updateStatus({{ $proc->id }}, 'odrzucony')"
                                class="btn btn-sm btn-outline-danger"
                                title="Odrzuć ten proces">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    @endif
                </div>
            </div>
        @endforeach
        </div>
    </div>
@endif
