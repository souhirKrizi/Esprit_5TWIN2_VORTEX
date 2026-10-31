(() => {
    const by = (selector, root = document) => root.querySelector(selector);
    const all = (selector, root = document) => [...root.querySelectorAll(selector)];
    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, character => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[character]));
    const typeLabels = { point_eau: 'Point d’eau', espace_fraicheur: 'Espace de fraîcheur', centre_accueil: 'Centre d’accueil' };

    function iconColor(etat) { return etat === 'rouge' ? '#a52d1c' : '#176b40'; }

    function setupMapPage() {
        const page = by('[data-signalements-map]');
        const element = by('#signalements-map');
        if (!page || !element || typeof L === 'undefined') return;
        const map = L.map(element).setView([33.8, 9.5], 6);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap contributors' }).addTo(map);
        const layers = L.layerGroup().addTo(map);
        const incidentLayers = L.layerGroup().addTo(map);
        const filters = all('[data-map-filter]', page);
        const initialParams = new URLSearchParams(window.location.search);
        filters.forEach(filter => { if (initialParams.has(filter.dataset.mapFilter)) filter.value = initialParams.get(filter.dataset.mapFilter); });
        const status = by('[data-map-status]', page);
        const load = () => {
            const params = new URLSearchParams(filters.filter(filter => filter.value).map(filter => [filter.dataset.mapFilter, filter.value]));
            status.textContent = 'Chargement des ressources…';
            fetch(`${page.dataset.apiUrl}?${params}`)
                .then(response => response.json())
                .then(payload => {
                    layers.clearLayers();
                    incidentLayers.clearLayers();
                    payload.data.forEach(resource => {
                        const marker = L.circleMarker([resource.latitude, resource.longitude], { radius: 9, color: iconColor(resource.etat), fillColor: iconColor(resource.etat), fillOpacity: .88, weight: 2 });
                        marker.bindPopup(`<strong>${escapeHtml(resource.nom)}</strong><br>${escapeHtml(typeLabels[resource.type] || resource.type)}<br><span class="map-state">${resource.etat === 'rouge' ? 'Incident actif' : 'Ressource OK'}</span><br><a href="${resource.url}">Voir la fiche</a>`).addTo(layers);
                    });
                    (payload.signalements || []).forEach(report => {
                        const marker = L.circleMarker([report.latitude, report.longitude], { radius: 8, color: '#a52d1c', fillColor: '#a52d1c', fillOpacity: .95, weight: 2 });
                        marker.bindPopup(`<strong>Incident validé</strong><br>${escapeHtml(report.commentaire || 'Incident signalé')}<br><a href="${report.url}">Voir le signalement</a>`).addTo(incidentLayers);
                    });
                    all('[data-map-count]').forEach(counter => { counter.textContent = payload.compteurs[counter.dataset.mapCount] ?? 0; });
                    status.textContent = `${payload.data.length} ressource(s) affichée(s)`;
                })
                .catch(() => { status.textContent = 'Impossible de charger les ressources.'; });
        };
        filters.forEach(filter => filter.addEventListener(filter.type === 'search' ? 'input' : 'change', load));
        load();
    }

    function setupStaticMaps() {
        if (typeof L === 'undefined') return;
        all('[data-static-map]').forEach(element => {
            const lat = Number(element.dataset.lat); const lng = Number(element.dataset.lng);
            const map = L.map(element).setView([lat, lng], 14);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap contributors' }).addTo(map);
            L.marker([lat, lng]).addTo(map);
        });
    }

    function setupForm() {
        const form = by('[data-signalement-form]');
        const element = by('#signalement-form-map');
        if (!form || !element || typeof L === 'undefined') return;
        const latInput = by('[data-coordinate="latitude"]', form); const lngInput = by('[data-coordinate="longitude"]', form);
        const map = L.map(element).setView([Number(element.dataset.lat) || 33.8, Number(element.dataset.lng) || 9.5], 7);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap contributors' }).addTo(map);
        const marker = L.marker([Number(latInput.value) || 33.8, Number(lngInput.value) || 9.5], { draggable: true }).addTo(map);
        const setPosition = (lat, lng) => { latInput.value = lat.toFixed(7); lngInput.value = lng.toFixed(7); marker.setLatLng([lat, lng]); };
        setPosition(Number(latInput.value) || 33.8, Number(lngInput.value) || 9.5);
        marker.on('dragend', () => { const position = marker.getLatLng(); setPosition(position.lat, position.lng); });
        by('[data-geolocate]', form)?.addEventListener('click', () => navigator.geolocation?.getCurrentPosition(position => { setPosition(position.coords.latitude, position.coords.longitude); map.setView([position.coords.latitude, position.coords.longitude], 14); }, () => alert('Votre position n’a pas pu être obtenue.')));
        const incidentField = by('[data-incident-field]', form); const incidentInput = by('[data-incident-input]', form); const comment = by('[data-commentaire]', form); const count = by('[data-comment-count]', form);
        const updateType = () => { const incident = by('[data-signalement-type]:checked', form)?.value === 'incident'; incidentField.hidden = !incident; incidentInput.required = incident; comment.required = incident; if (!incident) incidentInput.value = ''; };
        all('[data-signalement-type]', form).forEach(input => input.addEventListener('change', updateType)); updateType();
        const updateCount = () => { count.textContent = comment.value.length; }; comment.addEventListener('input', updateCount); updateCount();
        const fileInput = by('[data-images]', form); const preview = by('[data-image-previews]', form); const existingCount = Number(fileInput?.dataset.existingCount || 0); let files = [];
        const renderFiles = () => { preview.innerHTML = ''; files.forEach((file, index) => { const item = document.createElement('div'); item.className = 'ha-image-preview'; item.innerHTML = `<img alt="Aperçu de ${escapeHtml(file.name)}"><button type="button" aria-label="Retirer l’image">×</button>`; item.querySelector('img').src = URL.createObjectURL(file); item.querySelector('button').addEventListener('click', () => { files.splice(index, 1); syncFiles(); renderFiles(); }); preview.appendChild(item); }); };
        const syncFiles = () => { const transfer = new DataTransfer(); files.forEach(file => transfer.items.add(file)); fileInput.files = transfer.files; };
        fileInput?.addEventListener('change', () => { const incoming = [...fileInput.files]; if (existingCount + files.length + incoming.length > 5) { by('[data-error-for="images"]', form).textContent = 'Le nombre total de pièces jointes ne peut pas dépasser 5.'; syncFiles(); return; } const invalid = incoming.find(file => !['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 5 * 1024 * 1024); if (invalid) { by('[data-error-for="images"]', form).textContent = 'Chaque image doit être JPG, PNG ou WEBP et ne pas dépasser 5 Mo.'; syncFiles(); return; } by('[data-error-for="images"]', form).textContent = ''; files.push(...incoming); syncFiles(); renderFiles(); });
        const validateText = () => { const incident = by('[data-signalement-type]:checked', form)?.value === 'incident'; const commentError = comment.value.length > 0 && comment.value.length < 10 ? 'Le commentaire doit contenir au moins 10 caractères.' : ''; by('[data-error-for="commentaire"]', form).textContent = incident && !comment.value ? 'Le commentaire est obligatoire pour un incident.' : commentError; by('[data-error-for="incident"]', form).textContent = incident && !incidentInput.value ? 'Veuillez choisir le type d’incident.' : ''; return !by('[data-error-for="commentaire"]', form).textContent && !by('[data-error-for="incident"]', form).textContent; };
        comment.addEventListener('input', validateText); incidentInput.addEventListener('change', validateText);
        form.addEventListener('submit', event => { const validText = validateText(); if (!latInput.value || !lngInput.value) { event.preventDefault(); by('[data-error-for="position"]', form).textContent = 'Veuillez choisir une position sur la carte.'; return; } if (!validText) { event.preventDefault(); return; } const button = by('[data-submit-button]', form); button.disabled = true; button.textContent = 'Envoi en cours…'; });
    }

    function setupDeleteDialog() {
        const dialog = by('[data-delete-dialog]'); const form = by('[data-delete-form]'); if (!dialog || !form) return;
        all('[data-delete-trigger]').forEach(trigger => trigger.addEventListener('click', () => { form.action = trigger.dataset.deleteAction; dialog.showModal(); }));
        all('[data-close-dialog]', dialog).forEach(button => button.addEventListener('click', () => dialog.close()));
    }

    function setupAttachmentDelete() {
        all('[data-attachment-delete]').forEach(button => button.addEventListener('click', () => {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = button.dataset.deleteAction;
            form.innerHTML = '<input type="hidden" name="_token" value="' + button.dataset.csrfToken + '"><input type="hidden" name="_method" value="DELETE">';
            document.body.appendChild(form);
            form.submit();
        }));
    }

    function setupAssistantRedaction() {
        const container = by('[data-assistant-container]');
        if (!container) return;

        const form = by('[data-signalement-form]');
        const trigger = by('[data-assistant-trigger]', container);
        const label = by('[data-assistant-btn-label]', container);
        const spinner = by('[data-assistant-btn-spinner]', container);
        const errorBox = by('[data-assistant-error]', container);
        const suggestionBox = by('[data-assistant-suggestion]', container);
        const textTarget = by('[data-assistant-text]', container);
        const useBtn = by('[data-assistant-use]', container);
        const ignoreBtn = by('[data-assistant-ignore]', container);

        const commentInput = by('[data-commentaire]', form);
        const defaultUnavailableMsg = "L'assistant est indisponible, vous pouvez continuer à écrire votre description.";

        const showError = (msg) => {
            errorBox.textContent = msg || defaultUnavailableMsg;
            errorBox.hidden = false;
        };

        const clearError = () => {
            errorBox.textContent = '';
            errorBox.hidden = true;
        };

        const setLoading = (loading) => {
            trigger.disabled = loading;
            if (label) label.hidden = loading;
            if (spinner) spinner.hidden = !loading;
        };

        trigger?.addEventListener('click', async () => {
            clearError();
            suggestionBox.hidden = true;

            const textValue = (commentInput?.value || '').trim();
            if (textValue.length < 5) {
                showError("Veuillez saisir au moins 5 caractères dans le commentaire avant de demander l'aide de l'assistant.");
                return;
            }

            const typeSignalement = by('[data-signalement-type]:checked', form)?.value || null;
            const typeIncident = by('[data-incident-input]', form)?.value || null;
            const csrfToken = by('input[name="_token"]', form)?.value || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const url = container.dataset.url;

            setLoading(true);

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        texte: textValue,
                        type_signalement: typeSignalement,
                        type_incident: typeIncident,
                    }),
                });

                if (response.ok) {
                    const data = await response.json();
                    if (data.suggestion) {
                        textTarget.textContent = data.suggestion;
                        suggestionBox.hidden = false;
                    } else {
                        showError(defaultUnavailableMsg);
                    }
                } else if (response.status === 422) {
                    const data = await response.json().catch(() => ({}));
                    const firstError = data.errors ? Object.values(data.errors).flat()[0] : (data.message || defaultUnavailableMsg);
                    showError(firstError);
                } else if (response.status === 429) {
                    showError("Trop de demandes envoyées. Veuillez patienter une minute avant de réessayer.");
                } else if (response.status === 503) {
                    const data = await response.json().catch(() => ({}));
                    showError(data.message || defaultUnavailableMsg);
                } else {
                    showError(defaultUnavailableMsg);
                }
            } catch (err) {
                showError(defaultUnavailableMsg);
            } finally {
                setLoading(false);
            }
        });

        useBtn?.addEventListener('click', () => {
            if (textTarget.textContent && commentInput) {
                commentInput.value = textTarget.textContent;
                commentInput.dispatchEvent(new Event('input', { bubbles: true }));
            }
            suggestionBox.hidden = true;
            clearError();
        });

        ignoreBtn?.addEventListener('click', () => {
            suggestionBox.hidden = true;
            clearError();
        });
    }

    document.addEventListener('DOMContentLoaded', () => { setupMapPage(); setupStaticMaps(); setupForm(); setupDeleteDialog(); setupAttachmentDelete(); setupAssistantRedaction(); });
})();
