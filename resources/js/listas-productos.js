const escapeHtml = (value) => value
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#39;');

export const initListaProductosSearch = () => {
    const form = document.getElementById('buscador-catalogo-lista');
    const input = document.getElementById('busqueda-productos-lista');
    const suggestionsBox = document.getElementById('sugerencias-productos-lista');
    const catalogoWrapper = document.querySelector('[data-catalogo-wrapper]');
    const listaActualWrapper = document.querySelector('[data-lista-productos-actual]');
    const resumenWrapper = document.querySelector('[data-lista-resumen]');
    const feedback = document.querySelector('[data-catalogo-feedback]');

    if (!form || !input || !suggestionsBox || !catalogoWrapper) {
        return;
    }

    let debounceTimer;
    let activeController;
    let suggestionController;
    let updateController;
    let currentSuggestions = [];
    let activeIndex = -1;

    const buildCatalogUrl = (query = input.value, page = null) => {
        const endpoint = new URL(form.dataset.catalogoUrl, window.location.origin);
        const normalizedQuery = query.trim();

        if (normalizedQuery !== '') {
            endpoint.searchParams.set('q', normalizedQuery);
        }

        if (page) {
            endpoint.searchParams.set('page', page);
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

    const showFeedback = (message) => {
        if (!feedback || !message) {
            return;
        }

        feedback.textContent = message;
        feedback.classList.remove('hidden');
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

    const cargarCatalogo = async (url) => {
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

        if (typeof data.catalogo === 'string') {
            catalogoWrapper.innerHTML = data.catalogo;
        }
    };

    const applySearch = (query) => {
        const url = buildCatalogUrl(query);
        window.history.replaceState({}, '', url);
        cargarCatalogo(url).catch(() => {});
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

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        closeSuggestions();
        applySearch(input.value);
    });

    input.addEventListener('input', () => {
        const query = input.value;
        const normalizedQuery = query.trim();

        if (normalizedQuery.length < 2) {
            closeSuggestions();
            clearTimeout(debounceTimer);
            applySearch(query);
            return;
        }

        clearTimeout(debounceTimer);
        debounceTimer = window.setTimeout(async () => {
            const suggestions = await fetchSuggestions(normalizedQuery).catch(() => []);

            if (input.value.trim() !== normalizedQuery) {
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

    catalogoWrapper.addEventListener('click', (event) => {
        const link = event.target.closest('[data-catalogo-paginacion] a');

        if (!link) {
            return;
        }

        event.preventDefault();
        const url = new URL(link.href);
        input.value = url.searchParams.get('q') ?? '';
        window.history.replaceState({}, '', url.toString());
        closeSuggestions();
        cargarCatalogo(url.toString()).catch(() => {});
    });

    catalogoWrapper.addEventListener('submit', async (event) => {
        const submitForm = event.target.closest('form');

        if (!submitForm || !submitForm.querySelector('[data-catalogo-add]')) {
            return;
        }

        event.preventDefault();

        const submitButton = submitForm.querySelector('[data-catalogo-add]');
        submitButton?.setAttribute('disabled', 'disabled');

        const response = await fetch(submitForm.action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            body: new FormData(submitForm),
        }).catch(() => null);

        submitButton?.removeAttribute('disabled');

        if (!response || !response.ok) {
            return;
        }

        const data = await response.json();

        if (typeof data.catalogo === 'string') {
            catalogoWrapper.innerHTML = data.catalogo;
        }

        if (listaActualWrapper && typeof data.listaHtml === 'string') {
            listaActualWrapper.innerHTML = data.listaHtml;
        }

        if (resumenWrapper && typeof data.resumenHtml === 'string') {
            resumenWrapper.innerHTML = data.resumenHtml;
            initUserLocationCapture();
        }

        showFeedback(data.status ?? '');
        closeSuggestions();
    });

    listaActualWrapper?.addEventListener('change', (event) => {
        const inputCantidad = event.target.closest('[data-lista-cantidad-input]');

        if (!(inputCantidad instanceof HTMLInputElement)) {
            return;
        }

        inputCantidad.form?.requestSubmit();
    });

    listaActualWrapper?.addEventListener('submit', async (event) => {
        const submitForm = event.target.closest('form[data-lista-producto-update]');

        if (!submitForm) {
            return;
        }

        event.preventDefault();
        updateController?.abort();
        updateController = new AbortController();

        const inputCantidad = submitForm.querySelector('[data-lista-cantidad-input]');
        const payload = new FormData(submitForm);
        inputCantidad?.setAttribute('disabled', 'disabled');

        const response = await fetch(submitForm.action, {
            method: 'POST',
            signal: updateController.signal,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            body: payload,
        }).catch(() => null);

        inputCantidad?.removeAttribute('disabled');

        if (!response || !response.ok) {
            return;
        }

        const data = await response.json();

        if (typeof data.listaHtml === 'string') {
            listaActualWrapper.innerHTML = data.listaHtml;
        }

        if (resumenWrapper && typeof data.resumenHtml === 'string') {
            resumenWrapper.innerHTML = data.resumenHtml;
            initUserLocationCapture();
        }

        showFeedback(data.status ?? '');
    });

    document.addEventListener('click', (event) => {
        if (!form.contains(event.target)) {
            closeSuggestions();
        }
    });
};

export const initUserLocationCapture = () => {
    const form = document.getElementById('form-ubicacion-usuario');
    const button = document.getElementById('btn-usar-ubicacion');
    const latitudInput = document.getElementById('ubicacion-latitud');
    const longitudInput = document.getElementById('ubicacion-longitud');

    if (!form || !button || !latitudInput || !longitudInput) {
        return;
    }

    button.addEventListener('click', () => {
        if (!navigator.geolocation) {
            return;
        }

        button.setAttribute('disabled', 'disabled');
        button.textContent = button.dataset.loadingText ?? '...';

        navigator.geolocation.getCurrentPosition(
            (position) => {
                latitudInput.value = String(position.coords.latitude);
                longitudInput.value = String(position.coords.longitude);
                form.submit();
            },
            () => {
                button.removeAttribute('disabled');
                button.textContent = button.dataset.errorText ?? 'Error';
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0,
            }
        );
    });
};
