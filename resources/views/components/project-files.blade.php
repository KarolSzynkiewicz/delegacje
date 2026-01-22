@props(['project'])

<x-ui.card label="Pliki projektu">
    <form action="{{ route('projects.files.store', $project) }}" method="POST" enctype="multipart/form-data" class="mb-4">
        @csrf
        <div class="row g-3">
            <div class="col-md-8">
                <x-ui.input 
                    type="file" 
                    name="file" 
                    label="Wybierz plik"
                    required
                />
            </div>
            <div class="col-md-4">
                <x-ui.input 
                    type="text" 
                    name="name" 
                    label="Nazwa (opcjonalnie)"
                    placeholder="Zostaw puste, aby użyć nazwy pliku"
                />
            </div>
        </div>
        <div class="mt-3">
            <x-ui.button variant="primary" type="submit" action="save">
                Dodaj plik
            </x-ui.button>
        </div>
    </form>

    @if($project->files->count() > 0)
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nazwa</th>
                        <th>Rozmiar</th>
                        <th>Typ</th>
                        <th>Dodany przez</th>
                        <th>Data</th>
                        <th class="text-end">Akcje</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($project->files as $file)
                        <tr>
                            <td>{{ $file->name }}</td>
                            <td>{{ number_format($file->file_size / 1024, 2) }} KB</td>
                            <td>{{ $file->mime_type }}</td>
                            <td>{{ $file->uploadedBy->name }}</td>
                            <td>{{ $file->created_at->format('d.m.Y H:i') }}</td>
                            <td class="text-end">
                                <x-ui.action-buttons>
                                    <x-ui.button 
                                        variant="primary" 
                                        href="{{ route('projects.files.download', [$project, $file]) }}"
                                        class="btn-sm"
                                    >
                                        Pobierz
                                    </x-ui.button>
                                    <x-ui.delete-form 
                                        :url="route('projects.files.destroy', [$project, $file])"
                                        message="Czy na pewno chcesz usunąć ten plik?"
                                    />
                                </x-ui.action-buttons>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <x-ui.empty-state 
            icon="file-earmark"
            message="Brak plików"
        />
    @endif
</x-ui.card>
