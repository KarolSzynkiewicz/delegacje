<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Projekt: {{ $project->name }}">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('projects.index') }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
            <x-slot name="right">
                <div class="d-flex gap-2">
                    <x-ui.button 
                        variant="ghost" 
                        href="{{ route('projects.edit', $project) }}"
                        routeName="projects.edit"
                        action="edit"
                    >
                        Edytuj
                    </x-ui.button>
                    <form action="{{ route('projects.destroy', $project) }}" 
                          method="POST" 
                          class="d-inline"
                          onsubmit="return confirm('⚠️ UWAGA: Usunięcie projektu spowoduje kaskadowe usunięcie wszystkich powiązanych danych:\n• Wszystkie przypisania pracowników\n• Wszystkie wpisy czasu pracy\n• Wszystkie zapotrzebowania\n• Wszystkie zadania\n• Wszystkie pliki\n• Wszystkie komentarze\n• Wszystkie koszty zmienne\n\nCzy na pewno chcesz usunąć ten projekt?')">
                        @csrf
                        @method('DELETE')
                        <x-ui.button variant="danger" type="submit" title="Usuń projekt">
                            <i class="bi bi-trash"></i> Usuń
                        </x-ui.button>
                    </form>
                </div>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="container-xxl">
        <div class="row">
            <div class="col-md-12">
                <livewire:project-tabs :project="$project" />
            </div>
        </div>
    </div>
</x-app-layout>
