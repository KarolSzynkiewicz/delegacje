<template x-teleport="body">
    <div
        class="attachment-overlay forum-lightbox"
        x-show="$store.forumLightbox.open"
        x-cloak
        x-transition.opacity
        :data-open="$store.forumLightbox.open ? 'true' : null"
        @click.self="$store.forumLightbox.close()"
        @keydown.escape.window="if ($store.forumLightbox.open) $store.forumLightbox.close()"
        role="dialog"
        aria-modal="true"
        aria-label="Zdjęcie"
    >
        <div class="attachment-overlay__panel is-image" @click.stop>
            <div class="attachment-overlay__bar">
                <span class="attachment-overlay__name">Zdjęcie</span>
                <div class="attachment-overlay__actions">
                    <a :href="$store.forumLightbox.src" class="comments-icon-btn" title="Otwórz oryginał" target="_blank" rel="noopener">
                        <i class="bi bi-box-arrow-up-right"></i>
                    </a>
                    <button type="button" class="comments-icon-btn" title="Zamknij" @click="$store.forumLightbox.close()">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>
            <div class="attachment-overlay__stage">
                <img :src="$store.forumLightbox.src" alt="">
            </div>
        </div>
    </div>
</template>
