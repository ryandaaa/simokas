/**
 * Listing location picker (Leaflet + OpenStreetMap).
 *
 * Bundled locally by Vite as a separate entry (resources/js/listing-map.js)
 * and loaded ONLY on pages that include the listing location picker partial,
 * never in the global layout (UI_GUIDELINES section 10).
 *
 * Works together with plain lat/lng text inputs: clicking the map (or
 * dragging the pin) writes to those inputs so the form still submits without
 * JavaScript, and a MutationObserver moves the pin back when Livewire
 * re-renders the inputs after a validation error.
 */
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import './listing-map.css';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

// Fix default marker URLs for the Vite bundle (Leaflet expects its images
// next to the CSS file, which breaks once Vite hashes asset filenames).
L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

function toNumber(value) {
    const parsed = Number.parseFloat(value);
    return Number.isFinite(parsed) ? parsed : null;
}

function writeInputs(latInput, lngInput, lat, lng) {
    if (!latInput || !lngInput) {
        return;
    }

    const text = `${lat.toFixed(7)},${lng.toFixed(7)}`.split(',');
    window.__listingMapSyncing = true;
    latInput.value = text[0];
    lngInput.value = text[1];
    latInput.dispatchEvent(new Event('input', { bubbles: true }));
    lngInput.dispatchEvent(new Event('input', { bubbles: true }));
    window.__listingMapSyncing = false;
}

function initMap(el) {
    if (el.dataset.mapReady === 'true') {
        return;
    }
    el.dataset.mapReady = 'true';

    const centreLat = toNumber(el.dataset.centreLat) ?? 0.5071;
    const centreLng = toNumber(el.dataset.centreLng) ?? 101.4478;
    const startLat = toNumber(el.dataset.lat);
    const startLng = toNumber(el.dataset.lng);

    const map = L.map(el, {
        scrollWheelZoom: false, // keep desktop page scroll; mobile one-finger drag stays enabled
        tap: true,
    }).setView(
        startLat !== null && startLng !== null ? [startLat, startLng] : [centreLat, centreLng],
        12,
    );

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

    const marker = L.marker(map.getCenter(), { draggable: true }).addTo(map);

    const form = el.closest('form') ?? document;
    const latInput = form.querySelector('#lat');
    const lngInput = form.querySelector('#lng');

    const moveMarker = (lat, lng, keepView = false) => {
        marker.setLatLng([lat, lng]);
        if (!keepView) {
            map.setView([lat, lng], Math.max(map.getZoom(), 13));
        }
    };

    map.on('click', (event) => {
        moveMarker(event.latlng.lat, event.latlng.lng, true);
        writeInputs(latInput, lngInput, event.latlng.lat, event.latlng.lng);
    });

    marker.on('dragend', () => {
        const pos = marker.getLatLng();
        writeInputs(latInput, lngInput, pos.lat, pos.lng);
    });

    // Follow server-rendered values (e.g. after a Livewire validation round
    // trip) back onto the pin.
    if (latInput && lngInput && typeof MutationObserver !== 'undefined') {
        const syncFromInputs = () => {
            if (window.__listingMapSyncing) {
                return;
            }
            const lat = toNumber(latInput.value);
            const lng = toNumber(lngInput.value);
            if (lat !== null && lng !== null) {
                moveMarker(lat, lng, true);
            }
        };

        const observer = new MutationObserver(syncFromInputs);
        observer.observe(latInput, { attributes: true, attributeFilter: ['value'] });
        observer.observe(lngInput, { attributes: true, attributeFilter: ['value'] });
        latInput.addEventListener('change', syncFromInputs);
        lngInput.addEventListener('change', syncFromInputs);
    }

    // Leaflet needs a recalculation when the container becomes visible late
    // (e.g. after Livewire morphs surrounding markup).
    window.setTimeout(() => map.invalidateSize(), 100);
}

function initAll() {
    document.querySelectorAll('[data-listing-map]').forEach(initMap);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAll);
} else {
    initAll();
}

document.addEventListener('livewire:navigated', initAll);
