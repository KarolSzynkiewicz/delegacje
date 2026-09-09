@php
    $isEdit = isset($post);
    $suggestTags = $suggestTags ?? collect();
    $titleValue = old('title', $isEdit ? $post->title : '');
    $tagsValue = old('tags', $isEdit ? $post->tagsInput() : '');
    $pinnedValue = old('pinned', $isEdit ? $post->pinned : false);

    $initialBlocks = old('blocks');
    if (is_string($initialBlocks)) {
        $initialBlocks = json_decode($initialBlocks, true);
    }
    if (is_array($initialBlocks) && $initialBlocks !== []) {
        $initialBlocks = collect(\App\Models\ForumPost::normalizeBlocks($initialBlocks))
            ->map(function (array $block) {
                if (($block['type'] ?? '') === 'image') {
                    $block['url'] = asset('storage/'.$block['path']);
                }

                return $block;
            })
            ->values()
            ->all();
        if ($initialBlocks === []) {
            $initialBlocks = [['type' => 'text', 'content' => '']];
        }
    } else {
        $initialBlocks = $isEdit
            ? $post->blocksForEditor()
            : [['type' => 'text', 'content' => '']];
    }
@endphp

<div
    class="forum-sheet"
    x-data="forumComposer({
        title: @js($titleValue),
        tags: @js($tagsValue),
        blocks: @js($initialBlocks),
        uploadUrl: @js(route('dashboard.posts.images', absolute: false)),
        csrf: @js(csrf_token()),
    })"
    x-init="boot()"
    x-on:keydown.ctrl.b.prevent="format('bold')"
    x-on:keydown.meta.b.prevent="format('bold')"
    x-on:keydown.ctrl.i.prevent="format('italic')"
    x-on:keydown.meta.i.prevent="format('italic')"
    x-on:dragenter.prevent="onDragEnter()"
    x-on:dragover.prevent
    x-on:dragleave="onDragLeave()"
    x-on:drop.prevent="onDrop($event)"
    :class="{ 'is-drop': dragging }"
