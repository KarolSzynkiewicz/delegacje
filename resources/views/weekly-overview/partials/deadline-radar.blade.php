@php
    /** @var array $deadlineRadar */
    $lanes = $deadlineRadar['lanes'] ?? [];
    $clusters = $deadlineRadar['clusters'] ?? [];
    $items = $deadlineRadar['items'] ?? [];
    $kpis = $deadlineRadar['kpis'] ?? ['within_7' => 0, 'overdue' => 0, 'monitored' => 0];
    $plotStart = (int) ($deadlineRadar['plot_start_offset'] ?? -7);
    $plotEnd = (int) ($deadlineRadar['plot_end_offset'] ?? 30);
    $plotSpan = max(1, $plotEnd - $plotStart);
    $forwardDays = (int) ($deadlineRadar['forward_days'] ?? 30);
    $anchorLabel = $deadlineRadar['anchor_label'] ?? 'pon';
    $clustersByLane = collect($clusters)->groupBy('lane');
    $markers = [0, 7, 14, 21, 30];
    // Strefa bólu: zaległe (lewa krawędź okna) → +14 dni od poniedziałku tygodnia.
    $criticalStart = $plotStart;
    $criticalEnd = 14;
    $criticalLeft = (($criticalStart - $plotStart) / $plotSpan) * 100;
    $criticalWidth = (($criticalEnd - $criticalStart) / $plotSpan) * 100;
    $todayLeft = ((0 - $plotStart) / $plotSpan) * 100;
    $mark7Left = ((7 - $plotStart) / $plotSpan) * 100;
@endphp

<div
    class="wo-radar"
    :class="{ 'wo-radar--boot': booting }"
    x-data="deadlineRadar(@js([
        'lanes' => collect($lanes)->pluck('key')->all(),
        'clusters' => $clusters,
        'items' => $items,
    ]))"
