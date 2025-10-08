export function initOrderSearch() {
    const searchInput = document.getElementById('orderSearch');
    const resultsContainer = document.getElementById('orderResults');

    if (!searchInput || !resultsContainer) return;

    let timeout = null;

    searchInput.addEventListener('input', (event) => {
        const query = event.target.value.trim();
        clearTimeout(timeout);

        timeout = setTimeout(async () => {
              // Detecta el contexto según la URL actual
            let basePath = '/orders';
            const path = window.location.pathname;

            if (path.includes('/admin')) {
                basePath = '/admin/orders';
            } else if (path.includes('/almacen')) {
                basePath = '/almacen/orders';
            }
            /*
            const basePath = window.location.pathname.includes('/admin') 
                ? '/admin/orders' 
                : '/orders';
            */
            const url = new URL(window.location.origin + basePath);
            //const url = new URL(window.location.origin + '/admin/orders');
            if (query.length > 0) {
                url.searchParams.set('find_order', query);
            }

            try {
                const response = await fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

                const html = await response.text();
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const safeContent = doc.body.innerHTML;

                resultsContainer.innerHTML = safeContent;
            } catch (error) {
                console.error("Error al buscar pedidos:", error);
                resultsContainer.innerHTML = `
                    <div class="alert alert-warning mt-3 text-center">
                        No se pudieron cargar los pedidos. Intenta de nuevo.
                    </div>
                `;
            }
        }, 300);
    });
}

// Auto inicializa si se importa directamente
document.addEventListener('DOMContentLoaded', initOrderSearch);
