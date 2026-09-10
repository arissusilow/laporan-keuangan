document.querySelectorAll('[data-submit-loading]').forEach((form) => {
    form.addEventListener('submit', () => {
        const button = form.querySelector('button[type="submit"]');
        if (! button) return;

        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        button.textContent = button.dataset.loadingLabel || 'Memproses…';
    });
});

document.querySelectorAll('details').forEach((details) => {
    details.addEventListener('toggle', () => {
        if (! details.open) return;

        document.querySelectorAll('details[open]').forEach((other) => {
            if (other !== details) other.removeAttribute('open');
        });
    });
});

document.querySelectorAll('[data-password-toggle]').forEach((toggle) => {
    toggle.addEventListener('click', () => {
        const controlledIds = (toggle.getAttribute('aria-controls') || '').split(/\s+/).filter(Boolean);
        const inputs = controlledIds.map((id) => document.getElementById(id)).filter(Boolean);
        if (! inputs.length) return;

        const showPassword = inputs[0].type === 'password';
        inputs.forEach((input) => {
            input.type = showPassword ? 'text' : 'password';
        });
        toggle.setAttribute('aria-pressed', String(showPassword));
        const multipleFields = inputs.length > 1;
        toggle.setAttribute('aria-label', showPassword
            ? (multipleFields ? 'Sembunyikan kata sandi baru dan konfirmasi' : 'Sembunyikan kata sandi')
            : (multipleFields ? 'Tampilkan kata sandi baru dan konfirmasi' : 'Tampilkan kata sandi'));
        toggle.classList.toggle('is-visible', showPassword);
        inputs[0].focus();
    });
});

document.querySelectorAll('[data-login-password]').forEach((password) => {
    password.addEventListener('keydown', (event) => {
        if (event.key !== 'Enter') return;

        event.preventDefault();
        const form = password.closest('[data-login-form]');
        const submit = form ? form.querySelector('[data-login-submit]') : null;
        if (! form || ! submit) return;

        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit(submit);
        } else {
            submit.click();
        }
    });
});

document.querySelectorAll('[data-application-settings-tabs]').forEach((tabList) => {
    const tabs = Array.from(tabList.querySelectorAll('[data-settings-tab]'));
    const panels = Array.from(document.querySelectorAll('[data-settings-panel]'));
    const settingsForm = document.querySelector('[data-settings-form]');
    const settingsFooter = document.querySelector('[data-settings-form-footer]');
    const activeInput = document.querySelector('[data-settings-active-input]');
    const validTabs = tabs.map((tab) => tab.dataset.settingsTab);

    function tabFromHash() {
        const hash = window.location.hash.slice(1);
        if (validTabs.includes(hash)) return hash;
        if (hash === 'add-user' || hash.startsWith('edit-user-')) return 'users';

        return activeInput && validTabs.includes(activeInput.value) ? activeInput.value : 'users';
    }

    function activateTab(activeTab) {
        panels.forEach((panel) => {
            panel.hidden = panel.dataset.settingsPanel !== activeTab;
        });
        tabs.forEach((tab) => {
            const active = tab.dataset.settingsTab === activeTab;
            tab.classList.toggle('active', active);
            tab.setAttribute('aria-selected', String(active));
        });
        if (settingsFooter) settingsFooter.hidden = activeTab === 'users';
        if (activeInput) activeInput.value = activeTab === 'users' ? 'identity' : activeTab;
    }

    if (settingsForm) settingsForm.classList.add('is-enhanced');
    activateTab(tabFromHash());
    window.addEventListener('hashchange', () => activateTab(tabFromHash()));
});

