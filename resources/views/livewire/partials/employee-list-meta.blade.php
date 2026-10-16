@php
    $docs = ($plannerDocuments ?? collect())->get($employee->id) ?? [];
@endphp
<div class="wo-emp-meta mt-1">
    <x-ui.rating
        :score="$employee->latestEvaluation?->average_score"
        :evaluation="$employee->latestEvaluation"
        :show-empty="true"
    />
    <span class="wo-emp-meta__rule" aria-hidden="true"></span>
    <x-planner-document-icons
        :documents="$docs"
        :show-empty="true"
        stacked
    />
</div>
