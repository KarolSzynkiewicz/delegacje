<x-chrono.modal
    key="chrono-assist"
    close="close"
    :component-id="$this->getId()"
    :ready="true"
    :chrome="false"
    dialog-class="modal-xl modal-dialog-scrollable"
    empty-message=""
>
    <div
        x-data="{
            path: [],
            picked: null,
            busy: false,
            ready: false,
            _timer: null,
            dispatchKeys: @js($enabledKeys),
            go(key) {
                this.path = [...this.path, key];
                this.picked = null;
                this.busy = false;
                this.ready = false;
                clearTimeout(this._timer);
            },
            jump(key) {
                if (this.path[0] === key && this.path.length === 1 && !this.busy && !this.ready) {
                    this.reset();
                    return;
                }
                this.path = [key];
                this.picked = null;
                this.busy = false;
                this.ready = false;
                clearTimeout(this._timer);
            },
            reset() {
                this.path = [];
                this.picked = null;
                this.busy = false;
                this.ready = false;
                clearTimeout(this._timer);
            },
            pick(key, label) {
                clearTimeout(this._timer);
                this.picked = label;
                this.ready = false;
                this.busy = true;
                this._timer = setTimeout(() => {
                    if (this.dispatchKeys.includes(key)) {
                        Livewire.find(@js($this->getId())).call('pick', key);
                        return;
                    }
                    this.busy = false;
                    this.ready = true;
                }, 1000);
            },
            handleAssistClick(event) {
                if (this.busy) return;
                const root = event.target.closest('[data-root]');
                if (root) {
                    this.jump(root.dataset.root);
                    return;
                }
                const go = event.target.closest('[data-go]');
                if (go) {
                    this.go(go.dataset.go);
                    return;
                }
                const leaf = event.target.closest('[data-leaf]');
                if (leaf) {
                    const label = leaf.querySelector('.ac-assist__option-label, .ac-assist__tile-label')?.textContent?.trim();
                    this.pick(leaf.dataset.leaf, label || leaf.dataset.leaf);
                }
            },
        }"
        @click="handleAssistClick($event)"
    >
        <x-chrono.assist
            class="ac-assist--flush"
            :title="$title"
            :status="$status"
            :context-label="$contextLabel"
            :context-chips="$contextChips"
            :item-count="$itemCount"
            :actions="$actions"
            :enabled-keys="$enabledKeys"
        >
            <x-slot:footer>
                <button
                    type="button"
                    class="btn btn-sm btn-outline-secondary"
                    @click="Livewire.find(@js($this->getId())).call('close')"
                >
                    Zamknij
                </button>
            </x-slot:footer>
        </x-chrono.assist>
    </div>
</x-chrono.modal>
