/**
 * Catalog approximate-area map (Leaflet + OpenStreetMap).
 *
 * Bundled locally by Vite as a separate entry (resources/js/catalog-map.js)
 * and loaded ONLY on the public listing detail page, never in the global
 * layout (UI_GUIDELINES section 10).
 *
 * The page only ever passes the rounded approximate point (2 decimals,
 * see ApproximateLocation, max offset 800 m). This script renders an area
 * circle with an explicit "approximate" label and never an exact pin.
 * Coordinates come from data-lat / data-lng attributes, which contain no
 * user-controlled markup (numeric only).
 */
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import './listing-map.css';

function toNumber(value) {
    const parsed = Number.parseFloat(value);
    return Number.isFinite(parsed) ? parsed : null;
}

function initMap(el) {
    if (el.dataset.mapReady === 'true') {
        return;
    }
    el.dataset.mapReady = 'true';

    const lat = toNumber(el.dataset.lat);
    const lng = toNumber(el.dataset.lng);

    if (lat === null || lng === null) {
        return;
    }

    const map = L.map(el, {
        scrollWheelZoom: false, // keep desktop page scroll; mobile one-finger drag stays enabled
        tap: true,
    }).setView([lat, lng], 13);

    const tiles = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    });

    tiles.on('tileerror', () => {
        const alert = document.querySelector('[data-map-error]');
        if (alert) {
            alert.hidden = false;
        }
    });

    tiles.addTo(map);

    L.circle([lat, lng], {
        radius: 800,
        weight: 2,
        fillOpacity: 0.15,
    })
        .addTo(map)
        .bindTooltip('Perkiraan area (radius ±800 m)', { permanent: false });

    window.setTimeout(() => map.invalidateSize(), 100);
}

function initAll() {
    document.querySelectorAll('[data-catalog-map]').forEach(initMap);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAll);
} else {
    initAll();
}

document.addEventListener('livewire:navigated', initAll);