>
    <div class="wo-radar__head">
        <div>
            <div class="wo-radar__title-row mb-1">
                <span class="wo-radar__argus" title="Argus skanuje terminy" aria-hidden="true">
                    <x-ask-chrono-bot
                        variant="lens"
                        :size="44"
                        x-bind:class="booting ? 'ac-bot--thinking' : ''"
                    />
                </span>
                <div>
                    <h3 class="wo-radar__title mb-0">Radar terminów</h3>
                    <p class="wo-radar__argus-caption mb-0" x-text="booting ? 'Argus skanuje…' : 'Argus'"></p>
                </div>
            </div>
            <p class="wo-radar__sub mb-0 mt-1">
                Następne {{ $forwardDays }} dni · {{ $kpis['monitored'] }}
                {{ $kpis['monitored'] === 1 ? 'termin' : ($kpis['monitored'] >= 2 && $kpis['monitored'] <= 4 ? 'terminy' : 'terminów') }}
            </p>
        </div>
        <div class="wo-radar__kpis">
            <span class="wo-radar__kpi wo-radar__kpi--warn" title="Terminy od poniedziałku tygodnia do +7 dni">
                Wygasa w 7 dni: <strong>{{ $kpis['within_7'] }}</strong>
            </span>
            <span class="wo-radar__kpi wo-radar__kpi--danger" title="Terminy przed poniedziałkiem przeglądanego tygodnia">
                Po terminie: <strong>{{ $kpis['overdue'] }}</strong>
            </span>
            <span class="wo-radar__kpi" title="Wszystkie terminy w oknie radaru">
                Monitorowane: <strong>{{ $kpis['monitored'] }}</strong>
            </span>
        </div>
    </div>

    <div class="wo-radar__chart d-none d-xl-grid">
        <div class="wo-radar__tracks" style="--lane-count: {{ max(1, count($lanes)) }};">
            <div class="wo-radar__guides" aria-hidden="true">
                <div class="wo-radar__critical" style="left: {{ $criticalLeft }}%; width: {{ $criticalWidth }}%;"></div>
                <div class="wo-radar__today" style="left: {{ $todayLeft }}%;"></div>
                <div class="wo-radar__mark-line" style="left: {{ $mark7Left }}%;"></div>
            </div>
            <div class="wo-radar__scan" aria-hidden="true"></div>

            @foreach($lanes as $lane)
                @php
                    $laneClusters = $clustersByLane->get($lane['key'], collect());
                @endphp
                <div class="wo-radar__row">
                    <button
                        type="button"
                        class="wo-radar__legend-btn"
                        :class="{ 'is-off': !laneOn['{{ $lane['key'] }}'] }"
                        @click="toggleLane('{{ $lane['key'] }}')"
                    >
                        <span class="wo-radar__swatch" style="--lane: {{ $lane['color'] }}"></span>
                        <span class="wo-radar__legend-label">{{ $lane['label'] }}</span>
                        <span class="wo-radar__legend-count font-mono">{{ $lane['count'] }}</span>
                    </button>

                    <div class="wo-radar__track" x-show="laneOn['{{ $lane['key'] }}']">
                        @foreach($laneClusters as $cluster)
                            @php
                                $left = (($cluster['plot_offset'] - $plotStart) / $plotSpan) * 100;
                                $scanDelay = round(max(0, min(1, $left / 100)) * 1.15, 3);
                            @endphp
                            <button
                                type="button"
                                class="wo-radar__dot wo-radar__dot--{{ $cluster['urgency'] }}"
                                style="left: {{ $left }}%; --lane: {{ $lane['color'] }}; --scan-delay: {{ $scanDelay }}s;"
                                @click="openCluster('{{ $cluster['key'] }}')"
                                title="{{ $cluster['overdue'] ? 'Zaległe: '.$cluster['count'] : ($cluster['count'].' · '.$cluster['due_label']) }}"
                            >
                                @if($cluster['show_count'])
                                    <span>{{ $cluster['count_label'] }}</span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div class="wo-radar__axis-row">
                <div class="wo-radar__axis-spacer" aria-hidden="true"></div>
                <div class="wo-radar__axis">
                    @foreach($markers as $day)
                        @php
                            $label = $day === 0 ? $anchorLabel : '+'.$day.($day === 7 ? ' dni' : '');
                            $left = (($day - $plotStart) / $plotSpan) * 100;
                        @endphp
                        <span style="left: {{ $left }}%;">{{ $label }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="wo-radar__mobile d-xl-none">
        @forelse($items as $item)
            <a
                href="{{ $item['url'] }}"
                class="wo-radar__mobile-row"
                x-show="laneOn['{{ $item['lane'] }}']"
            >
                <span class="wo-radar__swatch" style="--lane: {{ collect($lanes)->firstWhere('key', $item['lane'])['color'] ?? '#94a3b8' }}"></span>
                <span class="wo-radar__mobile-main">
                    <span class="fw-semibold">{{ $item['title'] }}</span>
                    @if($item['badge'] || $item['subtitle'])
                        <span class="text-muted small">
                            @if($item['badge']){{ $item['badge'] }} · @endif{{ $item['subtitle'] }}
                        </span>
                    @endif
                </span>
                <span class="wo-radar__mobile-due font-mono @if($item['overdue']) text-danger @endif">
                    {{ $item['due_label'] }}
                </span>
            </a>
        @empty
            <p class="text-muted small mb-0">Brak terminów w najbliższe {{ $forwardDays }} dni.</p>
        @endforelse
    </div>

    <template x-if="activeCluster">
        <div class="wo-radar__overlay" @click.self="closeCluster()">
            <div class="wo-radar__panel rounded-3 border p-3" @click.stop>
                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                    <div>
                        <div class="fw-semibold" x-text="activeClusterTitle()"></div>
                        <div class="small text-muted" x-text="activeClusterMeta()"></div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" @click="closeCluster()" aria-label="Zamknij"></button>
                </div>
                <div class="wo-radar__panel-list small">
                    <template x-for="item in activeItems()" :key="item.id">
                        <a :href="item.url" class="wo-radar__panel-line text-decoration-none">
                            <span class="fw-semibold" x-text="item.title"></span>
                            <span class="text-muted" x-show="item.badge" x-text="' · ' + item.badge"></span>
                            <div class="text-muted" x-show="item.subtitle" x-text="item.subtitle"></div>
                            <div class="mt-1">
                                <i class="bi bi-calendar-event"></i>
                                <span x-text="item.due_label"></span>
                                <span class="text-danger" x-show="item.overdue"> · po terminie</span>
                            </div>
                        </a>
                    </template>
                </div>
            </div>
        </div>
    </template>
</div>

@once
@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    if (window.__deadlineRadarRegistered) return;
    window.__deadlineRadarRegistered = true;
    Alpine.data('deadlineRadar', (payload) => ({
        laneOn: Object.fromEntries((payload.lanes || []).map((k) => [k, true])),
        clusters: payload.clusters || [],
        items: payload.items || [],
        activeKey: null,
        booting: false,
        init() {
            const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            if (reduce || typeof IntersectionObserver === 'undefined') {
                return;
            }
            const el = this.$el;
            const io = new IntersectionObserver((entries) => {
                if (!entries.some((entry) => entry.isIntersecting)) {
                    return;
                }
                io.disconnect();
                this.booting = true;
                window.setTimeout(() => { this.booting = false; }, 1600);
            }, { threshold: 0.2, rootMargin: '0px 0px -8% 0px' });
            io.observe(el);
        },
        get activeCluster() {
            if (!this.activeKey) return null;
            return this.clusters.find((c) => c.key === this.activeKey) || null;
        },
        toggleLane(key) {
            this.laneOn[key] = !this.laneOn[key];
        },
        openCluster(key) {
            this.activeKey = key;
        },
        closeCluster() {
            this.activeKey = null;
        },
        activeItems() {
            const cluster = this.activeCluster;
            if (!cluster) return [];
            const ids = new Set(cluster.item_ids || []);
            return this.items.filter((item) => ids.has(item.id));
        },
        activeClusterTitle() {
            const cluster = this.activeCluster;
            if (!cluster) return '';
            const labels = {
                documents: 'Dokumenty',
                leases: 'Najmy',
                projects: 'Projekty',
                rotations: 'Rotacje',
                vehicles: 'Auta',
            };
            const lane = labels[cluster.lane] || cluster.lane;
            return cluster.overdue ? (lane + ' · zaległe') : (lane + ' · ' + cluster.due_label);
        },
        activeClusterMeta() {
            const cluster = this.activeCluster;
            if (!cluster) return '';
            const n = cluster.count || 0;
            if (cluster.overdue) return n + ' po terminie';
            if (cluster.days === 0) return n + ' w poniedziałek';
            if (cluster.days === 1) return n + ' za 1 dzień';
            return n + ' za ' + cluster.days + ' dni';
        },
    }));
});
</script>
@endpush
@endonce
