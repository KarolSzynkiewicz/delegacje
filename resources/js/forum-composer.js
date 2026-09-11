window.forumComposer = function forumComposer(opts) {
    const seed = (opts.blocks || []).map((block, i) => ({
        ...block,
        key: block.key || ('b-' + i + '-' + Math.random().toString(16).slice(2)),
        content: block.content || '',
        path: block.path || '',
        url: block.url || '',
    }));

    const clamp = (value) => Math.max(0, Math.min(100, Math.round(Number(value) || 0)));

    return {
        title: opts.title || '',
        tags: opts.tags || '',
        pinned: !!opts.pinned,
        blocks: seed.length ? seed : [{ type: 'text', content: '', key: 'b-0' }],
        uploadUrl: opts.uploadUrl,
        csrf: opts.csrf,
        uploading: false,
        error: '',
        dragging: false,
        dragDepth: 0,
        focusedIndex: 0,
        emojiOpen: false,
        colorOpen: false,
        emojis: ['👍', '👎', '✅', '❌', '⚠️', '💡', '🔥', '🎉', '🙌', '👏', '❤️', '💜', '✨', '📌', '📎', '🗓️', '⏰', '📍', '🚗', '✈️', '🏠', '🛠️', '📦', '📝', '💬', '📞', '👷', '🚢', '💶', '📊', '🎯', '⭐', '🚀', '👀', '🤝', '☕'],
        coverUrl: opts.coverUrl || '',
        coverFocalX: clamp(opts.coverFocalX ?? 50),
        coverFocalY: clamp(opts.coverFocalY ?? 50),
        coverThreadX: clamp(opts.coverThreadX ?? opts.coverFocalX ?? 50),
        coverThreadY: clamp(opts.coverThreadY ?? opts.coverFocalY ?? 50),
        coverZoom: 1.2,
        coverListAspect: 4 / 5,
        coverThreadAspect: 16 / 5,
        coverMapTick: 0,
        removeCover: false,
        reorderFrom: null,
        reorderOver: null,

        boot() {
            try {
                document.execCommand('styleWithCSS', false, false);
            } catch (e) {
                // execCommand is best-effort in the editor toolbar.
            }
            this.$nextTick(() => this.growAll());
        },
        coverPos(kind) {
            if (kind === 'thread') {
                return this.coverThreadX + '% ' + this.coverThreadY + '%';
            }

            return this.coverFocalX + '% ' + this.coverFocalY + '%';
        },
        coverStyle(kind) {
            return '--forum-cover-pos:' + this.coverPos(kind);
        },
        coverCropNorm(kind) {
            const img = this.$refs.coverMapImg;
            const nw = img?.naturalWidth || 1;
            const nh = img?.naturalHeight || 1;
            const imgAspect = nw / nh;
            const cropAspect = kind === 'thread' ? this.coverThreadAspect : this.coverListAspect;
            const z = this.coverZoom;
            let cropW;
            let cropH;
            if (imgAspect > cropAspect) {
                cropH = 1 / z;
                cropW = (cropAspect / imgAspect) / z;
            } else {
                cropW = 1 / z;
                cropH = (imgAspect / cropAspect) / z;
            }

            return {
                cropW: Math.min(1, Math.max(0.12, cropW)),
                cropH: Math.min(1, Math.max(0.12, cropH)),
            };
        },
        mapRectStyle(kind) {
            this.coverMapTick;
            const img = this.$refs.coverMapImg;
            if (!img || !img.clientWidth) {
                return { display: 'none' };
            }
            const { cropW, cropH } = this.coverCropNorm(kind);
            const fx = kind === 'thread' ? this.coverThreadX : this.coverFocalX;
            const fy = kind === 'thread' ? this.coverThreadY : this.coverFocalY;
            const left = (1 - cropW) * (fx / 100);
            const top = (1 - cropH) * (fy / 100);

            return {
                left: (left * img.clientWidth) + 'px',
                top: (top * img.clientHeight) + 'px',
                width: (cropW * img.clientWidth) + 'px',
                height: (cropH * img.clientHeight) + 'px',
            };
        },
        startMapRect(event, kind) {
            if (event.button != null && event.button !== 0) {
                return;
            }
            event.preventDefault();
            const img = this.$refs.coverMapImg;
            if (!img) {
                return;
            }
            const { cropW, cropH } = this.coverCropNorm(kind);
            const startFx = kind === 'thread' ? this.coverThreadX : this.coverFocalX;
            const startFy = kind === 'thread' ? this.coverThreadY : this.coverFocalY;
            const startX = event.clientX;
            const startY = event.clientY;
            const dispW = img.clientWidth || 1;
            const dispH = img.clientHeight || 1;
            const rangeX = Math.max(0.0001, 1 - cropW);
            const rangeY = Math.max(0.0001, 1 - cropH);
            const move = (e) => {
                const dx = (e.clientX - startX) / dispW;
                const dy = (e.clientY - startY) / dispH;
                const nextX = clamp(startFx + (dx / rangeX) * 100);
                const nextY = clamp(startFy + (dy / rangeY) * 100);
                if (kind === 'thread') {
                    this.coverThreadX = nextX;
                    this.coverThreadY = nextY;
                } else {
                    this.coverFocalX = nextX;
                    this.coverFocalY = nextY;
                }
            };
            const up = () => {
                window.removeEventListener('pointermove', move);
                window.removeEventListener('pointerup', up);
            };
            window.addEventListener('pointermove', move);
            window.addEventListener('pointerup', up);
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
        editorAt(index) {
            return this.$root.querySelector(`[data-forum-block="${index}"]`);
        },
        textareaAt(index) {
            return this.editorAt(index);
        },
        grow(el) {
            if (!el) {
                return;
            }
            el.style.minHeight = '5.5rem';
        },
        growAll() {
            this.$root.querySelectorAll('.forum-block__editor').forEach((el) => this.grow(el));
        },
        markEmpty(el) {
            if (!el) {
                return;
            }
            const text = (el.textContent || '').replace(/\u200B/g, '').trim();
            const hasMark = !!el.querySelector('span, strong, b, em, i, ul, ol, li, h1, h2, h3');
            el.classList.toggle('is-empty', text === '' && !hasMark);
        },
        isBlankContent(content) {
            const tmp = document.createElement('div');
            tmp.innerHTML = content || '';
            return !(tmp.textContent || '').replace(/\u200B/g, '').trim();
        },
        mountEditor(el, index) {
            const block = this.blocks[index];
            if (!el || !block) {
                return;
            }
            el.innerHTML = block.content || '';
            this.markEmpty(el);
            this.grow(el);
        },
        onEditorInput(event, index) {
            this.syncFromEditor(index, event.target);
        },
        onEditorPaste(event, index) {
            const file = this.firstImage(event.clipboardData?.files);
            if (file) {
                event.preventDefault();
                this.uploadFile(file);
                return;
            }
            event.preventDefault();
            const text = event.clipboardData?.getData('text/plain') || '';
            document.execCommand('insertText', false, text);
            this.syncFromEditor(index, event.target);
        },
        syncFromEditor(index, el) {
            const editor = el || this.editorAt(index);
            if (!editor || !this.blocks[index]) {
                return;
            }
            this.blocks[index].content = editor.innerHTML;
            this.markEmpty(editor);
            this.grow(editor);
        },
        focusEditor() {
            let index = this.focusedIndex;
            if (index == null || !this.blocks[index] || this.blocks[index].type !== 'text') {
                index = this.blocks.findIndex((block) => block.type === 'text');
                if (index < 0) {
                    this.addText();
                    index = this.focusedIndex;
                } else {
                    this.focusedIndex = index;
                }
            }
            const el = this.editorAt(index);
            el?.focus();
            return el;
        },
        toggleColor() {
            this.emojiOpen = false;
            this.colorOpen = !this.colorOpen;
        },
        applyHeading(tag) {
            const editor = this.focusEditor();
            if (!editor) {
                return;
            }
            const current = this.currentBlockTag();
            const next = current === tag ? 'p' : tag;
            document.execCommand('formatBlock', false, next);
            this.syncFromEditor(this.focusedIndex);
        },
        currentBlockTag() {
            const sel = window.getSelection();
            if (!sel || !sel.anchorNode) {
                return '';
            }
            let el = sel.anchorNode.nodeType === 1 ? sel.anchorNode : sel.anchorNode.parentElement;
            while (el && !el.classList.contains('forum-block__editor')) {
                const name = el.tagName ? el.tagName.toLowerCase() : '';
                if (name === 'h1' || name === 'h2' || name === 'h3' || name === 'p') {
                    return name;
                }
                el = el.parentElement;
            }

            return '';
        },
        applyColor(className) {
            this.colorOpen = false;
            this.applyClass(className, 'forum-color-');
        },
        spanAncestor(node, prefix) {
            let el = node && node.nodeType === 1 ? node : node?.parentElement;
            while (el && !el.classList.contains('forum-block__editor')) {
                if (el.tagName === 'SPAN' && [...el.classList].some((c) => c.startsWith(prefix))) {
                    return el;
                }
                el = el.parentElement;
            }
            return null;
        },
        applyClass(className, prefix) {
            const editor = this.focusEditor();
            if (!editor) {
                return;
            }
            const sel = window.getSelection();
            if (!sel || sel.rangeCount === 0 || !editor.contains(sel.anchorNode)) {
                return;
            }
            const range = sel.getRangeAt(0);
            const existing = this.spanAncestor(range.commonAncestorContainer, prefix)
                || this.spanAncestor(range.startContainer, prefix);

            if (existing && (range.collapsed || existing.contains(range.commonAncestorContainer) || existing === range.commonAncestorContainer)) {
                const kept = [...existing.classList].filter((c) => !c.startsWith(prefix));
                if (className) {
                    kept.push(className);
                }
                if (kept.length) {
                    existing.className = kept.join(' ');
                } else {
                    const parent = existing.parentNode;
                    while (existing.firstChild) {
                        parent.insertBefore(existing.firstChild, existing);
                    }
                    parent.removeChild(existing);
                }
                this.syncFromEditor(this.focusedIndex);
                return;
            }

            if (range.collapsed) {
                if (!className) {
                    return;
                }
                const span = document.createElement('span');
                span.className = className;
                span.appendChild(document.createTextNode('\u200B'));
                range.insertNode(span);
                const next = document.createRange();
                next.selectNodeContents(span);
                next.collapse(false);
                sel.removeAllRanges();
                sel.addRange(next);
                this.syncFromEditor(this.focusedIndex);
                return;
            }

            const fragment = range.extractContents();
            if (!className) {
                range.insertNode(fragment);
                this.syncFromEditor(this.focusedIndex);
                return;
            }
            const span = document.createElement('span');
            span.className = className;
            span.appendChild(fragment);
            range.insertNode(span);
            const next = document.createRange();
            next.selectNodeContents(span);
            sel.removeAllRanges();
            sel.addRange(next);
            this.syncFromEditor(this.focusedIndex);
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
            if (last?.type === 'text' && this.isBlankContent(last.content) && this.focusedIndex === this.blocks.length - 1) {
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
        onCoverFile(event) {
            const file = event.target.files && event.target.files[0];
            if (!file) {
                return;
            }
            this.removeCover = false;
            const reader = new FileReader();
            reader.onload = () => {
                this.coverUrl = reader.result;
                this.coverFocalX = 50;
                this.coverFocalY = 50;
                this.coverThreadX = 50;
                this.coverThreadY = 50;
                this.coverMapTick++;
            };
            reader.readAsDataURL(file);
        },
        isFileDrag(event) {
            return [...(event.dataTransfer?.types || [])].includes('Files');
        },
        onDragEnter(event) {
            if (this.reorderFrom !== null || !this.isFileDrag(event)) {
                return;
            }
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
            if (this.reorderFrom !== null) {
                return;
            }
            const file = this.firstImage(event.dataTransfer?.files);
            if (file) {
                this.uploadFile(file);
            }
        },
        onBlockDragStart(event, index) {
            if (event.target.closest('textarea, input, a, .forum-block__icon, .forum-block__editor')) {
                event.preventDefault();
                return;
            }
            this.reorderFrom = index;
            this.reorderOver = index;
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', String(index));
        },
        onBlockDragOver(event, index) {
            if (this.reorderFrom === null) {
                return;
            }
            event.preventDefault();
            this.reorderOver = index;
        },
        onBlockDrop(event, index) {
            if (this.reorderFrom === null) {
                return;
            }
            event.preventDefault();
            event.stopPropagation();
            const from = this.reorderFrom;
            this.reorderFrom = null;
            this.reorderOver = null;
            if (from === index) {
                return;
            }
            const copy = this.blocks.slice();
            const [item] = copy.splice(from, 1);
            copy.splice(index, 0, item);
            this.blocks = copy;
            this.focusedIndex = index;
            this.$nextTick(() => this.growAll());
        },
        onBlockDragEnd() {
            this.reorderFrom = null;
            this.reorderOver = null;
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
        insertEmoji(emoji) {
            this.emojiOpen = false;
            const editor = this.focusEditor();
            if (!editor) {
                return;
            }
            document.execCommand('insertText', false, emoji);
            this.syncFromEditor(this.focusedIndex);
        },
        format(kind, fromToolbar) {
            if (!fromToolbar) {
                const active = document.activeElement;
                if (!active || !active.classList.contains('forum-block__editor')) {
                    return;
                }
            }
            const editor = this.focusEditor();
            if (!editor) {
                return;
            }
            if (kind === 'bold') {
                document.execCommand('bold');
            } else if (kind === 'italic') {
                document.execCommand('italic');
            } else if (kind === 'list') {
                document.execCommand('insertUnorderedList');
            }
            this.syncFromEditor(this.focusedIndex);
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

document.addEventListener('alpine:init', () => {
    window.Alpine.store('forumLightbox', {
        open: false,
        src: '',
        show(src) {
            if (!src) {
                return;
            }
            this.src = src;
            this.open = true;
        },
        close() {
            this.open = false;
            this.src = '';
        },
    });
});
