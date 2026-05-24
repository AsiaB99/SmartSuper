import './bootstrap';
import { initListaProductosSearch, initUserLocationCapture } from './listas-productos';
import { initDespensaStock } from './despensas-stock';
import { initPreciosIndex } from './precios-index';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();
initListaProductosSearch();
initUserLocationCapture();
initDespensaStock();
initPreciosIndex();
