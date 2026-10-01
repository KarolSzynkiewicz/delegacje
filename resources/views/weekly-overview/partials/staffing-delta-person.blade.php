@php
    /** @var array<string, mixed> $row */
    $employee = $row['employee'];
    $tone = $row['tone'] ?? 'arrived';
@endphp
<article class="wo-delta-person">
    <div class="wo-emp">
        <x-employee-cell :employee="$employee" :link="true" />
        <div class="wo-emp-meta">
            <x-ui.rating
                :score="$row['latest_evaluation_score'] ?? null"
                :evaluation="$row['latest_evaluation'] ?? null"
                :show-empty="true"
            />
            <span class="wo-emp-meta__rule" aria-hidden="true"></span>
            <x-planner-document-icons
                :documents="$row['planner_documents'] ?? []"
                :show-empty="true"
                stacked
            />
        </div>
    </div>

    <div class="wo-delta-person__side">
        @if($row['role'] ?? null)
            <x-role-seniority-badge
                :role="$row['role']"
                :seniority="$row['seniority'] ?? null"
                :href="$row['timeline_url'] ?? null"
            />
        @endif
        @if(!empty($row['badge']))
            <span class="wo-delta-pill wo-delta-pill--{{ $tone }}">{{ $row['badge'] }}</span>
        @endif
        @if(!empty($row['detail']))
            <span class="wo-delta-person__detail text-muted">{{ $row['detail'] }}</span>
        @endif
    </div>
</article>