document.querySelectorAll('[data-ajax-user-form]').forEach((form) => {
    const defaultPasswordToggle = form.querySelector('[data-default-password-toggle]');
    const manualPasswordField = form.querySelector('[data-manual-password-field]');
    const passwordInput = form.querySelector('[name="password"]');
    const submitButton = form.querySelector('[data-ajax-user-submit]');
    const errorSummary = form.querySelector('[data-ajax-form-errors]');

    function syncPasswordChoice() {
        const useDefaultPassword = Boolean(defaultPasswordToggle?.checked);
        if (manualPasswordField) manualPasswordField.hidden = useDefaultPassword;
        if (passwordInput) {
            passwordInput.disabled = useDefaultPassword;
            passwordInput.required = ! useDefaultPassword;
            if (useDefaultPassword) passwordInput.value = '';
        }
    }

    function clearErrors() {
        form.querySelectorAll('[data-error-for]').forEach((error) => {
            error.hidden = true;
            error.textContent = '';
        });
        form.querySelectorAll('[aria-invalid="true"]').forEach((input) => input.removeAttribute('aria-invalid'));
        if (errorSummary) {
            errorSummary.hidden = true;
            const list = errorSummary.querySelector('ul');
            if (list) list.replaceChildren();
        }
    }

    function showErrors(errors) {
        const messages = Object.entries(errors).flatMap(([field, fieldMessages]) => {
            const normalizedMessages = Array.isArray(fieldMessages) ? fieldMessages : [fieldMessages];
            const fieldError = form.querySelector(`[data-error-for="${field}"]`);
            const input = form.elements.namedItem(field);
            if (fieldError && normalizedMessages[0]) {
                fieldError.textContent = normalizedMessages[0];
                fieldError.hidden = false;
            }
            if (input instanceof HTMLElement) input.setAttribute('aria-invalid', 'true');

            return normalizedMessages;
        });

        if (errorSummary) {
            const list = errorSummary.querySelector('ul');
            messages.forEach((message) => {
                const item = document.createElement('li');
                item.textContent = message;
                list?.appendChild(item);
            });
            errorSummary.hidden = false;
        }

        form.querySelector('[aria-invalid="true"]')?.focus();
    }

    defaultPasswordToggle?.addEventListener('change', () => {
        clearErrors();
        syncPasswordChoice();
    });
    syncPasswordChoice();

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearErrors();
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.setAttribute('aria-busy', 'true');
            submitButton.textContent = 'Memeriksa…';
        }

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const payload = await response.json().catch(() => ({}));

            if (response.status === 422) {
                showErrors(payload.errors || {form: ['Periksa kembali data pengguna.']});
                return;
            }
            if (! response.ok) {
                showErrors({form: [payload.message || 'Pengguna belum dapat dibuat. Silakan coba lagi.']});
                return;
            }

            window.location.assign(payload.redirect_url || `${window.location.pathname}#users`);
        } catch (error) {
            showErrors({form: ['Koneksi bermasalah. Silakan periksa jaringan lalu coba lagi.']});
        } finally {
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.removeAttribute('aria-busy');
                submitButton.textContent = 'Buat pengguna';
            }
        }
    });
});

document.querySelectorAll('[data-rupiah-input]').forEach((input) => {
    function formatRupiah() {
        const currentValue = input.value;
        const cursorPosition = input.selectionStart ?? currentValue.length;
        const isNegative = input.hasAttribute('data-rupiah-signed') && currentValue.charAt(0) === '-';
        const digitsBeforeCursor = currentValue.slice(0, cursorPosition).replace(/\D/g, '').length;
        const digits = currentValue.replace(/\D/g, '').replace(/^0+(?=\d)/, '');
        const unsignedFormatted = digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        const formatted = isNegative ? `-${unsignedFormatted}` : unsignedFormatted;

        input.value = formatted;

        if (document.activeElement !== input || typeof input.setSelectionRange !== 'function') return;

        let nextCursor = digitsBeforeCursor === 0 ? (isNegative ? Math.min(cursorPosition, 1) : 0) : formatted.length;
        if (digitsBeforeCursor > 0 && digitsBeforeCursor < digits.length) {
            let seenDigits = 0;
            for (let index = 0; index < formatted.length; index += 1) {
                if (/\d/.test(formatted[index])) seenDigits += 1;
                if (seenDigits === digitsBeforeCursor) {
                    nextCursor = index + 1;
                    break;
                }
            }
        }
        input.setSelectionRange(nextCursor, nextCursor);
    }

    input.addEventListener('input', formatRupiah);
    input.closest('.field')?.querySelectorAll('[data-rupiah-preset]').forEach((preset) => {
        preset.addEventListener('click', () => {
            input.value = preset.dataset.rupiahPreset || '';
            formatRupiah();
            input.focus();
        });
    });
    formatRupiah();
});

