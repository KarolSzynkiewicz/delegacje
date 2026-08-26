{{-- Podpowiedzi @wzmianek. Wymaga rodzica z x-data="subtaskMention(...)"
     oraz position: relative. --}}
<ul
    x-show="show && results.length > 0"
    x-cloak
    class="dropdown-menu show list-unstyled position-absolute mb-0 py-1"
    style="z-index:1090;min-width:16rem;max-height:14rem;overflow-y:auto;top:100%;left:0;right:auto;"
>
    <template x-for="(user, idx) in results" :key="user.name">
        <li>
            <button
                type="button"
                class="dropdown-item d-flex align-items-center gap-2 py-2 px-3 text-start w-100"
                :class="idx === activeIdx ? 'active' : ''"
                @click="selectUser(user)"
                @mouseenter="activeIdx = idx"
            >
                <span
                    class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary bg-opacity-25 text-primary fw-semibold flex-shrink-0"
                    style="width:1.75rem;height:1.75rem;font-size:.65rem;"
                    x-text="user.initials"
                ></span>
                <span class="small fw-medium" x-text="user.name"></span>
            </button>
        </li>
    </template>
</ul>
