export const initSupermercadosIndex = () => {
    const locationForm = document.getElementById('form-ubicacion-supermercados');
    const locationButton = document.getElementById('btn-supermercados-usar-ubicacion');
    const latitudInput = document.getElementById('supermercados-ubicacion-latitud');
    const longitudInput = document.getElementById('supermercados-ubicacion-longitud');
    const direccionInput = document.getElementById('direccion-postal-super');

    locationForm?.addEventListener('submit', () => {
        if ((direccionInput?.value || '').trim() !== '') {
            latitudInput.value = '';
            longitudInput.value = '';
        }
    });

    locationButton?.addEventListener('click', () => {
        if (!navigator.geolocation || !locationForm || !latitudInput || !longitudInput) {
            return;
        }

        locationButton.setAttribute('disabled', 'disabled');
        locationButton.textContent = locationButton.dataset.loadingText ?? '...';

        navigator.geolocation.getCurrentPosition(
            (position) => {
                latitudInput.value = String(position.coords.latitude);
                longitudInput.value = String(position.coords.longitude);
                locationForm.submit();
            },
            () => {
                locationButton.removeAttribute('disabled');
                locationButton.textContent = locationButton.dataset.errorText ?? 'Error';
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
        );
    });

    const mapElement = document.getElementById('supermercados-map');
    const leaflet = window.L;

    if (!mapElement || typeof leaflet === 'undefined') {
        return;
    }

    const escapeHtml = (value) => String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#39;');

    const markers = JSON.parse(mapElement.dataset.markers || '[]');
    const userLat = Number.parseFloat(mapElement.dataset.userLat || '');
    const userLng = Number.parseFloat(mapElement.dataset.userLng || '');
    const hasUserLocation = Number.isFinite(userLat) && Number.isFinite(userLng);
    const initialCenter = hasUserLocation
        ? [userLat, userLng]
        : (markers[0] ? [markers[0].latitud, markers[0].longitud] : [40.416775, -3.703790]);
    const map = leaflet.map(mapElement, { scrollWheelZoom: false }).setView(initialCenter, hasUserLocation ? 13 : 6);

    leaflet.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors',
    }).addTo(map);

    const bounds = [];

    if (hasUserLocation) {
        leaflet.circleMarker([userLat, userLng], {
            radius: 9,
            color: '#146546',
            fillColor: '#14b86a',
            fillOpacity: 0.95,
            weight: 3,
        }).addTo(map).bindPopup(mapElement.dataset.userLocationPopup || '');
        bounds.push([userLat, userLng]);
    }

    markers.forEach((marker) => {
        const popup = `<strong>${escapeHtml(marker.nombre)}</strong>${marker.direccion ? `<br>${escapeHtml(marker.direccion)}` : ''}`;
        leaflet.marker([marker.latitud, marker.longitud]).addTo(map).bindPopup(popup);
        bounds.push([marker.latitud, marker.longitud]);
    });

    if (bounds.length > 1) {
        map.fitBounds(bounds, { padding: [28, 28], maxZoom: hasUserLocation ? 14 : 7 });
    }
};