document.querySelectorAll('[data-month-picker-button]').forEach((button) => {
    const control = button.closest('.month-picker-control');
    const input = control?.querySelector('[data-month-picker-input]');
    const panel = control?.querySelector('[data-month-picker-panel]');
    const yearSelect = control?.querySelector('[data-month-picker-year]');
    const monthGrid = control?.querySelector('[data-month-picker-grid]');
    const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    if (! input || ! panel || ! yearSelect || ! monthGrid) return;

    for (let year = 2100; year >= 1900; year -= 1) {
        const option = document.createElement('option');
        option.value = String(year);
        option.textContent = String(year);
        yearSelect.appendChild(option);
    }

    function selectedParts() {
        const match = input.value.match(/^(\d{4})-(\d{2})$/);
        return match ? {year: Number(match[1]), month: Number(match[2])} : {year: new Date().getFullYear(), month: new Date().getMonth() + 1};
    }

    function renderMonths() {
        const selected = selectedParts();
        const displayedYear = Number(yearSelect.value || selected.year);
        monthGrid.replaceChildren();
        monthNames.forEach((name, index) => {
            const month = index + 1;
            const monthButton = document.createElement('button');
            monthButton.type = 'button';
            monthButton.textContent = name;
            monthButton.classList.toggle('is-selected', displayedYear === selected.year && month === selected.month);
            monthButton.setAttribute('aria-label', `${name} ${displayedYear}`);
            monthButton.addEventListener('click', () => {
                input.value = `${displayedYear}-${String(month).padStart(2, '0')}`;
                input.dispatchEvent(new Event('change', {bubbles: true}));
                panel.hidden = true;
                button.setAttribute('aria-expanded', 'false');
                input.focus();
            });
            monthGrid.appendChild(monthButton);
        });
    }

    button.addEventListener('click', () => {
        const willOpen = panel.hidden;
        document.querySelectorAll('[data-month-picker-panel]').forEach((otherPanel) => { otherPanel.hidden = true; });
        document.querySelectorAll('[data-month-picker-button]').forEach((otherButton) => { otherButton.setAttribute('aria-expanded', 'false'); });
        if (! willOpen) return;

        yearSelect.value = String(selectedParts().year);
        renderMonths();
        panel.hidden = false;
        button.setAttribute('aria-expanded', 'true');
        yearSelect.focus();
    });

    yearSelect.addEventListener('change', renderMonths);
    document.addEventListener('click', (event) => {
        if (control.contains(event.target)) return;
        panel.hidden = true;
        button.setAttribute('aria-expanded', 'false');
    });
});

