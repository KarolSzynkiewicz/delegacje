window.forumComposer = function forumComposer(opts) {
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
        dragging: false,
        dragDepth: 0,
        focusedIndex: 0,

        boot() {
            this.$nextTick(() => this.growAll());
        },
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
        csrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                || this.csrf
                || '';
        },
        textareaAt(index) {
            return this.$root.querySelector(`[data-forum-block="${index}"]`);
        },
        grow(el) {
            if (!el) {
                return;
            }
            el.style.height = 'auto';
            el.style.height = Math.max(88, el.scrollHeight) + 'px';
        },
        growAll() {
            this.$root.querySelectorAll('.forum-block__text').forEach((el) => this.grow(el));
        },
        focusBlock(index) {
            this.focusedIndex = index;
        },
        insertAt() {
            const index = this.focusedIndex;
            if (index == null || index < 0) {
                return this.blocks.length;
            }

            return Math.min(index + 1, this.blocks.length);
        },
        addText() {
            const last = this.blocks[this.blocks.length - 1];
            if (last?.type === 'text' && !(last.content || '').trim() && this.focusedIndex === this.blocks.length - 1) {
                this.$nextTick(() => {
                    const ta = this.textareaAt(this.blocks.length - 1);
                    ta?.focus();
                    this.grow(ta);
                });
                return;
            }
            const at = this.insertAt();
            this.blocks.splice(at, 0, { type: 'text', content: '', key: 'b-' + Date.now() });
            this.focusedIndex = at;
            this.$nextTick(() => {
                const ta = this.textareaAt(at);
                ta?.focus();
                this.growAll();
            });
        },
        pickImage() {
            if (this.uploading || this.imageCount() >= 12) {
                return;
            }
            this.$refs.file.value = '';
            this.$refs.file.click();
        },
        onFile(event) {
            const file = event.target.files && event.target.files[0];
            event.target.value = '';
            if (file) {
                this.uploadFile(file);
            }
        },
        onDragEnter() {
            this.dragDepth += 1;
            this.dragging = true;
        },
        onDragLeave() {
            this.dragDepth = Math.max(0, this.dragDepth - 1);
            if (this.dragDepth === 0) {
                this.dragging = false;
            }
        },
        onDrop(event) {
            this.dragDepth = 0;
            this.dragging = false;
            const file = this.firstImage(event.dataTransfer?.files);
            if (file) {
                this.uploadFile(file);
            }
        },
        onPaste(event) {
            const file = this.firstImage(event.clipboardData?.files);
            if (!file) {
                return;
            }
            event.preventDefault();
            this.uploadFile(file);
        },
        firstImage(list) {
            return [...(list || [])].find((file) => file.type && file.type.startsWith('image/')) || null;
        },
        async uploadFile(file) {
            if (this.uploading) {
                return;
            }
            if (this.imageCount() >= 12) {
                this.error = 'Najwyżej 12 zdjęć w treści.';
                return;
            }
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
                const at = this.insertAt();
                this.blocks.splice(at, 0, {
                    type: 'image',
                    path: data.path,
                    url: data.url,
                    key: 'b-' + Date.now(),
                });
                this.focusedIndex = at;
                this.$nextTick(() => this.growAll());
            } catch (e) {
                this.error = 'Nie udało się wgrać zdjęcia.';
            } finally {
                this.uploading = false;
            }
        },
        format(kind, fromToolbar) {
            if (!fromToolbar) {
                const active = document.activeElement;
                if (active && !active.classList.contains('forum-block__text')) {
                    return;
                }
            }
            let index = this.focusedIndex;
            if (index == null || !this.blocks[index] || this.blocks[index].type !== 'text') {
                index = this.blocks.findIndex((block) => block.type === 'text');
            }
            if (index < 0) {
                return;
            }
            const ta = this.textareaAt(index);
            if (!ta) {
                return;
            }
            const start = ta.selectionStart;
            const end = ta.selectionEnd;
            const val = ta.value;
            let next = val;
            let cursor = end;

            if (kind === 'bold' || kind === 'italic') {
                const wrap = kind === 'bold' ? '**' : '*';
                const fallback = kind === 'bold' ? 'pogrubienie' : 'kursywa';
                const selected = val.slice(start, end) || fallback;
                next = val.slice(0, start) + wrap + selected + wrap + val.slice(end);
                cursor = start + wrap.length + selected.length + wrap.length;
            } else if (kind === 'list') {
                const blockStart = val.lastIndexOf('\n', start - 1) + 1;
                const nl = val.indexOf('\n', end);
                const blockEnd = nl === -1 ? val.length : nl;
                const chunk = val.slice(blockStart, blockEnd);
                const listed = chunk.split('\n').map((line) => (
                    /^\s*[-*]\s/.test(line) ? line : '- ' + line
                )).join('\n');
                next = val.slice(0, blockStart) + listed + val.slice(blockEnd);
                cursor = blockStart + listed.length;
            }

            this.blocks[index].content = next;
            this.focusedIndex = index;
            this.$nextTick(() => {
                ta.focus();
                ta.setSelectionRange(cursor, cursor);
                this.grow(ta);
            });
        },
        move(index, dir) {
            const next = index + dir;
            if (next < 0 || next >= this.blocks.length) {
                return;
            }
            const copy = this.blocks.slice();
            const [item] = copy.splice(index, 1);
            copy.splice(next, 0, item);
            this.blocks = copy;
            this.focusedIndex = next;
            this.$nextTick(() => this.growAll());
        },
        remove(index) {
            if (this.blocks.length === 1) {
                this.blocks = [{ type: 'text', content: '', key: 'b-' + Date.now() }];
                this.focusedIndex = 0;
                this.$nextTick(() => this.growAll());
                return;
            }
            this.blocks.splice(index, 1);
            this.focusedIndex = Math.max(0, index - 1);
            this.$nextTick(() => this.growAll());
        },
    };
};
