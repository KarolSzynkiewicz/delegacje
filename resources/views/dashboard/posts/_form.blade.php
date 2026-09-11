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
        pinned: @js((bool) $pinnedValue),
        blocks: @js($initialBlocks),
        uploadUrl: @js(route('dashboard.posts.images', absolute: false)),
        csrf: @js(csrf_token()),
        coverUrl: @js($isEdit ? ($post->cover_url ?? '') : ''),
        coverFocalX: @js((int) old('cover_focal_x', $isEdit ? ($post->cover_focal_x ?? 50) : 50)),
        coverFocalY: @js((int) old('cover_focal_y', $isEdit ? ($post->cover_focal_y ?? 50) : 50)),
        coverThreadX: @js((int) old('cover_thread_x', $isEdit ? ($post->cover_thread_x ?? $post->cover_focal_x ?? 50) : 50)),
        coverThreadY: @js((int) old('cover_thread_y', $isEdit ? ($post->cover_thread_y ?? $post->cover_focal_y ?? 50) : 50)),
    })"
    x-init="boot()"
    x-on:keydown.ctrl.b.prevent="format('bold')"
    x-on:keydown.meta.b.prevent="format('bold')"
    x-on:keydown.ctrl.i.prevent="format('italic')"
    x-on:keydown.meta.i.prevent="format('italic')"
    x-on:dragenter.prevent="onDragEnter($event)"
    x-on:dragover.prevent
    x-on:dragleave="onDragLeave()"
    x-on:drop.prevent="onDrop($event)"
    :class="{ 'is-drop': dragging }"
