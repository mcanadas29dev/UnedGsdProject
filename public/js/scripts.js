//import { initSidebar } from './modules/sidebar.js';
import { initProductSearch } from './modules/product_search.js';
import { initOrderSearch } from './modules/order_search.js';
import { initUserSearch } from './modules/user_search.js';
import { initCategorySearch } from './modules/category_search.js';
import { initOfferSearch } from './modules/offer_search.js';
import { initEstadoOrderSearch } from './modules/orderStatus_search.js';

function ServicesInit(){
 //initSidebar();
    initProductSearch();
    initOrderSearch();
    initUserSearch();
    initCategorySearch();
    initOfferSearch();
    initEstadoOrderSearch();
}

document.addEventListener('DOMContentLoaded', () => {
   ServicesInit;
});
