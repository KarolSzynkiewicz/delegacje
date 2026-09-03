/**
 * Read-only procedure-run flow canvas: pan, zoom, fit-to-content.
 * Uses event delegation so Livewire morphs don't drop listeners.
 */
(function initProcedureRunFlow() {
    const NODE_W = 190;
    const NODE_H = 88;

    function rootOf(el) {
        return el?.closest?.('[data-pr-flow]') || null;
    }

    function canvasEl(root) {
        return root.querySelector('[data-pr-canvas]');
    }

    function worldEl(root) {
        return root.querySelector('[data-pr-world]');
    }

    function bounds(root) {
        const nodes = root.querySelectorAll('.pr-flow-node');
        if (!nodes.length) {
            return null;
        }
        let minX = Infinity;
        let minY = Infinity;
        let maxX = -Infinity;
        let maxY = -Infinity;
        nodes.forEach((node) => {
            const x = parseFloat(node.style.left) || 0;
            const y = parseFloat(node.style.top) || 0;
            minX = Math.min(minX, x);
            minY = Math.min(minY, y);
            maxX = Math.max(maxX, x + NODE_W);
            maxY = Math.max(maxY, y + NODE_H);
        });
        return { minX, minY, maxX, maxY, w: maxX - minX, h: maxY - minY };
    }

    function apply(root) {
        const world = worldEl(root);
        if (!world || !root._prFlow) {
            return;
        }
        const { panX, panY, scale } = root._prFlow;
        world.style.transform = `translate(${panX}px, ${panY}px) scale(${scale})`;
        const canvas = canvasEl(root);
        if (canvas) {
            canvas.style.backgroundSize = `${28 * scale}px ${28 * scale}px`;
            canvas.style.backgroundPosition = `${panX}px ${panY}px`;
        }
    }

    function fit(root) {
        const canvas = canvasEl(root);
        const box = bounds(root);
        if (!canvas || !box) {
            return;
        }
        const pad = 28;
        const cw = canvas.clientWidth || 640;
        const ch = canvas.clientHeight || 280;
        const scale = Math.min(
            1.05,
            (cw - pad * 2) / Math.max(box.w, 1),
            (ch - pad * 2) / Math.max(box.h, 1),
        );
        root._prFlow = {
            panX: (cw - box.w * scale) / 2 - box.minX * scale,
            panY: (ch - box.h * scale) / 2 - box.minY * scale,
            scale: Math.max(0.25, scale),
        };
        apply(root);
    }

    function ensureViewport(root) {
        if (!root._prFlow) {
            fit(root);
        } else {
            apply(root);
        }
    }

    function scan(scope) {
        (scope && scope.querySelectorAll ? scope : document)
            .querySelectorAll('[data-pr-flow]')
            .forEach((root) => ensureViewport(root));
    }

    document.addEventListener('wheel', (event) => {
        const canvas = event.target.closest?.('[data-pr-canvas]');
        if (!canvas) {
            return;
        }
        const root = rootOf(canvas);
        if (!root) {
            return;
        }
        event.preventDefault();
        ensureViewport(root);
        const rect = canvas.getBoundingClientRect();
        const mx = event.clientX - rect.left;
        const my = event.clientY - rect.top;
        const prev = root._prFlow.scale;
        const next = Math.max(0.25, Math.min(1.8, prev * (event.deltaY > 0 ? 0.9 : 1.1)));
        const k = next / prev;
        root._prFlow.panX = mx - (mx - root._prFlow.panX) * k;
        root._prFlow.panY = my - (my - root._prFlow.panY) * k;
        root._prFlow.scale = next;
        apply(root);
    }, { passive: false });

    let drag = null;

    document.addEventListener('pointerdown', (event) => {
        if (event.pointerType === 'mouse' && event.button !== 0) {
            return;
        }
        const canvas = event.target.closest?.('[data-pr-canvas]');
        if (!canvas || event.target.closest('.pr-flow-node')) {
            return;
        }
        const root = rootOf(canvas);
        if (!root) {
            return;
        }
        ensureViewport(root);
        drag = { root, canvas, lastX: event.clientX, lastY: event.clientY };
        canvas.classList.add('is-panning');
        canvas.setPointerCapture?.(event.pointerId);
    });

    document.addEventListener('pointermove', (event) => {
        if (!drag) {
            return;
        }
        drag.root._prFlow.panX += event.clientX - drag.lastX;
        drag.root._prFlow.panY += event.clientY - drag.lastY;
        drag.lastX = event.clientX;
        drag.lastY = event.clientY;
        apply(drag.root);
    });

    const stopDrag = () => {
        if (!drag) {
            return;
        }
        drag.canvas.classList.remove('is-panning');
        drag = null;
    };
    document.addEventListener('pointerup', stopDrag);
    document.addEventListener('pointercancel', stopDrag);

    document.addEventListener('DOMContentLoaded', () => scan());
    document.addEventListener('livewire:navigated', () => scan());
    document.addEventListener('livewire:init', () => {
        Livewire.hook('morph.updated', ({ el }) => scan(el));
        Livewire.hook('commit', ({ succeed }) => {
            succeed(() => requestAnimationFrame(() => scan()));
        });
    });

    window.initProcedureRunFlow = scan;
})();