document.querySelectorAll('[data-transaction-form]').forEach((form) => {
    const typeButtons = Array.from(form.querySelectorAll('[data-transaction-type]'));
    const categorySelect = form.querySelector('[data-category-select]');
    const categoryInput = form.querySelector('[data-category-autocomplete]');
    if (! typeButtons.length || (! categorySelect && ! categoryInput)) return;

    const categoryLabel = form.querySelector('[data-category-label]');
    const categoryPlaceholder = categorySelect?.querySelector('[data-category-placeholder]');
    const categorySuggestionList = form.querySelector('[data-category-suggestion-list]');
    const categorySuggestionButtons = Array.from(form.querySelectorAll('[data-category-suggestion]'));
    const typeHelp = form.querySelector('[data-transaction-type-help]');
    const submitButton = form.querySelector('[data-transaction-submit]');
    const rememberedCategories = {IN: '', OUT: ''};
    let currentType = form.dataset.currentType || 'IN';
    rememberedCategories[currentType] = categorySelect?.value || categoryInput?.value || '';

    function closeCategorySuggestions() {
        if (! categorySuggestionList || ! categoryInput) return;
        categorySuggestionList.hidden = true;
        categoryInput.setAttribute('aria-expanded', 'false');
    }

    function showCategorySuggestions() {
        if (! categorySuggestionList || ! categoryInput) return;
        const query = categoryInput.value.trim().toLocaleLowerCase('id');
        let visibleCount = 0;
        categorySuggestionButtons.forEach((button) => {
            const visible = button.dataset.categoryType === currentType
                && (! query || (button.dataset.categoryValue || '').toLocaleLowerCase('id').includes(query));
            button.hidden = ! visible;
            if (visible) visibleCount += 1;
        });
        categorySuggestionList.hidden = visibleCount === 0;
        categoryInput.setAttribute('aria-expanded', String(visibleCount > 0));
    }

    if (categoryInput && categorySuggestionList) categoryInput.removeAttribute('list');

    function activateType(nextType) {
        rememberedCategories[currentType] = categorySelect?.value || categoryInput?.value || '';
        currentType = nextType;
        form.dataset.currentType = currentType;
        form.action = currentType === 'IN' ? form.dataset.storeIn : form.dataset.storeOut;

        typeButtons.forEach((button) => {
            button.setAttribute('aria-pressed', String(button.dataset.transactionType === currentType));
        });

        categorySelect?.querySelectorAll('[data-category-type]').forEach((option) => {
            const matchesType = option.dataset.categoryType === currentType;
            option.hidden = ! matchesType;
            option.disabled = ! matchesType || option.dataset.categoryAvailable === 'false';
        });

        if (categoryLabel) categoryLabel.textContent = currentType === 'IN' ? 'Kategori pemasukan' : 'Kategori pengeluaran';
        if (categoryPlaceholder) categoryPlaceholder.textContent = 'Pilih kategori';
        if (typeHelp) typeHelp.textContent = currentType === 'IN' ? 'Pemasukan menambah saldo.' : 'Pengeluaran mengurangi saldo.';
        if (submitButton) submitButton.classList.toggle('btn-accent', currentType === 'OUT');
        if (submitButton) submitButton.classList.toggle('btn-primary', currentType === 'IN');

        const rememberedValue = rememberedCategories[currentType];
        if (categorySelect) {
            const rememberedOption = rememberedValue
                ? categorySelect.querySelector(`option[value="${rememberedValue}"]:not([disabled])`)
                : null;
            categorySelect.value = rememberedOption ? rememberedValue : '';
        }
        if (categoryInput) {
            categoryInput.value = rememberedValue;
            categoryInput.placeholder = currentType === 'IN' ? 'Ketik kategori pemasukan' : 'Ketik kategori pengeluaran';
            if (! categorySuggestionList) categoryInput.setAttribute('list', `category-suggestions-${currentType.toLowerCase()}`);
            if (document.activeElement === categoryInput) showCategorySuggestions();
        }
    }

    categorySelect?.addEventListener('change', () => {
        rememberedCategories[currentType] = categorySelect.value;
    });
    categoryInput?.addEventListener('input', () => {
        rememberedCategories[currentType] = categoryInput.value;
        showCategorySuggestions();
    });
    categoryInput?.addEventListener('focus', showCategorySuggestions);
    categoryInput?.addEventListener('click', showCategorySuggestions);
    categoryInput?.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closeCategorySuggestions();
        if (event.key === 'ArrowDown') {
            const firstSuggestion = categorySuggestionButtons.find((button) => ! button.hidden);
            if (firstSuggestion) {
                event.preventDefault();
                firstSuggestion.focus();
            }
        }
    });
    categorySuggestionButtons.forEach((button) => {
        button.addEventListener('click', () => {
            if (! categoryInput) return;
            categoryInput.value = button.dataset.categoryValue || '';
            rememberedCategories[currentType] = categoryInput.value;
            categoryInput.focus();
            closeCategorySuggestions();
        });
    });
    document.addEventListener('pointerdown', (event) => {
        if (! event.target.closest('.category-autocomplete')) closeCategorySuggestions();
    });
    typeButtons.forEach((button) => {
        button.addEventListener('click', () => activateType(button.dataset.transactionType));
    });
    activateType(currentType);
});
