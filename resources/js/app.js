import './bootstrap';
import { initListaProductosSearch, initUserLocationCapture } from './listas-productos';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();
initListaProductosSearch();
initUserLocationCapture();
