<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="$template->name">
            <x-slot name="left">
                <x-ui.button variant="ghost" href="{{ route('procedure-templates.index') }}" action="back">
                    Procedury
                </x-ui.button>
            </x-slot>
            <x-slot name="right">
                <x-ui.button variant="primary" href="{{ route('procedure-templates.editor', $template) }}" class="btn-sm">
                    <i class="bi bi-pencil me-1"></i> Edytuj
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    @if(session('success'))
        <x-ui.alert variant="success" dismissible class="mb-3">{{ session('success') }}</x-ui.alert>
    @endif
    @if(session('error'))
        <x-ui.alert variant="danger" dismissible class="mb-3">{{ session('error') }}</x-ui.alert>
    @endif

    <div class="row g-4">
        <div class="col-lg-8">
            <x-ui.card class="mb-4">
                <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                    @if($template->category)
                        <x-ui.badge variant="secondary">{{ $template->category }}</x-ui.badge>
                    @endif
                    @if($template->subjectType())
                        <x-ui.badge variant="info">{{ $template->subjectType()->label() }}</x-ui.badge>
                    @endif
                </div>

                @if($template->description)
                    <p class="text-muted mb-0">{{ $template->description }}</p>
                @else
                    <p class="text-muted fst-italic mb-0">Brak opisu.</p>
                @endif
            </x-ui.card>

            <x-ui.card>
                <div class="d-flex align-items-center justify-content-between gap-2 mb-3 flex-wrap">
                    <h3 class="fs-6 fw-semibold text-uppercase text-muted mb-0" style="letter-spacing:.05em;">
                        <i class="bi bi-diagram-3 me-1"></i> Przepływ ({{ $template->nodeCount() }})
                    </h3>
                    @if($template->latestVersion())
                        <span class="small text-muted font-mono">{{ $template->latestVersion()->label() }}</span>
                    @endif
                </div>

                @php
                    $latestDefinition = $template->latestVersion()?->definition ?? $template->definition;
                    $hasNodes = count($latestDefinition['nodes'] ?? []) > 0;
                @endphp

                @if(! $hasNodes)
                    <p class="text-muted small mb-0">
                        Ten szablon nie ma jeszcze zdefiniowanych kroków.
                        <a href="{{ route('procedure-templates.editor', $template) }}">Otwórz edytor</a>, aby je dodać.
                    </p>
                @else
                    <x-procedure-flow :definition="$latestDefinition" legend="none" class="pr-flow--lg" />
                @endif
            </x-ui.card>
        </div>

        <div class="col-lg-4">
            <x-ui.card class="mb-4">
                <h3 class="fs-6 fw-semibold text-uppercase text-muted mb-3" style="letter-spacing:.05em;">
                    <i class="bi bi-bar-chart me-1"></i> Statystyki
                </h3>
                <dl class="row mb-0">
                    <dt class="col-8 text-muted fw-normal">Wszystkie przebiegi</dt>
                    <dd class="col-4 text-end fw-semibold font-mono">{{ $template->runs_count }}</dd>

                    <dt class="col-8 text-muted fw-normal">W trakcie</dt>
                    <dd class="col-4 text-end fw-semibold font-mono">{{ $runsByStatus['in_progress'] ?? 0 }}</dd>

                    <dt class="col-8 text-muted fw-normal">Zakończone</dt>
                    <dd class="col-4 text-end fw-semibold font-mono">{{ $runsByStatus['finished'] ?? 0 }}</dd>

                    <dt class="col-8 text-muted fw-normal">Porzucone</dt>
                    <dd class="col-4 text-end fw-semibold font-mono">{{ $runsByStatus['abandoned'] ?? 0 }}</dd>

                    <dt class="col-8 text-muted fw-normal border-top pt-2 mt-1" style="border-color: var(--glass-border) !important;">Kroków w szablonie</dt>
                    <dd class="col-4 text-end fw-semibold font-mono border-top pt-2 mt-1" style="border-color: var(--glass-border) !important;">{{ $template->nodeCount() }}</dd>
                </dl>
            </x-ui.card>

            <x-ui.card class="mb-4">
                <h3 class="fs-6 fw-semibold text-uppercase text-muted mb-3" style="letter-spacing:.05em;">
                    <i class="bi bi-layers me-1"></i> Wersje
                </h3>

                @if($versionStats === [])
                    <p class="text-muted small mb-0">Brak opublikowanych wersji.</p>
                @else
                    <div class="d-flex flex-column gap-2">
                        @foreach($versionStats as $stat)
                            @php $version = $stat['version']; @endphp
                            <div class="d-flex align-items-center justify-content-between gap-2 p-2 rounded-3"
                                 style="background: rgba(255,255,255,.03); border: 1px solid var(--glass-border);">
                                <div class="min-w-0">
                                    <div class="fw-semibold">{{ $version->label() }}</div>
                                    <div class="small text-muted">
                                        {{ $version->changedBy?->name ?? '—' }}
                                        · {{ $version->changed_at?->format('d.m.Y H:i') }}
                                        · {{ $version->nodeCount() }} kroków
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                    <x-ui.badge variant="{{ $stat['runs_count'] > 0 ? 'primary' : 'secondary' }}">
                                        {{ $stat['runs_count'] }} uruchomień
                                    </x-ui.badge>
                                    @if($stat['runs_count'] === 0)
                                        <form action="{{ route('procedure-templates.versions.destroy', [$template, $version]) }}"
                                              method="POST"
                                              onsubmit="return confirm('Usunąć {{ $version->label() }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Usuń wersję">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-ui.card>

            <x-ui.card>
                <h3 class="fs-6 fw-semibold text-uppercase text-muted mb-3" style="letter-spacing:.05em;">
                    <i class="bi bi-info-circle me-1"></i> Informacje
                </h3>
                <dl class="row mb-0 small">
                    <dt class="col-6 text-muted fw-normal">Utworzył</dt>
                    <dd class="col-6 text-end">{{ $template->createdBy?->name ?? '—' }}</dd>

                    <dt class="col-6 text-muted fw-normal">Utworzono</dt>
                    <dd class="col-6 text-end font-mono">{{ $template->created_at?->format('d.m.Y') }}</dd>

                    <dt class="col-6 text-muted fw-normal">Zaktualizowano</dt>
                    <dd class="col-6 text-end font-mono">{{ $template->updated_at?->format('d.m.Y H:i') }}</dd>
                </dl>
            </x-ui.card>
        </div>
    </div>

    <x-ui.card class="mt-4">
        <h3 class="fs-6 fw-semibold text-uppercase text-muted mb-3" style="letter-spacing:.05em;">
            <i class="bi bi-play-circle me-1"></i> Przebiegi ({{ $runs->total() }})
        </h3>

        @if($runs->isEmpty())
            <x-ui.empty-state icon="play-circle" message="Ta procedura nie była jeszcze uruchamiana." />
        @else
            {{-- Desktop table --}}
            <div class="table-responsive d-none d-md-block">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr class="small text-muted text-uppercase">
                            <th>Wersja</th>
                            <th>Status</th>
                            <th>Dotyczy</th>
                            <th>Rozpoczął</th>
                            <th>Data rozpoczęcia</th>
                            <th style="min-width:140px;">Postęp</th>
                            <th class="text-end"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($runs as $run)
                            @php $sourceCard = $run->sourceCard(); @endphp
                            <tr>
                                <td class="font-mono small">{{ $run->version?->label() ?? '—' }}</td>
                                <td><x-ui.badge variant="{{ $run->status->badgeVariant() }}">{{ $run->status->label() }}</x-ui.badge></td>
                                <td>
                                    @if($sourceCard)
                                        <a href="{{ $sourceCard['url'] }}"><i class="bi {{ $sourceCard['icon'] }} me-1"></i>{{ $sourceCard['label'] }}</a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>{{ $run->startedBy?->name ?? '—' }}</td>
                                <td class="font-mono small">{{ $run->started_at?->format('d.m.Y H:i') ?? '—' }}</td>
                                <td>
                                    <x-ui.progress :value="round($run->progress() * 100)" :max="100"
                                        variant="{{ $run->status === \App\Enums\ProcedureRunStatus::FINISHED ? 'success' : ($run->status === \App\Enums\ProcedureRunStatus::ABANDONED ? 'danger' : 'default') }}" />
                                </td>
                                <td class="text-end">
                                    @if($run->task)
                                        <a href="{{ route('tasks.show', $run->task) }}" class="btn btn-sm btn-outline-secondary" title="Otwórz zadanie">
                                            <i class="bi bi-box-arrow-up-right"></i>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Mobile cards --}}
            <div class="d-md-none d-flex flex-column gap-2">
                @foreach($runs as $run)
                    @php $sourceCard = $run->sourceCard(); @endphp
                    <div class="p-3 rounded-3" style="background: rgba(255,255,255,.03); border: 1px solid var(--glass-border);">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <x-ui.badge variant="{{ $run->status->badgeVariant() }}">{{ $run->status->label() }}</x-ui.badge>
                                @if($run->version)
                                    <span class="small text-muted font-mono">{{ $run->version->label() }}</span>
                                @endif
                            </div>
                            @if($run->task)
                                <a href="{{ route('tasks.show', $run->task) }}" class="btn btn-sm btn-outline-secondary" title="Otwórz zadanie">
                                    <i class="bi bi-box-arrow-up-right"></i>
                                </a>
                            @endif
                        </div>
                        @if($sourceCard)
                            <div class="small mb-1">
                                <a href="{{ $sourceCard['url'] }}"><i class="bi {{ $sourceCard['icon'] }} me-1"></i>{{ $sourceCard['label'] }}</a>
                            </div>
                        @endif
                        <div class="small text-muted mb-2">
                            {{ $run->startedBy?->name ?? '—' }} ·
                            <span class="font-mono">{{ $run->started_at?->format('d.m.Y H:i') ?? '—' }}</span>
                        </div>
                        <x-ui.progress :value="round($run->progress() * 100)" :max="100"
                            variant="{{ $run->status === \App\Enums\ProcedureRunStatus::FINISHED ? 'success' : ($run->status === \App\Enums\ProcedureRunStatus::ABANDONED ? 'danger' : 'default') }}" />
                    </div>
                @endforeach
            </div>

            @if($runs->hasPages())
                <div class="mt-3 pt-3 border-top" style="border-color: var(--glass-border) !important;">
                    <x-ui.pagination :paginator="$runs" />
                </div>
            @endif
        @endif
    </x-ui.card>
</x-app-layout>
