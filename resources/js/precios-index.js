export const initPreciosIndex = () => {
    const form = document.getElementById('buscador-precios');
    const input = document.getElementById('busqueda-precios');
    const productosWrapper = document.querySelector('[data-precios-productos]');
    const comparadorWrapper = document.querySelector('[data-precios-comparador]');

    if (!form || !input || !productosWrapper || !comparadorWrapper) {
        return;
    }

    let debounceTimer;
    let activeController;
    let selectedProductId = Number(document.querySelector('[data-precio-producto][data-producto-id].border-brand-300')?.dataset.productoId ?? 0) || null;
    let currentPage = Number(new URL(window.location.href).searchParams.get('page') ?? 1) || 1;

    const buildUrl = (query = input.value, productoId = selectedProductId, page = currentPage) => {
        const endpoint = new URL(form.dataset.preciosUrl, window.location.origin);
        const normalizedQuery = query.trim();

        if (normalizedQuery !== '') {
            endpoint.searchParams.set('busqueda', normalizedQuery);
        }

        if (productoId) {
            endpoint.searchParams.set('producto', String(productoId));
        }

        if (page && page > 1) {
            endpoint.searchParams.set('page', String(page));
        }

        return endpoint.toString();
    };

    const cargarVista = async (url) => {
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

        if (typeof data.productosHtml === 'string') {
            productosWrapper.innerHTML = data.productosHtml;
        }

        if (typeof data.comparadorHtml === 'string') {
            comparadorWrapper.innerHTML = data.comparadorHtml;
        }

        selectedProductId = data.productoId ? Number(data.productoId) : null;
        currentPage = Number(data.page ?? 1) || 1;
        window.history.replaceState({}, '', buildUrl(data.busqueda ?? '', selectedProductId, currentPage));
    };

    const aplicarBusqueda = (query, page = currentPage) => {
        currentPage = page;
        cargarVista(buildUrl(query, selectedProductId, currentPage)).catch(() => {});
    };

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        aplicarBusqueda(input.value);
    });

    input.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = window.setTimeout(() => {
            selectedProductId = null;
            currentPage = 1;
            aplicarBusqueda(input.value, 1);
        }, 180);
    });

    productosWrapper.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-precio-producto]');

        if (!trigger) {
            return;
        }

        event.preventDefault();
        selectedProductId = Number(trigger.dataset.productoId) || null;
        aplicarBusqueda(input.value);
    });

    productosWrapper.addEventListener('click', (event) => {
        const link = event.target.closest('[data-precios-paginacion] a');

        if (!link) {
            return;
        }

        event.preventDefault();
        const url = new URL(link.href);
        const page = Number(url.searchParams.get('page') ?? 1) || 1;
        aplicarBusqueda(input.value, page);
    });
};
