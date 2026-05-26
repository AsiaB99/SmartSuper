export const initAdminSupermercadosIndex = () => {
    const targetId = 'admin-supermercados-tab';

    const refreshSupermercadosTab = async (url) => {
        try {
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html',
                },
            });

            if (!response.ok) {
                window.location.href = url;
                return;
            }

            const html = await response.text();
            const container = document.createElement('div');
            container.innerHTML = html;

            const nextTab = container.querySelector(`#${targetId}`);
            const currentTab = document.getElementById(targetId);

            if (!nextTab || !currentTab) {
                window.location.href = url;
                return;
            }

            currentTab.replaceWith(nextTab);
            window.history.replaceState({}, '', url);
            bindEvents();
        } catch (_error) {
            window.location.href = url;
        }
    };

    const bindEvents = () => {
        const tab = document.getElementById(targetId);

        if (!tab) {
            return;
        }

        const form = document.getElementById('admin-supermercados-search-form');

        if (form) {
            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                const url = `${form.action}?${new URLSearchParams(new FormData(form)).toString()}`;
                await refreshSupermercadosTab(url);
            });
        }

        tab.querySelectorAll('.pagination a').forEach((link) => {
            link.addEventListener('click', async (event) => {
                event.preventDefault();
                await refreshSupermercadosTab(link.href);
            });
        });
    };

    bindEvents();
};
