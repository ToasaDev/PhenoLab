// PhenoLab - Laravel Vite Entry Point
// Import styles
import 'bootstrap/dist/css/bootstrap.min.css';
import '@fortawesome/fontawesome-free/css/all.min.css';
import 'leaflet/dist/leaflet.css';
import '../css/app.css';
import '../css/form-fixes.css';
import '../css/navbar-improvements.css';
import '../css/photo-gallery.css';

// Import libraries and expose globally for the monolithic app
import { createApp } from 'vue';
import axios from 'axios';
import L from 'leaflet';
import { Chart, registerables } from 'chart.js';
import * as bootstrap from 'bootstrap';

// Register Chart.js components
Chart.register(...registerables);

// Expose globally (needed by the inline Vue app)
window.Vue = { createApp };
window.axios = axios;
window.L = L;
window.Chart = Chart;
window.bootstrap = bootstrap;

// Fix Leaflet default icon paths in Vite bundled build
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

// CSRF token setup for axios
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
if (csrfToken) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
}
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.withCredentials = true;

async function loadLegacyScript(source) {
    return new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = source;
        script.async = false;
        script.onload = resolve;
        script.onerror = () => reject(new Error(`Failed to load legacy script: ${source}`));
        document.body.appendChild(script);
    });
}

async function bootstrapLegacyApp() {
    if (window.__phenolabLegacyBootstrapped) {
        return;
    }

    window.__phenolabLegacyBootstrapped = true;

    const legacyScripts = [
        '/js/services/api.js',
        '/js/utils/errorHandler.js',
        '/js/utils/formHelpers.js',
        '/js/phenolab-app.js',
    ];

    for (const source of legacyScripts) {
        await loadLegacyScript(source);
    }
}

bootstrapLegacyApp().catch((error) => {
    console.error('PhenoLab Laravel - Legacy app bootstrap failed', error);
});

console.log('PhenoLab Laravel - Libraries loaded');
