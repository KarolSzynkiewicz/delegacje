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
    x-data="forumComposer({
        title: @js($titleValue),
        tags: @js($tagsValue),
        blocks: @js($initialBlocks),
        uploadUrl: @js(route('dashboard.posts.images', absolute: false)),
        csrf: @js(csrf_token()),
    })"
>
    <x-ui.errors />

    <div class="mb-3">
        <x-ui.input
            type="text"
            name="title"
            id="forum_title"
            label="Tytuł"
            placeholder="O czym jest wątek?"
            maxlength="80"
            required="true"
            x-model="title"
            value="{{ $titleValue }}"
        />
        <div class="forum-char font-mono"><span x-text="title.length">0</span>/80</div>
    </div>

    <div class="mb-3">
        <span class="form-label">Treść</span>
        <p class="text-muted small mb-2">Sekcje tekstu i zdjęć na przemian. Zaznacz fragment i kliknij <strong>B</strong> — albo napisz <code>**pogrubienie**</code>.</p>
        <input type="hidden" name="blocks" :value="payloadJson()">
        <div class="forum-composer">
            <template x-for="(block, index) in blocks" :key="block.key || index">
                <div class="forum-block">
                    <div class="forum-block__bar">
                        <span class="forum-block__kind font-mono" x-text="block.type === 'image' ? 'Zdjęcie' : 'Tekst'"></span>
                        <button type="button" class="forum-block__icon" title="Wyżej" x-on:click="move(index, -1)" :disabled="index === 0">
                            <i class="bi bi-chevron-up"></i>
                        </button>
                        <button type="button" class="forum-block__icon" title="Niżej" x-on:click="move(index, 1)" :disabled="index === blocks.length - 1">
                            <i class="bi bi-chevron-down"></i>
                        </button>
                        <button type="button" class="forum-block__icon is-danger" title="Usuń sekcję" x-on:click="remove(index)">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>

                    <div x-show="block.type === 'text'">
                        <div class="forum-block__tools">
                            <button type="button" class="forum-block__tool" title="Pogrubienie" x-on:click="wrapBold(index, $event)">
                                <strong>B</strong>
                            </button>
                        </div>
                        <textarea
                            class="form-control forum-block__text"
                            rows="6"
                            placeholder="Pisz tutaj. Pusta linia = nowy akapit."
                            x-model="block.content"
                        ></textarea>
                    </div>
                    <div class="forum-block__image" x-show="block.type === 'image'" x-cloak>
                        <img :src="block.url" alt="" x-show="block.url">
                        <p class="text-muted small mb-0" x-show="!block.url">Brak podglądu</p>
                    </div>
                </div>
            </template>
        </div>
        <div class="forum-composer__add">
            <button type="button" class="forum-composer__add-btn" x-on:click="addText()">
                <i class="bi bi-type"></i>
                Dodaj tekst
            </button>
            <button type="button" class="forum-composer__add-btn" x-on:click="pickImage()" :disabled="uploading || imageCount() >= 12">
                <i class="bi bi-image"></i>
                <span x-text="uploading ? 'Wgrywam…' : 'Dodaj zdjęcie'"></span>
            </button>
            <input type="file" class="d-none" accept="image/jpeg,image/png,image/jpg,image/gif,image/webp" x-ref="file" x-on:change="onFile($event)">
        </div>
        <p class="text-danger small mb-0 mt-2" x-show="error" x-cloak x-text="error"></p>
    </div>

    <div class="mb-3">
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

    <div class="forum-cover-drop mb-3">
        <div>
            <x-ui.input
                type="file"
                name="image"
                id="forum_image"
                label="Okładka na kartę (opcjonalnie)"
                accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
            />
            <small class="form-text text-muted mb-0">JPEG, PNG, WEBP, max 2 MB. Osobno od zdjęć w treści.</small>
        </div>
        @if($isEdit && $post->cover_url)
            <div class="text-end">
                <img src="{{ $post->cover_url }}" alt="" class="rounded" style="max-height: 72px;">
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="remove_image" id="remove_image" value="1">
                    <label class="form-check-label" for="remove_image">Usuń okładkę</label>
                </div>
            </div>
        @endif
    </div>

    <div class="form-check mb-4">
        <input class="form-check-input" type="checkbox" name="pinned" id="forum_pinned" value="1" {{ $pinnedValue ? 'checked' : '' }}>
        <label class="form-check-label" for="forum_pinned">Przypnij na górze tablicy</label>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <x-ui.button variant="ghost" href="{{ $isEdit ? route('dashboard.posts.show', $post) : route('dashboard') }}" action="back">
            Anuluj
        </x-ui.button>
        <x-ui.button variant="primary" type="submit" action="save">
            {{ $isEdit ? 'Zapisz' : 'Opublikuj' }}
        </x-ui.button>
    </div>
