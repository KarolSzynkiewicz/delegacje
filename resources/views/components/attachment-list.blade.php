@props([
    'attachments',
])

@if($attachments->count() > 0)
    <div {{ $attributes->merge(['class' => 'attachment-list']) }} x-data="attachmentPreview()" @keydown.escape.window="if (open) close()">
        <ul class="list-unstyled small mb-0">
            @foreach($attachments as $attachment)
                @php
                    $label = $attachment->original_name ?: basename($attachment->file_path);
                    $kind = $attachment->previewKind();
                    $previewable = $attachment->isPreviewable();
                @endphp
                <li class="attachment-row">
                    @if($previewable)
                        <button
                            type="button"
                            class="attachment-open"
                            title="Podgląd: {{ $label }}"
                            @click="show({
                                kind: @js($kind),
                                name: @js($label),
                                previewUrl: @js(route('attachments.preview', $attachment)),
                                downloadUrl: @js(route('attachments.download', $attachment)),
                            })"
                        >
                            @if($attachment->isImage())
                                <img src="{{ $attachment->url() }}" alt="" class="attachment-thumb">
                            @elseif($attachment->isPdf())
                                <i class="bi bi-file-earmark-pdf"></i>
                            @else
                                <i class="bi bi-file-earmark-text"></i>
                            @endif
                            <span class="attachment-open__name">{{ $label }}</span>
                        </button>
                    @else
                        <a href="{{ route('attachments.download', $attachment) }}" class="attachment-open" title="Pobierz {{ $label }}">
                            <i class="bi bi-paperclip"></i>
                            <span class="attachment-open__name">{{ $label }}</span>
                        </a>
                    @endif
                    <a
                        href="{{ route('attachments.download', $attachment) }}"
                        class="attachment-dl"
                        title="Pobierz"
                        download
                    >
                        <i class="bi bi-download"></i>
                    </a>
                    @can('delete', $attachment)
                        <form action="{{ route('attachments.destroy', $attachment) }}" method="POST" class="d-inline" onsubmit="return confirm('Usunąć ten załącznik?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="attachment-dl is-danger" title="Usuń załącznik">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                    @endcan
                </li>
            @endforeach
        </ul>

        <template x-teleport="body">
            <div
                class="attachment-overlay"
                x-show="open"
                x-cloak
                x-transition.opacity
                :data-open="open ? 'true' : null"
                @click.self="close()"
                role="dialog"
                aria-modal="true"
                :aria-label="name"
            >
                <div class="attachment-overlay__panel" :class="kind === 'image' ? 'is-image' : 'is-document'" @click.stop>
                    <div class="attachment-overlay__bar">
                        <span class="attachment-overlay__name" x-text="name"></span>
                        <div class="attachment-overlay__actions">
                            <a :href="downloadUrl" class="comments-icon-btn" title="Pobierz" download>
                                <i class="bi bi-download"></i>
                            </a>
                            <button type="button" class="comments-icon-btn" title="Zamknij" @click="close()">
                                <i class="bi bi-x-lg"></i>
                            </button>
                        </div>
                    </div>
                    <div class="attachment-overlay__stage">
                        <img x-show="kind === 'image'" x-cloak :src="kind === 'image' ? previewUrl : ''" :alt="name">
                        <iframe x-show="kind === 'pdf' || kind === 'text'" x-cloak :src="(kind === 'pdf' || kind === 'text') ? previewUrl : ''" title="Podgląd"></iframe>
                    </div>
                </div>
            </div>
        </template>
    </div>
@endif
