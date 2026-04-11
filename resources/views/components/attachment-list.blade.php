@props([
    'attachments',
])

@if($attachments->count() > 0)
    <ul {{ $attributes->merge(['class' => 'list-unstyled small mb-0']) }}>
        @foreach($attachments as $attachment)
            <li class="d-flex align-items-center flex-wrap gap-2 mb-1">
                <a href="{{ route('attachments.download', $attachment) }}" target="_blank" rel="noopener" class="text-decoration-none">
                    <i class="bi bi-paperclip me-1"></i>{{ $attachment->original_name ?: basename($attachment->file_path) }}
                </a>
                @can('delete', $attachment)
                    <form action="{{ route('attachments.destroy', $attachment) }}" method="POST" class="d-inline" onsubmit="return confirm('Usunąć ten załącznik?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-link btn-sm text-danger p-0" title="Usuń załącznik">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                @endcan
            </li>
        @endforeach
    </ul>
@endif
