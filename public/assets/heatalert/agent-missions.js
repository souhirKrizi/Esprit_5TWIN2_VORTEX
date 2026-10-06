(() => {
    const page = document.querySelector('[data-agent-missions-page]');
    if (!page) return;
    const endpoint = page.dataset.endpoint;
    const editor = page.querySelector('[data-editor-dialog]');
    const form = page.querySelector('[data-mission-form]');
    const details = page.querySelector('[data-participants-dialog]');
    const deletion = page.querySelector('[data-delete-dialog]');
    let editingId = null;
    let deletingId = null;
    let saving = false;
    const labels = { ouverte: 'Ouverte', complete: 'Complète', en_cours: 'En cours', terminee: 'Terminée', annulee: 'Annulée', en_attente: 'En attente', acceptee: 'Acceptée', refusee: 'Refusée', non_renseignee: 'Non renseignée', present: 'Présent', absent: 'Absent' };

    function errorAt(selector, message) {
        const error = page.querySelector(selector);
        error.textContent = message;
        error.hidden = false;
        error.focus();
    }
    async function request(url, options = {}) {
        const response = await fetch(url, {
            ...options,
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': page.dataset.csrf, ...(options.body ? { 'Content-Type': 'application/json' } : {}) },
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            const errors = Object.values(data.errors || {}).flat();
            throw new Error(errors.length ? errors.join('\n') : response.status === 404
                ? 'Cette mission ou sa campagne ne vous est plus affectée. Rechargez la page.'
                : [401, 419].includes(response.status) ? 'Votre session a expiré. Rechargez la page.'
                : response.status === 403 ? 'Vous n’avez pas accès à cette action.' : 'L’action a échoué. Veuillez réessayer.');
        }
        return data;
    }
    function validateDates() {
        const option = form.elements.campagne_id.selectedOptions[0];
        const start = form.elements.date_debut;
        const end = form.elements.date_fin;
        start.min = option?.dataset.start ? `${option.dataset.start}T00:00` : '';
        start.max = option?.dataset.end ? `${option.dataset.end}T23:59` : '';
        end.min = start.value || start.min;
        end.max = start.max;
        end.setCustomValidity(end.value && start.value && end.value <= start.value ? 'La fin doit être postérieure au début de la mission.' : '');
    }
    function openEditor(mission = null, campaignId = '') {
        if (saving || editor.open) return;
        editingId = mission?.id ?? null;
        form.reset();
        page.querySelector('[data-form-error]').hidden = true;
        page.querySelector('#mission-editor-title').textContent = mission ? 'Modifier la mission' : 'Ajouter une mission';
        if (mission) {
            for (const field of ['campagne_id', 'titre', 'description', 'lieu', 'date_debut', 'date_fin', 'nombre_places', 'statut']) {
                form.elements[field].value = mission[field];
            }
        } else form.elements.campagne_id.value = campaignId;
        validateDates();
        editor.showModal();
    }
    page.querySelectorAll('[data-dialog-close]').forEach(button => button.addEventListener('click', () => button.closest('dialog').close()));
    page.querySelectorAll('[data-mission-create]').forEach(button => button.addEventListener('click', () => openEditor(null, button.dataset.missionCreate)));
    for (const field of ['campagne_id', 'date_debut', 'date_fin']) form.elements[field].addEventListener('input', validateDates);

    async function readMission(button, callback) {
        button.disabled = true;
        page.querySelector('[data-page-error]').hidden = true;
        try {
            const data = await request(`${endpoint}/${button.dataset.missionEdit || button.dataset.missionDetails}`);
            callback(data);
        } catch (error) {
            errorAt('[data-page-error]', error.message);
        } finally {
            button.disabled = false;
        }
    }
    page.querySelectorAll('[data-mission-edit]').forEach(button => button.addEventListener('click', () => readMission(button, data => openEditor(data.mission))));
    page.querySelectorAll('[data-mission-details]').forEach(button => button.addEventListener('click', () => readMission(button, data => {
        page.querySelector('#mission-details-title').textContent = data.mission.titre;
        const content = page.querySelector('[data-mission-details-content]');
        content.replaceChildren();
        const date = value => value.replace('T', ' à ');
        for (const [label, value] of [['Description', data.mission.description], ['Lieu', data.mission.lieu], ['Places', data.mission.nombre_places], ['Début', date(data.mission.date_debut)], ['Fin', date(data.mission.date_fin)], ['Statut', labels[data.mission.statut]]]) {
            const item = document.createElement('div');
            const term = document.createElement('dt');
            const definition = document.createElement('dd');
            term.textContent = label;
            definition.textContent = value;
            item.append(term, definition);
            content.append(item);
        }
        const list = page.querySelector('[data-participants-list]');
        list.replaceChildren();
        for (const participant of data.participants) {
            const row = document.createElement('tr');
            for (const value of [participant.name, participant.motivation || 'Aucune motivation renseignée', labels[participant.statut], labels[participant.presence]]) {
                const cell = document.createElement('td');
                cell.textContent = value;
                row.append(cell);
            }
            list.append(row);
        }
        if (!data.participants.length) {
            const row = document.createElement('tr');
            const cell = document.createElement('td');
            cell.colSpan = 4;
            cell.textContent = 'Aucun participant inscrit à cette mission.';
            row.append(cell);
            list.append(row);
        }
        details.showModal();
    })));
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (saving) return;
        for (const field of ['titre', 'description', 'lieu']) form.elements[field].value = form.elements[field].value.trim();
        validateDates();
        if (!form.reportValidity()) return;
        saving = true;
        const submit = form.querySelector('[type="submit"]');
        submit.disabled = true;
        page.querySelector('[data-form-error]').hidden = true;
        try {
            const values = Object.fromEntries(new FormData(form));
            await request(editingId === null ? endpoint : `${endpoint}/${editingId}`, { method: editingId === null ? 'POST' : 'PUT', body: JSON.stringify(values) });
            // Reload from the database so counts and status groups reflect the saved mission.
            location.reload();
        } catch (error) {
            errorAt('[data-form-error]', error.message);
            saving = false;
            submit.disabled = false;
        }
    });
    page.querySelectorAll('[data-mission-delete]').forEach(button => button.addEventListener('click', () => {
        deletingId = button.dataset.missionDelete;
        page.querySelector('[data-delete-title]').textContent = button.dataset.missionTitle;
        page.querySelector('[data-delete-error]').hidden = true;
        deletion.showModal();
    }));
    deletion.addEventListener('close', () => { deletingId = null; });
    page.querySelector('[data-confirm-delete]').addEventListener('click', async event => {
        if (deletingId === null) return;
        const button = event.currentTarget;
        button.disabled = true;
        try {
            await request(`${endpoint}/${deletingId}`, { method: 'DELETE' });
            location.reload();
        } catch (error) {
            errorAt('[data-delete-error]', error.message);
            button.disabled = false;
        }
    });
    function openFromHash() {
        if (location.hash === '#creer-mission') {
            if (form.elements.campagne_id.options.length <= 1) {
                errorAt('[data-page-error]', 'Aucune campagne ne vous est affectée. Vous pourrez créer une mission après une affectation.');
                return;
            }
            openEditor();
        }
    }
    document.querySelectorAll('[data-create-mission-link]').forEach(link => link.addEventListener('click', event => {
        event.preventDefault();
        if (document.querySelector('[data-space-shell]')?.classList.contains('is-mobile-open')) document.querySelector('[data-mobile-close]')?.click();
        if (form.elements.campagne_id.options.length > 1) openEditor();
        else errorAt('[data-page-error]', 'Aucune campagne ne vous est affectée. Vous pourrez créer une mission après une affectation.');
    }));
    window.addEventListener('hashchange', openFromHash);
    editor.addEventListener('close', () => {
        if (location.hash === '#creer-mission') history.replaceState(null, '', location.pathname + location.search);
    });
    openFromHash();
})();
