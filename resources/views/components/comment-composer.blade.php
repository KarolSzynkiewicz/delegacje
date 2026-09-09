@props([
    'placeholder' => '@osoba, @osoba! albo @osoba?…',
    'rows' => 2,
    'autocompletePayload' => ['users' => [], 'subtasks' => []],
    'submitTitle' => 'Wyślij',
    'fileInputId' => null,
    'value' => '',
])

@php
    $fileInputId = $fileInputId ?: 'comment-files-'.uniqid();
    $accept = '.pdf,.jpg,.jpeg,.png,.gif,.webp,.doc,.docx,.xls,.xlsx,.txt,.zip,application/pdf,image/*';
@endphp

<div class="comments-composer" x-data="commentBodyAutocomplete(@js($autocompletePayload))" x-init="boot()">
    <input type="hidden" name="body" x-ref="body" value="{{ $value }}">
    <div
        class="comments-composer-input comments-composer-editor"
        :class="{ 'is-empty': isEmpty }"
        contenteditable="true"
        role="textbox"
        aria-label="Treść komentarza"
        data-placeholder="{{ $placeholder }}"
        style="min-height: {{ max(2, (int) $rows) * 1.45 }}rem"
        x-ref="editor"
        @input="onEditorInput()"
        @keydown="onKeydown($event)"
        @paste="onPaste($event)"
        @blur="tryCommitMention(); syncBody()"
    ></div>

    <div class="comments-composer-toolbar">
        <div class="comments-composer-tools" role="toolbar" aria-label="Akcje komentarza">
            <button
                type="button"
                class="comments-icon-btn"
                :class="mentionMode === 'notify' ? 'is-mode' : ''"
                title="Wzmianka — powiadom osobę (@imię)"
                aria-label="Wzmianka"
                x-on:click="startMention('notify')"
            >
                <i class="bi bi-at"></i>
            </button>
            <button
                type="button"
                class="comments-icon-btn"
                :class="mentionMode === 'task' ? 'is-mode is-mode-work' : ''"
                title="Zadanie z kontekstu (@imię!)"
                aria-label="Zadanie z kontekstu"
                x-on:click="startMention('task')"
            >
                <i class="bi bi-check2-square"></i>
            </button>
            <button
                type="button"
                class="comments-icon-btn"
                :class="mentionMode === 'approval' ? 'is-mode is-mode-work' : ''"
                title="Prośba o zatwierdzenie (@imię?)"
                aria-label="Prośba o zatwierdzenie"
                x-on:click="startMention('approval')"
            >
                <i class="bi bi-question-lg"></i>
            </button>
        </div>
        <span class="comments-composer-files" x-cloak x-show="files.length > 0" x-text="fileSummary()"></span>
        <label
            class="comments-icon-btn"
            :class="files.length > 0 ? 'is-attached' : ''"
            for="{{ $fileInputId }}"
            :title="files.length > 0 ? fileSummary() + ' — kliknij aby zmienić' : 'Dodaj załącznik'"
        >
            <i class="bi bi-paperclip"></i>
            <span class="comments-attach-count" x-cloak x-show="files.length > 1" x-text="files.length"></span>
        </label>
        <input
            id="{{ $fileInputId }}"
            type="file"
            name="attachments[]"
            class="comments-file-input"
            multiple
            accept="{{ $accept }}"
            @change="onFiles($event)"
        >
        <button type="submit" class="comments-icon-btn comments-send-btn" title="{{ $submitTitle }} (Ctrl+Enter)" aria-label="{{ $submitTitle }}">
            <i class="bi bi-arrow-return-left"></i>
        </button>
        {{ $toolbar ?? '' }}
    </div>

    <ul
        @mousedown.prevent
        x-show="show && results.length > 0"
        x-cloak
        class="dropdown-menu show list-unstyled position-absolute mb-0 py-1 comments-composer-suggest"
    >
        <template x-for="(item, idx) in results" :key="item.kind === 'user' ? ('u-' + item.name) : ('s-' + item.num)">
            <li>
                <button
                    type="button"
                    class="dropdown-item d-flex align-items-center gap-2 py-2 px-3 text-start w-100"
                    :class="idx === activeIdx ? 'active' : ''"
                    @click="selectItem(item)"
                    @mouseenter="activeIdx = idx"
                >
                    <span x-show="item.kind === 'user'" class="d-flex align-items-center gap-2 w-100 min-w-0">
                        <span
                            class="d-inline-flex align-items-center justify-content-center rounded-circle fw-semibold flex-shrink-0"
                            :class="item.isEveryone ? 'bg-warning bg-opacity-25 text-warning' : 'bg-primary bg-opacity-25 text-primary'"
                            style="width:1.6rem;height:1.6rem;font-size:.62rem;"
                            x-text="item.initials"
                        ></span>
                        <span class="small fw-medium text-truncate" x-text="item.isEveryone ? ('@wszyscy' + mentionSuffix + ' — powiadomienie do wszystkich') : ('@' + item.name + mentionSuffix)"></span>
                    </span>
                    <span x-show="item.kind === 'subtask'" class="d-flex align-items-center gap-2 w-100 min-w-0">
                        <span class="badge bg-secondary bg-opacity-50 text-body flex-shrink-0" x-text="'#' + item.num"></span>
                        <span class="small text-truncate" style="max-width:14rem;" x-text="item.name"></span>
                    </span>
                </button>
            </li>
        </template>
    </ul>
</div>
