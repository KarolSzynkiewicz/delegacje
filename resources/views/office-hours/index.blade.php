<x-app-layout :edge-to-edge="true">
    <livewire:office-hours-grid
        :month="$month"
        :week="$week ?? null"
        :view-mode="$viewMode ?? 'week'"
        :only-mine="$onlyMine"
    />
</x-app-layout>
