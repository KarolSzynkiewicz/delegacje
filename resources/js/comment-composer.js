window.commentBodyAutocomplete = function commentBodyAutocomplete(payload) {
    const allUsers = payload.users || [];
    const subtasks = payload.subtasks || [];
    const mentionRe = /@([\p{L}\p{N}_.@-]+)([!?])?/gu;

    const kindOf = (suffix) => (suffix === '!' ? 'task' : (suffix === '?' ? 'approval' : 'notify'));
    const iconOf = (kind) => {
        if (kind === 'task') {
            return 'bi-check2-square';
        }
        if (kind === 'approval') {
            return 'bi-question-lg';
        }

        return 'bi-at';
    };
    const titleOf = (kind) => {
        if (kind === 'task') {
            return 'Zadanie';
        }
        if (kind === 'approval') {
            return 'Prośba o zatwierdzenie';
        }

        return 'Wzmianka';
    };

    return {
        show: false,
        results: [],
        activeIdx: 0,
        files: [],
        mentionMode: null,
        mentionSuffix: '',
        isEmpty: true,

        boot() {
            this.renderFromText(this.$refs.body?.value || '');
            this.syncBody();
            this.$el.closest('form')?.addEventListener('submit', () => {
                this.tryCommitMention();
                this.syncBody();
            });
        },

        onFiles(event) {
            this.files = Array.from(event.target.files || []);
        },

        fileSummary() {
            if (this.files.length === 0) {
                return '';
            }
            if (this.files.length === 1) {
                return this.files[0].name;
            }
            const n = this.files.length;

            return n + (n < 5 ? ' pliki' : ' plików');
        },

        resolveHandle(handle) {
            const q = String(handle || '').toLowerCase();
            if (q === 'wszyscy') {
                return { name: 'wszyscy' };
            }
            const user = allUsers.find((u) => u.name.toLowerCase() === q);

            return user ? { name: user.name } : null;
        },

        createChip(name, suffix) {
            const kind = kindOf(suffix || '');
            const span = document.createElement('span');
            span.className = 'comment-chip comment-chip--' + kind + (name === 'wszyscy' ? ' comment-chip--all' : '');
            span.contentEditable = 'false';
            span.dataset.handle = name;
            span.dataset.suffix = suffix || '';
            span.title = titleOf(kind);
            const icon = document.createElement('i');
            icon.className = 'bi ' + iconOf(kind);
            icon.setAttribute('aria-hidden', 'true');
            const label = document.createElement('span');
            label.className = 'comment-chip__name';
            label.textContent = name;
            span.append(icon, label);

            return span;
        },

        serialize(root) {
            let out = '';
            const walk = (node) => {
                if (node.nodeType === Node.TEXT_NODE) {
                    out += node.textContent || '';
                    return;
                }
                if (node.nodeType !== Node.ELEMENT_NODE) {
                    return;
                }
                if (node.classList?.contains('comment-chip')) {
                    out += '@' + (node.dataset.handle || '') + (node.dataset.suffix || '');
                    return;
                }
                if (node.tagName === 'BR') {
                    out += '\n';
                    return;
                }
                if ((node.tagName === 'DIV' || node.tagName === 'P') && out && !out.endsWith('\n')) {
                    out += '\n';
                }
                Array.from(node.childNodes).forEach(walk);
            };
            Array.from((root || this.$refs.editor).childNodes).forEach(walk);

            return out.replace(/\u00a0/g, ' ');
        },

        syncBody() {
            const value = this.serialize(this.$refs.editor);
            if (this.$refs.body) {
                this.$refs.body.value = value;
            }
            this.isEmpty = value.trim() === '';
        },

        renderFromText(text) {
            const editor = this.$refs.editor;
            if (!editor) {
                return;
            }
            editor.innerHTML = '';
            const raw = String(text || '');
            mentionRe.lastIndex = 0;
            let last = 0;
            let match;
            while ((match = mentionRe.exec(raw))) {
                const known = this.resolveHandle(match[1]);
                if (!known) {
                    continue;
                }
                if (match.index > last) {
                    editor.appendChild(document.createTextNode(raw.slice(last, match.index)));
                }
                editor.appendChild(this.createChip(known.name, match[2] || ''));
                last = match.index + match[0].length;
            }
            if (last < raw.length) {
                editor.appendChild(document.createTextNode(raw.slice(last)));
            }
        },

        caretText() {
            const editor = this.$refs.editor;
            const sel = window.getSelection();
            if (!sel || sel.rangeCount === 0 || !editor.contains(sel.anchorNode)) {
                return { node: null, offset: 0, before: '' };
            }
            let node = sel.anchorNode;
            let offset = sel.anchorOffset;
            if (node.nodeType !== Node.TEXT_NODE) {
                return { node: null, offset: 0, before: this.textBeforeCaret() };
            }

            return { node, offset, before: node.textContent.slice(0, offset) };
        },

        textBeforeCaret() {
            const editor = this.$refs.editor;
            const sel = window.getSelection();
            if (!sel || sel.rangeCount === 0) {
                return this.serialize(editor);
            }
            const end = sel.getRangeAt(0);
            if (!editor.contains(end.startContainer)) {
                return this.serialize(editor);
            }
            const range = document.createRange();
            range.selectNodeContents(editor);
            range.setEnd(end.startContainer, end.startOffset);
            const holder = document.createElement('div');
            holder.appendChild(range.cloneContents());

            return this.serialize(holder);
        },

        placeCaret(node, offset) {
            const sel = window.getSelection();
            const range = document.createRange();
            range.setStart(node, Math.min(offset, node.textContent?.length ?? 0));
            range.collapse(true);
            sel.removeAllRanges();
            sel.addRange(range);
        },

        placeCaretAfter(node) {
            const sel = window.getSelection();
            const range = document.createRange();
            range.setStartAfter(node);
            range.collapse(true);
            sel.removeAllRanges();
            sel.addRange(range);
        },

        insertChipAtCaret(name, suffix) {
            const chip = this.createChip(name, suffix || '');
            const sel = window.getSelection();
            if (!sel || sel.rangeCount === 0) {
                this.$refs.editor.appendChild(chip);
                this.placeCaretAfter(chip);
                return chip;
            }
            const range = sel.getRangeAt(0);
            range.deleteContents();
            range.insertNode(chip);
            this.placeCaretAfter(chip);

            return chip;
        },

        insertText(text) {
            const sel = window.getSelection();
            if (!sel || sel.rangeCount === 0) {
                this.$refs.editor.appendChild(document.createTextNode(text));
                return;
            }
            const range = sel.getRangeAt(0);
            range.deleteContents();
            const node = document.createTextNode(text);
            range.insertNode(node);
            this.placeCaret(node, text.length);
        },

        deleteAtToken() {
            const { node, offset, before } = this.caretText();
            if (!node) {
                return false;
            }
            const match = before.match(/(^|\s)@(\S*)$/u);
            if (!match) {
                return false;
            }
            const atPos = before.lastIndexOf('@');
            node.textContent = before.slice(0, atPos) + (node.textContent || '').slice(offset);
            this.placeCaret(node, atPos);

            return true;
        },

        tryCommitMention() {
            const { node, offset, before } = this.caretText();
            if (!node) {
                return false;
            }
            const match = before.match(/@([\p{L}\p{N}_.@-]+)([!?])?$/u);
            if (!match) {
                return false;
            }
            const known = this.resolveHandle(match[1]);
            if (!known) {
                return false;
            }
            const suffix = match[2] || this.mentionSuffix || '';
            const atPos = before.lastIndexOf('@');
            node.textContent = before.slice(0, atPos) + (node.textContent || '').slice(offset);
            this.placeCaret(node, atPos);
            this.insertChipAtCaret(known.name, suffix);
            this.mentionMode = null;
            this.mentionSuffix = '';
            this.closeSuggest();
            this.syncBody();

            return true;
        },

        startMention(mode) {
            const suffix = mode === 'task' ? '!' : (mode === 'approval' ? '?' : '');
            this.mentionMode = mode;
            this.mentionSuffix = suffix;
            this.$refs.editor.focus();
            const before = this.textBeforeCaret();
            if (!/(^|\s)@(\S*)$/u.test(before)) {
                const pad = before.length > 0 && !/\s$/.test(before) ? ' @' : '@';
                this.insertText(pad);
            }
            this.onInput();
        },

        onEditorInput() {
            this.syncBody();
            this.onInput();
        },

        onKeydown(event) {
            if (event.key === 'Escape') {
                this.close();
                return;
            }
            if (this.show && this.results.length) {
                if (event.key === 'ArrowDown') {
                    event.preventDefault();
                    this.moveActive(1);
                    return;
                }
                if (event.key === 'ArrowUp') {
                    event.preventDefault();
                    this.moveActive(-1);
                    return;
                }
                if (event.key === 'Enter' || event.key === 'Tab') {
                    event.preventDefault();
                    this.pickActive();
                    return;
                }
            }
            if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
                event.preventDefault();
                this.tryCommitMention();
                this.syncBody();
                this.$el.closest('form')?.requestSubmit();
                return;
            }
            if (event.key === 'Enter') {
                event.preventDefault();
                this.tryCommitMention();
                this.insertText('\n');
                this.syncBody();
                return;
            }
            if (event.key === ' ' || event.key === ',') {
                if (this.tryCommitMention()) {
                    event.preventDefault();
                    this.insertText(event.key === ',' ? ', ' : ' ');
                    this.syncBody();
                }
            }
        },

        onPaste(event) {
            event.preventDefault();
            const text = event.clipboardData?.getData('text/plain') || '';
            if (!text) {
                return;
            }
            const before = this.textBeforeCaret();
            const full = this.serialize(this.$refs.editor);
            const after = full.slice(before.length);
            this.renderFromText(before + text + after);
            this.syncBody();
            this.onInput();
            const editor = this.$refs.editor;
            if (editor.lastChild) {
                if (editor.lastChild.nodeType === Node.TEXT_NODE) {
                    this.placeCaret(editor.lastChild, editor.lastChild.textContent.length);
                } else {
                    this.placeCaretAfter(editor.lastChild);
                }
            }
        },

        onInput() {
            const text = this.textBeforeCaret();
            const atMatch = text.match(/(^|(?<=\s))@(\S*)$/u);
            if (atMatch) {
                const fragment = atMatch[2];
                const q = fragment.replace(/[!?]+$/u, '').toLowerCase();
                const userResults = allUsers
                    .filter((u) => q === '' || u.name.toLowerCase().includes(q))
                    .slice(0, 7)
                    .map((u) => ({ kind: 'user', name: u.name, initials: u.initials }));
                const wszyscyResults = (q === '' || 'wszyscy'.startsWith(q))
                    ? [{ kind: 'user', name: 'wszyscy', initials: '★', isEveryone: true }]
                    : [];
                this.results = [...wszyscyResults, ...userResults];
                this.activeIdx = 0;
                this.show = this.results.length > 0;
                return;
            }

            const hashMatch = subtasks.length ? text.match(/(^|(?<=\s))#(\d*)$/u) : null;
            if (hashMatch) {
                this.mentionMode = null;
                this.mentionSuffix = '';
                const fragment = hashMatch[2];
                this.results = subtasks
                    .filter((s) => fragment === '' || String(s.num).startsWith(fragment))
                    .slice(0, 8)
                    .map((s) => ({ kind: 'subtask', num: s.num, name: s.name }));
                this.activeIdx = 0;
                this.show = this.results.length > 0;
                return;
            }

            this.closeSuggest();
        },

        moveActive(delta) {
            if (!this.show || this.results.length === 0) {
                return;
            }
            const n = this.results.length;
            this.activeIdx = (this.activeIdx + delta + n) % n;
        },

        pickActive() {
            if (!this.show || this.results.length === 0) {
                return;
            }
            this.selectItem(this.results[this.activeIdx]);
        },

        selectItem(item) {
            this.deleteAtToken();
            if (item.kind === 'user') {
                this.insertChipAtCaret(item.name, this.mentionSuffix);
                this.insertText(' ');
            } else {
                this.insertText('#' + item.num + ' ');
            }
            this.$refs.editor.focus();
            this.close();
            this.syncBody();
        },

        closeSuggest() {
            this.show = false;
            this.results = [];
        },

        close() {
            this.closeSuggest();
            this.mentionMode = null;
            this.mentionSuffix = '';
        },
    };
};
