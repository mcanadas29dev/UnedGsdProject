//import { initSidebar } from './modules/sidebar.js';
import { initProductSearch } from './modules/product_search.js';
import { initOrderSearch } from './modules/order_search.js';
import { initUserSearch } from './modules/user_search.js';

document.addEventListener('DOMContentLoaded', () => {
    //initSidebar();
    initProductSearch();
    initOrderSearch();
    initUserSearch();
});
