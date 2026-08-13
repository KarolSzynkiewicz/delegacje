@push('scripts')
<script>
    (function () {
        const form = document.querySelector('form.js-auto-submit');
        if (!form) return;
        let t = null;
        form.querySelectorAll('.js-debounced').forEach((el) => {
            el.addEventListener('input', () => {
                if (t) clearTimeout(t);
                t = setTimeout(() => form.submit(), 300);
            });
        });
    })();
</script>
@endpush
