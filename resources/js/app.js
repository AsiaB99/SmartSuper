import './bootstrap';
import { initListaProductosSearch, initUserLocationCapture } from './listas-productos';
import { initDespensaStock } from './despensas-stock';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();
initListaProductosSearch();
initUserLocationCapture();
initDespensaStock();