>
    <x-ui.errors />

    <div class="forum-sheet__toolbar" role="toolbar" aria-label="Formatowanie treści">
        <div class="forum-sheet__formats">
            <button type="button" class="forum-sheet__tool" title="Pogrubienie (Ctrl+B)" x-on:click="format('bold', true)">
                <strong>B</strong>
            </button>
            <button type="button" class="forum-sheet__tool" title="Kursywa (Ctrl+I)" x-on:click="format('italic', true)">
                <em>I</em>
            </button>
            <button type="button" class="forum-sheet__tool" title="Lista" x-on:click="format('list', true)">
                <i class="bi bi-list-ul"></i>
            </button>
            <span class="forum-sheet__sep"></span>
            <button
                type="button"
                class="forum-sheet__tool"
                title="Zdjęcie w treści"
                x-on:click="pickImage()"
                :disabled="uploading || imageCount() >= 12"
            >
                <i class="bi bi-image"></i>
            </button>
            <span class="forum-sheet__hint font-mono" x-show="uploading" x-cloak>Wgrywam…</span>
        </div>
        <label class="forum-sheet__pin">
            <input type="checkbox" name="pinned" value="1" {{ $pinnedValue ? 'checked' : '' }}>
            <i class="bi bi-pin-angle"></i>
            Przypnij
        </label>
    </div>

    <label class="visually-hidden" for="forum_title">Tytuł</label>
    <input
        type="text"
        name="title"
        id="forum_title"
        class="forum-sheet__title"
        placeholder="Tytuł wątku"
        maxlength="80"
        required
        x-model="title"
        value="{{ $titleValue }}"
    >
    <div class="forum-char font-mono"><span x-text="title.length">0</span>/80</div>

    <input type="hidden" name="blocks" :value="payloadJson()">

    <div class="forum-sheet__body">
        <template x-for="(block, index) in blocks" :key="block.key || index">
            <div class="forum-block" :class="'is-' + block.type">
                <div class="forum-block__rail">
                    <button type="button" class="forum-block__icon" title="Wyżej" x-on:click="move(index, -1)" :disabled="index === 0">
                        <i class="bi bi-chevron-up"></i>
                    </button>
                    <button type="button" class="forum-block__icon" title="Niżej" x-on:click="move(index, 1)" :disabled="index === blocks.length - 1">
                        <i class="bi bi-chevron-down"></i>
                    </button>
                    <button type="button" class="forum-block__icon is-danger" title="Usuń" x-on:click="remove(index)">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>

                <textarea
                    class="forum-block__text"
                    rows="3"
                    placeholder="Zacznij pisać, albo upuść zdjęcie…"
                    :data-forum-block="index"
                    x-show="block.type === 'text'"
                    x-model="block.content"
                    x-on:focus="focusBlock(index)"
                    x-on:input="grow($event.target)"
                    x-on:paste="onPaste($event)"
                ></textarea>

                <figure class="forum-block__figure" x-show="block.type === 'image'" x-cloak x-on:click="focusBlock(index)">
                    <img :src="block.url" alt="" x-show="block.url">
                    <p class="text-muted small mb-0" x-show="!block.url">Brak podglądu</p>
                </figure>
            </div>
        </template>
    </div>

    <div class="forum-sheet__insert">
        <button type="button" class="forum-sheet__add" x-on:click="addText()">
            <i class="bi bi-plus"></i>
            Tekst
        </button>
        <button type="button" class="forum-sheet__add" x-on:click="pickImage()" :disabled="uploading || imageCount() >= 12">
            <i class="bi bi-image"></i>
            Zdjęcie
        </button>
        <input type="file" class="d-none" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" x-ref="file" x-on:change="onFile($event)">
    </div>
    <p class="forum-sheet__drop-hint">Ctrl+B pogrubia, Ctrl+I kursywa. Zdjęcie można wkleić albo upuścić na ten obszar.</p>
    <p class="text-danger small mb-0 mt-2" x-show="error" x-cloak x-text="error"></p>

    <div class="forum-sheet__meta">
        <div>
            <x-ui.input
                type="text"
                name="tags"
                id="forum_tags"
                label="Tagi"
                placeholder="logistyka, wyjazdy, instrukcja"
                x-model="tags"
                value="{{ $tagsValue }}"
            />
            <small class="form-text text-muted">Oddziel przecinkiem, najwyżej 8. Kliknij istniejący, żeby dodać.</small>
            @if($suggestTags->isNotEmpty())
                <div class="forum-tag-cloud mt-2 mb-0">
                    @foreach($suggestTags as $tag)
                        <button
                            type="button"
                            class="forum-tag"
                            x-on:click="addTag(@js($tag->name))"
                        >#{{ $tag->name }}</button>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="forum-cover">
            <label class="forum-cover__hit" for="forum_image">
                <span class="forum-cover__slot">
                    @if($isEdit && $post->cover_url)
                        <img src="{{ $post->cover_url }}" alt="">
                    @else
                        <i class="bi bi-card-image"></i>
                    @endif
                </span>
                <span class="forum-cover__copy">
                    <span class="forum-cover__label">Okładka karty</span>
                    <span class="forum-cover__hint">Opcjonalnie · JPEG, PNG, WEBP · max 2 MB. Osobno od zdjęć w treści.</span>
                    <input
                        type="file"
                        name="image"
                        id="forum_image"
                        class="forum-cover__file"
                        accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
                    >
                </span>
            </label>
            @if($isEdit && $post->cover_url)
                <label class="forum-cover__remove" for="remove_image">
                    <input type="checkbox" name="remove_image" id="remove_image" value="1">
                    Usuń okładkę
                </label>
            @endif
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mt-4">
        <x-ui.button variant="ghost" href="{{ $isEdit ? route('dashboard.posts.show', $post) : route('dashboard') }}" action="back">
            Anuluj
        </x-ui.button>
        <x-ui.button variant="primary" type="submit" action="save">
            {{ $isEdit ? 'Zapisz' : 'Opublikuj' }}
        </x-ui.button>
    </div>
</div>
