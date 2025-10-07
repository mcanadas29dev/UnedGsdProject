/**
 * userSearch.js
 * ----------------------------------------------------------
 * Búsqueda dinámica (AJAX) de usuarios por email.
 * Inspirado en orderSearch.js del módulo de pedidos.
 * 
 * Permite filtrar usuarios sin recargar la página.
 * Evita peticiones innecesarias mediante "debounce" (300ms).
 * ----------------------------------------------------------
 */

export function initUserSearch() {
    const searchInput = document.getElementById('userSearch');
    const resultsContainer = document.getElementById('userResults');

    if (!searchInput || !resultsContainer) return;

    let timeout = null;

    searchInput.addEventListener('input', (event) => {
        const query = event.target.value.trim();
        clearTimeout(timeout);

        timeout = setTimeout(async () => {
            //const url = new URL(window.location.origin + '/admin/users');
            const url = new URL(window.location.origin + '/user');
            
            if (query.length > 0) {
                url.searchParams.set('find_user', query);
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
                console.error("Error al buscar usuarios:", error);
                resultsContainer.innerHTML = `
                    <div class="alert alert-warning mt-3 text-center">
                        No se pudieron cargar los usuarios. Intenta de nuevo.
                    </div>
                `;
            }
        }, 300);
    });
}

document.addEventListener('DOMContentLoaded', initUserSearch);