</div>

@once
@push('scripts')
<script>
    function forumComposer(opts) {
        const seed = (opts.blocks || []).map((block, i) => ({
            ...block,
            key: block.key || ('b-' + i + '-' + Math.random().toString(16).slice(2)),
            content: block.content || '',
            path: block.path || '',
            url: block.url || '',
        }));
        return {
            title: opts.title || '',
            tags: opts.tags || '',
            blocks: seed.length ? seed : [{ type: 'text', content: '', key: 'b-0' }],
            uploadUrl: opts.uploadUrl,
            csrf: opts.csrf,
            uploading: false,
            error: '',
            addTag(name) {
                const current = this.tags.split(/[,;]+/).map((t) => t.trim()).filter(Boolean);
                if (!current.some((t) => t.toLowerCase() === name.toLowerCase())) {
                    current.push(name);
                    this.tags = current.join(', ');
                }
            },
            imageCount() {
                return this.blocks.filter((b) => b.type === 'image').length;
            },
            payloadJson() {
                return JSON.stringify(this.blocks.map((b) => (
                    b.type === 'image'
                        ? { type: 'image', path: b.path }
                        : { type: 'text', content: b.content || '' }
                )));
            },
            addText() {
                this.blocks.push({ type: 'text', content: '', key: 'b-' + Date.now() });
            },
            csrfToken() {
                return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    || this.csrf
                    || '';
            },
            pickImage() {
                if (this.uploading || this.imageCount() >= 12) return;
                this.$refs.file.value = '';
                this.$refs.file.click();
            },
            async onFile(event) {
                const file = event.target.files && event.target.files[0];
                event.target.value = '';
                if (!file) return;
                this.error = '';
                this.uploading = true;
                try {
                    const token = this.csrfToken();
                    const body = new FormData();
                    body.append('_token', token);
                    body.append('image', file);
                    const res = await fetch(this.uploadUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body,
                    });
                    const data = await res.json().catch(() => ({}));
                    if (res.status === 419) {
                        this.error = 'Sesja wygasła — odśwież stronę i wgraj zdjęcie jeszcze raz.';
                        return;
                    }
                    if (!res.ok) {
                        this.error = (data.errors && data.errors.image && data.errors.image[0])
                            || data.message
                            || 'Nie udało się wgrać zdjęcia.';
                        return;
                    }
                    this.blocks.push({
                        type: 'image',
                        path: data.path,
                        url: data.url,
                        key: 'b-' + Date.now(),
                    });
                } catch (e) {
                    this.error = 'Nie udało się wgrać zdjęcia.';
                } finally {
                    this.uploading = false;
                }
            },
            move(index, dir) {
                const next = index + dir;
                if (next < 0 || next >= this.blocks.length) return;
                const copy = this.blocks.slice();
                const [item] = copy.splice(index, 1);
                copy.splice(next, 0, item);
                this.blocks = copy;
            },
            remove(index) {
                if (this.blocks.length === 1) {
                    this.blocks = [{ type: 'text', content: '', key: 'b-' + Date.now() }];
                    return;
                }
                this.blocks.splice(index, 1);
            },
            wrapBold(index, event) {
                const root = event.target.closest('.forum-block');
                const ta = root && root.querySelector('textarea');
                if (!ta) return;
                const start = ta.selectionStart;
                const end = ta.selectionEnd;
                const val = ta.value;
                const selected = val.slice(start, end) || 'pogrubienie';
                const next = val.slice(0, start) + '**' + selected + '**' + val.slice(end);
                this.blocks[index].content = next;
                this.$nextTick(() => {
                    ta.focus();
                    const pos = start + 2 + selected.length + 2;
                    ta.setSelectionRange(pos, pos);
                });
            },
        };
    }
</script>
@endpush
@endonce