>
    <x-ui.errors />

    <div class="forum-sheet__toolbar" role="toolbar" aria-label="Formatowanie treści">
        <div class="forum-sheet__formats" @mousedown.prevent>
            <button type="button" class="forum-sheet__tool" title="Pogrubienie (Ctrl+B)" x-on:click="format('bold', true)">
                <strong>B</strong>
            </button>
            <button type="button" class="forum-sheet__tool" title="Kursywa (Ctrl+I)" x-on:click="format('italic', true)">
                <em>I</em>
            </button>
            <button type="button" class="forum-sheet__tool" title="Lista" x-on:click="format('list', true)">
                <i class="bi bi-list-ul"></i>
            </button>
            <button type="button" class="forum-sheet__tool forum-sheet__heading" title="Wyróżnienie H1" x-on:click="applyHeading('h1')">H1</button>
            <button type="button" class="forum-sheet__tool forum-sheet__heading" title="Wyróżnienie H2" x-on:click="applyHeading('h2')">H2</button>
            <button type="button" class="forum-sheet__tool forum-sheet__heading" title="Wyróżnienie H3" x-on:click="applyHeading('h3')">H3</button>
            <div class="forum-emoji" @click.outside="colorOpen = false">
                <button
                    type="button"
                    class="forum-sheet__tool"
                    title="Kolor czcionki"
                    x-on:click="toggleColor()"
                    :aria-expanded="colorOpen ? 'true' : 'false'"
                >
                    <i class="bi bi-palette"></i>
                </button>
                <div class="forum-emoji__menu forum-color-menu" x-show="colorOpen" x-cloak>
                    <button type="button" class="forum-color-swatch is-muted" title="Szary" x-on:click="applyColor('')"></button>
                    <button type="button" class="forum-color-swatch is-main" title="Biały" x-on:click="applyColor('forum-color-main')"></button>
                    <button type="button" class="forum-color-swatch is-primary" title="Niebieski" x-on:click="applyColor('forum-color-primary')"></button>
                    <button type="button" class="forum-color-swatch is-accent" title="Fiolet" x-on:click="applyColor('forum-color-accent')"></button>
                    <button type="button" class="forum-color-swatch is-warning" title="Żółty" x-on:click="applyColor('forum-color-warning')"></button>
                    <button type="button" class="forum-color-swatch is-danger" title="Czerwony" x-on:click="applyColor('forum-color-danger')"></button>
                    <button type="button" class="forum-color-swatch is-success" title="Zielony" x-on:click="applyColor('forum-color-success')"></button>
                </div>
            </div>
            <div class="forum-emoji" @click.outside="emojiOpen = false">
                <button
                    type="button"
                    class="forum-sheet__tool"
                    title="Emotka"
                    x-on:click="colorOpen = false; emojiOpen = !emojiOpen"
                    :aria-expanded="emojiOpen ? 'true' : 'false'"
                >
                    <span aria-hidden="true">😊</span>
                </button>
                <div class="forum-emoji__menu" x-show="emojiOpen" x-cloak>
                    <template x-for="emoji in emojis" :key="emoji">
                        <button type="button" class="forum-emoji__item" x-text="emoji" x-on:click="insertEmoji(emoji)"></button>
                    </template>
                </div>
            </div>
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
        <label class="forum-sheet__pin" :class="{ 'is-on': pinned }">
            <input type="checkbox" name="pinned" value="1" class="visually-hidden" x-model="pinned">
            <i class="bi" :class="pinned ? 'bi-pin-angle-fill' : 'bi-pin-angle'"></i>
            <span x-text="pinned ? 'Przypięte' : 'Przypnij'">{{ $pinnedValue ? 'Przypięte' : 'Przypnij' }}</span>
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
            <div class="forum-block"
                 :class="'is-' + block.type + (reorderOver === index && reorderFrom !== null && reorderFrom !== index ? ' is-drop-target' : '')"
                 draggable="true"
                 x-on:dragstart="onBlockDragStart($event, index)"
                 x-on:dragover="onBlockDragOver($event, index)"
                 x-on:drop="onBlockDrop($event, index)"
                 x-on:dragend="onBlockDragEnd()">
                <div class="forum-block__rail">
                    <span class="forum-block__grip" title="Przeciągnij, żeby zmienić kolejność" aria-hidden="true">
                        <i class="bi bi-grip-vertical"></i>
                    </span>
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

                <div
                    class="forum-block__editor forum-post__prose"
                    contenteditable="true"
                    role="textbox"
                    aria-label="Treść bloku"
                    data-placeholder="Zacznij pisać, albo upuść zdjęcie…"
                    :data-forum-block="index"
                    x-show="block.type === 'text'"
                    x-init="mountEditor($el, index)"
                    x-on:focus="focusBlock(index)"
                    x-on:input="onEditorInput($event, index)"
                    x-on:paste="onEditorPaste($event, index)"
                ></div>

                <figure class="forum-block__figure" x-show="block.type === 'image'" x-cloak x-on:click="focusBlock(index)">
                    <img :src="block.url" alt="" x-show="block.url" draggable="false">
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
    <p class="forum-sheet__drop-hint">W edytorze widać gotowy tekst. Ctrl+B pogrubia, Ctrl+I kursywa. H1–H3 to wyróżnienia. Zdjęcie wklejasz albo upuszczasz. Klocki przeciągasz za uchwyt.</p>
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
            <input type="hidden" name="cover_focal_x" :value="coverFocalX">
            <input type="hidden" name="cover_focal_y" :value="coverFocalY">
            <input type="hidden" name="cover_thread_x" :value="coverThreadX">
            <input type="hidden" name="cover_thread_y" :value="coverThreadY">
            <label class="forum-cover__hit" for="forum_image">
                <span class="forum-cover__slot" :style="coverStyle('list')">
                    <template x-if="coverUrl && !removeCover">
                        <img :src="coverUrl" alt="" draggable="false">
                    </template>
                    <template x-if="!coverUrl || removeCover">
                        <i class="bi bi-card-image"></i>
                    </template>
                </span>
                <span class="forum-cover__copy">
                    <span class="forum-cover__label">Okładka karty</span>
                    <span class="forum-cover__hint">Opcjonalnie · JPEG, PNG, WEBP · max 2 MB. Osobno od zdjęć w treści. Kadr listy i wątku ustawiasz na mapie.</span>
                    <input
                        type="file"
                        name="image"
                        id="forum_image"
                        class="forum-cover__file"
                        accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
                        x-on:change="onCoverFile($event)"
                        x-ref="coverFile"
                    >
                </span>
            </label>
            <label class="forum-cover__remove" for="remove_image" x-show="coverUrl" x-cloak>
                <input type="checkbox" name="remove_image" id="remove_image" value="1" x-model="removeCover"
                       x-on:change="if (removeCover && $refs.coverFile) $refs.coverFile.value = ''">
                Usuń okładkę
            </label>
        </div>

        <div class="forum-cover-framer" x-show="coverUrl && !removeCover" x-cloak @resize.window="coverMapTick++">
            <p class="forum-cover-framer__hint">Przeciągnij ramki po zdjęciu — lista i wątek osobno, w tym góra–dół.</p>
            <div class="forum-cover-map" x-ref="coverMap">
                <img
                    :src="coverUrl"
                    alt=""
                    x-ref="coverMapImg"
                    draggable="false"
                    @load="coverMapTick++"
                >
                <button
                    type="button"
                    class="forum-cover-map__rect is-list"
                    :style="mapRectStyle('list')"
                    @pointerdown="startMapRect($event, 'list')"
                >Lista</button>
                <button
                    type="button"
                    class="forum-cover-map__rect is-thread"
                    :style="mapRectStyle('thread')"
                    @pointerdown="startMapRect($event, 'thread')"
                >Wątek</button>
            </div>
            <div class="forum-cover-crops">
                <div>
                    <div class="forum-cover-crop forum-cover-crop--tile" :style="coverStyle('list')" aria-hidden="true">
                        <img :src="coverUrl" alt="" draggable="false">
                    </div>
                    <span class="forum-cover-crop__label">Lista</span>
                </div>
                <div class="forum-cover-crop__banner-wrap">
                    <div class="forum-cover-crop forum-cover-crop--banner" :style="coverStyle('thread')" aria-hidden="true">
                        <img :src="coverUrl" alt="" draggable="false">
                    </div>
                    <span class="forum-cover-crop__label">Wątek</span>
                </div>
            </div>
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
