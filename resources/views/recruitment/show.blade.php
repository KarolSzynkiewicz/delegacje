<x-app-layout edge-to-edge>
    <livewire:recruitment-processes-table :process-id="$application->id" :key="'rp-show-'.$application->id" />
</x-app-layout>
