export function initEstadoOrderSearch() {
    const searchInput = document.getElementById('estadoSearch');
    //console.log(searchInput);
    const resultsContainer = document.getElementById('searchResults');

    if (!searchInput || !resultsContainer) return;

    let timeout = null;

    searchInput.addEventListener('input', (event) => {
        const query = event.target.value.trim();
        clearTimeout(timeout);

        timeout = setTimeout(async () => {
            // Generamos la URL del endpoint
            const url = new URL(window.location.origin + '/order/status');
            if (query.length > 0) {
                url.searchParams.set('find_orderStatus', query);
            }

            // Mostramos spinner mientras se busca
            /*
            resultsContainer.innerHTML = `
                <div class="text-center py-3 text-muted">
                    <div class="spinner-border text-success" role="status"></div>
                </div>
            `; */

            try {
                const response = await fetch(url.toString(), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

                const html = await response.text();
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const safeContent = doc.body.innerHTML;

                resultsContainer.innerHTML = safeContent;
            } catch (error) {
                console.error("Error al buscar estados:", error);
                resultsContainer.innerHTML = `
                    <div class="alert alert-warning mt-3 text-center">
                        No se pudieron cargar los estados. Intenta de nuevo.
                    </div>
                `;
            }
        }, 300); // debounce
    });
}

document.addEventListener('DOMContentLoaded', initEstadoOrderSearch);