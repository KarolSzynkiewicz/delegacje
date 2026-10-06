<script>
    (function () {
        const documentSelect = document.getElementById('document_id');
        const companyField = document.getElementById('company-field');
        const companySelect = document.getElementById('company_id');
        const validToField = document.getElementById('valid-to-field');
        const validToInput = document.getElementById('valid_to');
        const hint = document.getElementById('type-rules-hint');
        if (!documentSelect) {
            return;
        }

        const sync = function () {
            const option = documentSelect.options[documentSelect.selectedIndex];
            const hasType = !!(option && option.value);
            const scoped = hasType && option.dataset.companyScoped === '1';
            const periodic = hasType && option.dataset.periodic === '1';

            if (companyField) {
                companyField.classList.toggle('d-none', !scoped);
            }
            if (companySelect) {
                companySelect.required = scoped;
                companySelect.disabled = !scoped;
            }
            if (validToField) {
                validToField.classList.toggle('d-none', !periodic);
            }
            if (validToInput) {
                validToInput.required = periodic;
                if (!periodic) {
                    validToInput.value = '';
                }
            }
            if (hint) {
                hint.textContent = hasType
                    ? (option.dataset.hint || '')
                    : 'Wybierz typ — spółka i data końca zależą od wymagań formalnych.';
            }
        };

        documentSelect.addEventListener('change', sync);
        sync();
    })();
</script>
