<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header :title="$approval->name">
            <x-slot name="left">
                <x-ui.button variant="ghost" href="{{ route('tasks.home') }}" action="back">
                    Powrót
                </x-ui.button>
            </x-slot>
            <x-slot name="right">
                <x-ui.badge variant="accent">Zatwierdzenie</x-ui.badge>
                <x-ui.approval-decision :decision="$approval->decision" size="lg" with-label />
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    @if(session('success'))
        <x-ui.alert variant="success" dismissible class="mb-4">
            {{ session('success') }}
        </x-ui.alert>
    @endif
    @if(session('error'))
        <x-ui.alert variant="danger" dismissible class="mb-4">
            {{ session('error') }}
        </x-ui.alert>
    @endif

    <div class="row g-4 justify-content-center">
        <div class="col-lg-7">
            @include('approval-requests.partials.body', [
                'approval' => $approval,
                'embedded' => false,
                'showComments' => true,
            ])
        </div>
    </div>
</x-app-layout>
