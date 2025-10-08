//import { initSidebar } from './modules/sidebar.js';
import { initProductSearch } from './modules/product_search.js';
import { initOrderSearch } from './modules/order_search.js';
import { initUserSearch } from './modules/user_search.js';
import { initCategorySearch } from './modules/category_search.js';
import { initOfferSearch } from './modules/offer_search.js';


document.addEventListener('DOMContentLoaded', () => {
    //initSidebar();
    initProductSearch();
    initOrderSearch();
    initUserSearch();
    initCategorySearch();
    initOfferSearch();
});
