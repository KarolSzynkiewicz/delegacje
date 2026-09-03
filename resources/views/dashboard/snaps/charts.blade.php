<x-dashboard.snap
    kicker="Analityka · Prompt Engine"
    title="Wykresy zadań i kosztów"
    caption="Te same wykresy co w eksporcie JSON na /prompts: rozkład statusów backlogu i struktura kosztów per waluta. Dane poniżej są przykładowe — w systemie liczą się z prawdziwego okresu."
    :href="Route::has('prompts.index') ? route('prompts.index') : null"
    href-label="Prompt Engine"
    tall
>
    <div class="row g-3">
        <div class="col-lg-6">
            <x-ui.card label="Zadania">
                <div id="dash-task-charts" class="dash-charts" data-dash-charts="tasks"></div>
            </x-ui.card>
        </div>
        <div class="col-lg-6">
            <x-ui.card label="Finanse / koszty">
                <div id="dash-cost-charts" class="dash-charts" data-dash-charts="costs"></div>
            </x-ui.card>
        </div>
    </div>
    <script type="application/json" id="dash-tasks-json">{!! json_encode($snaps['taskChartJson'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    <script type="application/json" id="dash-costs-json">{!! json_encode($snaps['costChartJson'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
</x-dashboard.snap>
