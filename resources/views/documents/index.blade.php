@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <h1>Wymagania formalne</h1>
                <a href="{{ route('documents.create') }}" class="btn btn-primary">Dodaj Dokument</a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Nazwa</th>
                                <th>Opis</th>
                                <th>Okresowy</th>
                                <th>Wymagane</th>
                                <th>Liczba przypisań</th>
                                <th>Akcje</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($documents as $document)
                                <tr>
                                    <td>{{ $document->name }}</td>
                                    <td>{{ $document->description ?? '-' }}</td>
                                    <td>{{ $document->is_periodic ? 'Tak' : 'Nie' }}</td>
                                    <td>
                                        @if($document->is_required)
                                            <span class="badge bg-danger">Tak</span>
                                        @else
                                            <span class="badge bg-secondary">Nie</span>
                                        @endif
                                    </td>
                                    <td>{{ $document->employee_documents_count }}</td>
                                    <td>
                                        <a href="{{ route('documents.show', $document) }}" class="btn btn-sm btn-info">Szczegóły</a>
                                        <a href="{{ route('documents.edit', $document) }}" class="btn btn-sm btn-warning">Edytuj</a>
                                        <form action="{{ route('documents.destroy', $document) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Czy na pewno chcesz usunąć ten dokument?')">Usuń</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">Brak dokumentów</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    @if($documents->hasPages())
                        <div class="mt-4">
                            {{ $documents->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
