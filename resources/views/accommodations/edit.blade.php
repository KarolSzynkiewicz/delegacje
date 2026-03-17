<x-app-layout>
    <x-slot name="header">
        <x-ui.page-header title="Edytuj Akomodację: {{ $accommodation->name }}">
            <x-slot name="left">
                <x-ui.button 
                    variant="ghost" 
                    href="{{ route('accommodations.show', $accommodation) }}"
                    action="back"
                >
                    Powrót
                </x-ui.button>
            </x-slot>
        </x-ui.page-header>
    </x-slot>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <x-ui.card label="Edytuj Akomodację">
                <x-ui.errors />

                <form action="{{ route('accommodations.update', $accommodation) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    @livewire('accommodation-form', ['accommodation' => $accommodation])

                    <x-ui.image-preview 
                        :showCurrentImage="$accommodation->image_path ? true : false"
                        :currentImageUrl="$accommodation->image_path ? $accommodation->image_url : null"
                        :currentImage="$accommodation->name"
                    />

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <x-ui.button 
                            variant="primary" 
                            type="submit"
                            action="save"
                        >
                            Zaktualizuj Akomodację
                        </x-ui.button>
                        <x-ui.button 
                            variant="ghost" 
                            href="{{ route('accommodations.show', $accommodation) }}"
                            action="cancel"
                        >
                            Anuluj
                        </x-ui.button>
                    </div>
                </form>
            </x-ui.card>
        </div>
    </div>
</x-app-layout>
