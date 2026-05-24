const escapeHtml = (value) => value
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#39;');

export const initDespensaStock = () => {
    const form = document.getElementById('buscador-stock-despensa');
    const input = document.getElementById('busqueda-stock-despensa');
    const suggestionsBox = document.getElementById('sugerencias-stock-despensa');
    const stockWrapper = document.querySelector('[data-stock-wrapper]');
    const filterChip = document.getElementById('stock-filter-chip');
    const manualSearchInput = document.getElementById('busqueda-producto-manual');
    const manualProductInput = document.getElementById('id_producto_manual');
    const manualForm = document.querySelector('[data-stock-manual-form]');
    const manualFeedback = document.querySelector('[data-stock-manual-feedback]');
    const totalProductosEl = document.querySelector('[data-stock-total-productos]');
    const productosBajosEl = document.querySelector('[data-stock-productos-bajos]');
    const unidadesTotalesEl = document.querySelector('[data-stock-unidades-totales]');

    if (!form || !input || !suggestionsBox || !stockWrapper) {
        return;
    }

    let debounceTimer;
    let activeController;
    let suggestionController;
    let manualSuggestionController;
    let manualDebounceTimer;
    let currentSuggestions = [];
    let activeIndex = -1;
    let lowThreshold = Number(form.dataset.lowThreshold ?? '1');
    const recommendedPrefix = `smartsuper:despensa:${window.location.pathname}:recommended:`;
    const lastListKey = `smartsuper:despensa:${window.location.pathname}:last-lista`;
    const readRecommended = (productId) => Number(window.localStorage.getItem(`${recommendedPrefix}${productId}`) ?? '0');
    const writeRecommended = (productId, value) => window.localStorage.setItem(`${recommendedPrefix}${productId}`, String(value));
    const readLastList = () => window.localStorage.getItem(lastListKey) ?? '';
    const writeLastList = (value) => window.localStorage.setItem(lastListKey, value);
    const buildUrl = (query = input.value.trim()) => {
        const endpoint = new URL(form.dataset.stockUrl, window.location.origin);
        endpoint.searchParams.set('low_stock_threshold', String(lowThreshold));
        if (query !== '') {
            endpoint.searchParams.set('q', query);
        }
        return endpoint.toString();
    };

    const closeSuggestions = () => {
        suggestionsBox.classList.add('hidden');
        suggestionsBox.innerHTML = '';
        input.setAttribute('aria-expanded', 'false');
        currentSuggestions = [];
        activeIndex = -1;
    };

    const setActiveSuggestion = (index) => {
        activeIndex = index;
        Array.from(suggestionsBox.querySelectorAll('[data-suggestion-index]')).forEach((element, itemIndex) => {
            element.classList.toggle('bg-brand-50', itemIndex === activeIndex);
        });
    };

    const renderSuggestions = (suggestions) => {
        currentSuggestions = suggestions;
        if (currentSuggestions.length === 0) {
            closeSuggestions();
            return;
        }

        suggestionsBox.innerHTML = `
            <ul class="py-2" role="listbox" aria-label="${document.documentElement.dataset.searchSuggestionsLabel ?? 'Search suggestions'}">
                ${currentSuggestions.map((suggestion, index) => `
                    <li>
                        <button
                            type="button"
                            class="flex w-full items-center px-4 py-3 text-left transition hover:bg-brand-50"
                            data-suggestion-index="${index}"
                        >
                            <span class="truncate text-sm font-medium text-ink-900">${escapeHtml(suggestion)}</span>
                        </button>
                    </li>
                `).join('')}
            </ul>
        `;

        suggestionsBox.classList.remove('hidden');
        input.setAttribute('aria-expanded', 'true');
        setActiveSuggestion(-1);
    };

    const updateFilterChip = (query) => {
        if (!filterChip) {
            return;
        }

        if (query === '') {
            filterChip.classList.add('hidden');
            filterChip.textContent = '';
            return;
        }

        filterChip.classList.remove('hidden');
        filterChip.textContent = `${form.dataset.filterLabel ?? 'Filtrado por:'} ${query}`;
    };

    const cargarStock = async (url) => {
        activeController?.abort();
        activeController = new AbortController();

        const response = await fetch(url, {
            signal: activeController.signal,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        });

        if (!response.ok) {
            return;
        }

        const data = await response.json();
        if (typeof data.productos === 'string') {
            stockWrapper.innerHTML = data.productos;
        }

        updateFilterChip((data.query ?? '').trim());
        applyRecommendedAlerts();
    };

    const showManualFeedback = (message) => {
        if (!(manualFeedback instanceof HTMLElement) || !message) {
            return;
        }

        manualFeedback.textContent = message;
        manualFeedback.classList.remove('hidden');
    };

    const applyRecommendedAlerts = () => {
        const template = form.dataset.recommendedAlertTemplate ?? 'Stock bajo recomendado (mín: __MIN__)';
        stockWrapper.querySelectorAll('[data-product-row]').forEach((row) => {
            const productId = Number(row.dataset.productId ?? '0');
            const stock = Number(row.dataset.stockValue ?? '0');
            const minRecommended = readRecommended(productId);
            const alert = row.querySelector('[data-recommended-alert]');

            if (!(alert instanceof HTMLElement)) {
                return;
            }

            if (productId <= 0 || minRecommended <= 0 || stock >= minRecommended) {
                alert.classList.add('hidden');
                alert.textContent = '';
                return;
            }

            alert.classList.remove('hidden');
            alert.textContent = template.replace('__MIN__', String(minRecommended));
        });
    };

    const applySearch = (query) => {
        const normalizedQuery = query.trim();
        input.value = normalizedQuery;
        const url = buildUrl(normalizedQuery);
        window.history.replaceState({}, '', url);
        cargarStock(url).catch(() => {});
    };

    const fetchSuggestions = async (query) => {
        suggestionController?.abort();
        suggestionController = new AbortController();

        const endpoint = new URL(form.dataset.sugerenciasUrl, window.location.origin);
        endpoint.searchParams.set('q', query);

        const response = await fetch(endpoint.toString(), {
            signal: suggestionController.signal,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        });

        if (!response.ok) {
            return [];
        }

        return response.json();
    };

    const fetchManualSuggestions = async (query) => {
        manualSuggestionController?.abort();
        manualSuggestionController = new AbortController();

        const endpoint = new URL(form.dataset.catalogoSugerenciasUrl, window.location.origin);
        endpoint.searchParams.set('q', query);

        const response = await fetch(endpoint.toString(), {
            signal: manualSuggestionController.signal,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
        });

        if (!response.ok) {
            return [];
        }

        return response.json();
    };

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        closeSuggestions();
        applySearch(input.value);
    });

    input.addEventListener('input', () => {
        const query = input.value.trim();

        if (query.length < 2) {
            closeSuggestions();
            clearTimeout(debounceTimer);
            applySearch(query);
            return;
        }

        clearTimeout(debounceTimer);
        debounceTimer = window.setTimeout(async () => {
            const suggestions = await fetchSuggestions(query).catch(() => []);
            if (input.value.trim() !== query) {
                return;
            }

            renderSuggestions(Array.isArray(suggestions) ? suggestions : []);
            applySearch(query);
        }, 180);
    });

    input.addEventListener('keydown', (event) => {
        if (currentSuggestions.length === 0) {
            if (event.key === 'Enter') {
                event.preventDefault();
                closeSuggestions();
                applySearch(input.value);
            }
            return;
        }

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            setActiveSuggestion((activeIndex + 1) % currentSuggestions.length);
            return;
        }

        if (event.key === 'ArrowUp') {
            event.preventDefault();
            setActiveSuggestion(activeIndex <= 0 ? currentSuggestions.length - 1 : activeIndex - 1);
            return;
        }

        if (event.key === 'Escape') {
            closeSuggestions();
            return;
        }

        if (event.key === 'Enter') {
            event.preventDefault();
            if (activeIndex >= 0) {
                input.value = currentSuggestions[activeIndex];
            }
            closeSuggestions();
            applySearch(input.value);
        }
    });

    suggestionsBox.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-suggestion-index]');
        if (!trigger) {
            return;
        }

        const suggestion = currentSuggestions[Number(trigger.dataset.suggestionIndex)];
        if (!suggestion) {
            return;
        }

        closeSuggestions();
        applySearch(suggestion);
    });

    document.addEventListener('click', (event) => {
        if (!form.contains(event.target)) {
            closeSuggestions();
        }
    });

    manualSearchInput?.addEventListener('input', () => {
        if (!(manualSearchInput instanceof HTMLInputElement) || !(manualProductInput instanceof HTMLSelectElement)) {
            return;
        }

        const query = manualSearchInput.value.trim();

        if (query.length < 2) {
            clearTimeout(manualDebounceTimer);
            manualProductInput.innerHTML = `<option value="">${escapeHtml(manualProductInput.dataset.placeholder ?? 'Selecciona producto')}</option>`;
            return;
        }

        clearTimeout(manualDebounceTimer);
        manualDebounceTimer = window.setTimeout(async () => {
            const suggestions = await fetchManualSuggestions(query).catch(() => []);

            if (manualSearchInput.value.trim() !== query) {
                return;
            }

            const options = Array.isArray(suggestions) ? suggestions : [];
            const placeholder = manualProductInput.dataset.placeholder ?? 'Selecciona producto';

            manualProductInput.innerHTML = `<option value="">${escapeHtml(placeholder)}</option>`;

            options.forEach((suggestion) => {
                const option = document.createElement('option');
                option.value = String(suggestion.id ?? '');
                option.textContent = suggestion.descripcion?.trim()
                    ? `${suggestion.nombre ?? ''} · ${suggestion.descripcion}`
                    : `${suggestion.nombre ?? ''}`;
                manualProductInput.append(option);
            });

            if (options.length > 0) {
                manualProductInput.selectedIndex = 1;
            }
        }, 180);
    });

    manualForm?.addEventListener('submit', async (event) => {
        if (!(manualForm instanceof HTMLFormElement)) {
            return;
        }

        event.preventDefault();

        const submitButton = manualForm.querySelector('button[type="submit"]');
        submitButton?.setAttribute('disabled', 'disabled');

        const response = await fetch(manualForm.action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            body: new FormData(manualForm),
        }).catch(() => null);

        submitButton?.removeAttribute('disabled');

        if (!response || !response.ok) {
            return;
        }

        const data = await response.json();

        if (typeof data.productos === 'string') {
            stockWrapper.innerHTML = data.productos;
            applyRecommendedAlerts();
        }

        if (data.stats && totalProductosEl && productosBajosEl && unidadesTotalesEl) {
            totalProductosEl.textContent = String(data.stats.totalProductos ?? totalProductosEl.textContent ?? '');
            productosBajosEl.textContent = String(data.stats.productosBajos ?? productosBajosEl.textContent ?? '');
            unidadesTotalesEl.textContent = String(data.stats.unidadesTotales ?? unidadesTotalesEl.textContent ?? '');
        }

        showManualFeedback(data.status ?? '');
        manualForm.reset();

        if (manualProductInput instanceof HTMLSelectElement) {
            manualProductInput.innerHTML = `<option value="">${escapeHtml(manualProductInput.dataset.placeholder ?? 'Selecciona producto')}</option>`;
        }
    });

    const deleteModal = document.getElementById('stock-delete-modal');
    const deleteForm = document.getElementById('stock-delete-modal-form');
    const deleteText = document.getElementById('stock-delete-modal-text');
    const editModal = document.getElementById('stock-edit-modal');
    const editForm = document.getElementById('stock-edit-modal-form');
    const editProductText = document.getElementById('stock-edit-modal-product');
    const editStockInput = document.getElementById('stock-edit-modal-input');
    const editMinInput = document.getElementById('stock-edit-modal-min-input');
    const addToListModal = document.getElementById('stock-add-to-list-modal');
    const addToListForm = document.getElementById('stock-add-to-list-modal-form');
    const addToListProductText = document.getElementById('stock-add-to-list-modal-product');
    const addToListProductIdInput = document.getElementById('stock-add-to-list-modal-product-id');
    const addToListListaSelect = document.getElementById('stock-add-to-list-modal-lista');
    const addToListQuantityInput = document.getElementById('stock-add-to-list-modal-quantity');
    let editingProductId = 0;

    stockWrapper.addEventListener('click', (event) => {
        const deleteButton = event.target.closest('[data-stock-delete-open]');
        if (deleteButton && deleteModal && deleteForm && deleteText) {
            deleteForm.setAttribute('action', deleteButton.dataset.action ?? '');
            const productName = (deleteButton.dataset.productName ?? '').trim();
            const template = form.dataset.deleteTemplate ?? '__NAME__';
            deleteText.textContent = template.replace('__NAME__', productName);
            deleteModal.showModal();
            return;
        }

        const editButton = event.target.closest('[data-stock-edit-open]');
        if (editButton && editModal && editForm && editProductText && editStockInput && editMinInput) {
            editingProductId = Number(editButton.dataset.productId ?? '0');
            editForm.setAttribute('action', editButton.dataset.action ?? '');
            editProductText.textContent = (editButton.dataset.productName ?? '').trim();
            editStockInput.value = String(Number(editButton.dataset.stock ?? '0'));
            editMinInput.value = String(Math.max(1, readRecommended(editingProductId) || lowThreshold));
            editModal.showModal();
            return;
        }

        const addToListButton = event.target.closest('[data-stock-add-to-list-open]');
        if (
            addToListButton
            && addToListModal
            && addToListProductText
            && addToListProductIdInput instanceof HTMLInputElement
            && addToListListaSelect instanceof HTMLSelectElement
            && addToListQuantityInput instanceof HTMLInputElement
        ) {
            addToListProductText.textContent = (addToListButton.dataset.productName ?? '').trim();
            addToListProductIdInput.value = String(Number(addToListButton.dataset.productId ?? '0'));
            const lastListId = readLastList();
            addToListListaSelect.value = Array.from(addToListListaSelect.options).some((option) => option.value === lastListId)
                ? lastListId
                : '';
            addToListQuantityInput.value = '1';
            addToListModal.showModal();
        }
    });

    stockWrapper.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-inline-low-threshold-apply]');
        if (!trigger) {
            return;
        }

        const wrapper = trigger.closest('[data-inline-low-threshold-row]');
        const inputThreshold = wrapper?.querySelector('[data-inline-low-threshold]');
        if (!(inputThreshold instanceof HTMLInputElement)) {
            return;
        }

        lowThreshold = Math.max(1, Math.min(99, Number(inputThreshold.value || '1')));
        form.dataset.lowThreshold = String(lowThreshold);
        applySearch(input.value);
    });

    document.querySelectorAll('[data-modal-cancel]').forEach((button) => {
        button.addEventListener('click', () => {
            button.closest('dialog')?.close();
        });
    });

    editForm?.addEventListener('submit', () => {
        if (editingProductId > 0 && editMinInput instanceof HTMLInputElement) {
            const minValue = Math.max(1, Math.min(99, Number(editMinInput.value || '1')));
            writeRecommended(editingProductId, minValue);
        }
    });

    addToListForm?.addEventListener('submit', (event) => {
        if (!(addToListListaSelect instanceof HTMLSelectElement)) {
            return;
        }

        const listaId = addToListListaSelect.value.trim();
        if (listaId === '') {
            event.preventDefault();
            addToListListaSelect.focus();
            return;
        }

        writeLastList(listaId);
        const actionTemplate = form.dataset.addToListUrlTemplate ?? '';
        addToListForm.setAttribute('action', actionTemplate.replace('__LISTA__', listaId));
    });

    if (manualProductInput instanceof HTMLSelectElement) {
        manualProductInput.dataset.placeholder = manualProductInput.dataset.placeholder || manualProductInput.options[0]?.textContent || 'Selecciona producto';
    }
    applyRecommendedAlerts();
};
