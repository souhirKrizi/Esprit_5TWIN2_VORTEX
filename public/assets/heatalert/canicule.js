(() => {
    const page = document.querySelector('[data-canicule-map]');
    const element = document.getElementById('canicule-map');
    const source = page?.querySelector('[data-canicule-geojson]');
    if (!page || !element || !source || typeof L === 'undefined') return;

    const colors = {
        jaune: { fill: '#f4c542', stroke: '#9b7510' },
        orange: { fill: '#ee873d', stroke: '#a64b16' },
        rouge: { fill: '#d94c45', stroke: '#8e2926' },
        neutre: { fill: '#cbd5d1', stroke: '#66736e' },
    };
    const map = L.map(element, { scrollWheelZoom: false });
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 12,
    }).addTo(map);

    const collection = JSON.parse(source.textContent);
    const layer = L.geoJSON(collection, {
        style: feature => {
            const color = colors[feature.properties.niveau] || colors.neutre;
            return { color: color.stroke, fillColor: color.fill, weight: 1.5, fillOpacity: 0.68 };
        },
        onEachFeature: (feature, shape) => {
            const properties = feature.properties;
            const title = `<strong>${escapeHtml(properties.nom)}</strong>`;
            const detail = properties.alerte
                ? `<br>${escapeHtml(properties.alerte)}<br><span>Niveau ${escapeHtml(properties.niveau)}${properties.fin ? ` · jusqu’au ${escapeHtml(properties.fin)}` : ''}</span>`
                : '<br>Aucune alerte active';
            shape.bindPopup(`${title}${detail}`);
            shape.bindTooltip(escapeHtml(properties.nom), { sticky: true });
        },
    }).addTo(map);

    map.fitBounds(layer.getBounds(), { padding: [16, 16] });

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, character => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[character]));
    }
})();