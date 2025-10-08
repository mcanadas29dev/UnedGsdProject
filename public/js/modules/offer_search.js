export function initOfferSearch() {
    const searchInput = document.getElementById('offerSearch');
    const resultsContainer = document.getElementById('searchResults');

    if (!searchInput || !resultsContainer) return;

    let timeout = null;

    searchInput.addEventListener('input', (event) => {
        const query = event.target.value.trim();
        clearTimeout(timeout);

        timeout = setTimeout(async () => {
            const url = new URL(window.location.origin + '/offer/admin');
            if (query.length > 0) {
                url.searchParams.set('q', query);
            }

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
                console.error("Error al buscar ofertas:", error);
                resultsContainer.innerHTML = `
                    <div class="alert alert-warning mt-3 text-center">
                        No se pudieron cargar las ofertas. Intenta de nuevo.
                    </div>
                `;
            }
        }, 300); // debounce 300ms
    });
}
