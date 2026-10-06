(() => {
    const page = document.querySelector('[data-missions-page]');
    if (!page) return;
    const search = page.querySelector('[data-campaign-search]');
    const dialog = page.querySelector('[data-missions-dialog]');
    const list = page.querySelector('[data-mission-list]');
    const notice = page.querySelector('[data-mission-notice]');
    let selectedCampaign = null;
    let missions = [];
    let participating = false;
    let opening = false;
    const labels = { ouverte: 'Ouverte', complete: 'Complète', en_cours: 'En cours', terminee: 'Terminée', annulee: 'Annulée' };
    const groups = { ouverte: 'Missions ouvertes', en_cours: 'Missions en cours', complete: 'Missions complètes', terminee: 'Missions terminées', annulee: 'Missions annulées' };
    const dateFormat = new Intl.DateTimeFormat('fr-TN', { day: '2-digit', month: 'short', year: 'numeric' });
    const timeFormat = new Intl.DateTimeFormat('fr-TN', { hour: '2-digit', minute: '2-digit' });
    const date = value => dateFormat.format(new Date(value.length === 10 ? `${value}T12:00:00` : value));
    const dateTime = value => `${date(value)} à ${timeFormat.format(new Date(value))}`;
    function duration(mission) {
        const minutes = Math.round((new Date(mission.date_fin) - new Date(mission.date_debut)) / 60000);
        const days = Math.floor(minutes / 1440);
        const hours = Math.floor((minutes % 1440) / 60);
        const remainder = minutes % 60;
        return [days ? `${days} j` : '', hours ? `${hours} h` : '', remainder ? `${remainder} min` : ''].filter(Boolean).join(' ') || '0 min';
    }

    function element(tag, text, className) {
        const node = document.createElement(tag);
        if (text !== undefined) node.textContent = text;
        if (className) node.className = className;
        return node;
    }
    async function request(url, options = {}) {
        const response = await fetch(url, { ...options, headers: { Accept: 'application/json', 'X-CSRF-TOKEN': page.dataset.csrf } });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            const errors = Object.values(data.errors || {}).flat();
            throw new Error(errors.length ? errors.join('\n') : [401, 419].includes(response.status)
                ? 'Votre session a expiré. Rechargez la page.' : response.status === 404
                ? 'Cette campagne ou cette mission n’existe plus.' : 'L’action a échoué. Veuillez réessayer.');
        }
        return data;
    }
    function announce(message, error = false) {
        notice.textContent = message;
        notice.className = `ha-notice ${error ? 'ha-notice-error' : 'ha-notice-success'}`;
        notice.hidden = false;
        notice.focus();
    }
    function renderMissions() {
        list.replaceChildren();
        if (!missions.length) list.append(element('p', 'Les missions de cette campagne seront bientôt disponibles.', 'ha-empty-state'));
        for (const [status, label] of Object.entries(groups)) {
            const entries = missions.filter(mission => mission.statut === status);
            if (!entries.length) continue;
            const section = element('section');
            section.append(element('h3', `${label} · ${entries.length}`, 'ha-user-status-title'));
            const cards = element('div', undefined, 'ha-mission-list');
            for (const mission of entries) {
                const card = element('article', undefined, 'ha-mission-card');
                const heading = element('header');
                const badge = element('span', labels[mission.statut], 'ha-mission-badge');
                badge.dataset.status = mission.statut;
                heading.append(element('h4', mission.titre), badge);
                const meta = element('dl', undefined, 'ha-mission-meta');
                for (const [title, value] of [['Lieu', mission.lieu], ['Places', `${mission.available} disponibles sur ${mission.nombre_places}`], ['Début', dateTime(mission.date_debut)], ['Fin', dateTime(mission.date_fin)], ['Durée', duration(mission)]]) {
                    const item = element('div');
                    item.append(element('dt', title), element('dd', value));
                    meta.append(item);
                }
                const actions = element('div', undefined, 'ha-mission-actions');
                const text = mission.participation_statut === 'acceptee' ? 'Participation acceptée'
                    : mission.participation_statut === 'en_attente' ? 'Inscription en attente'
                    : mission.can_participate ? 'Participer' : 'Inscriptions fermées';
                const join = element('button', text, 'ha-primary-button');
                join.type = 'button';
                join.disabled = !mission.can_participate || participating;
                join.setAttribute('aria-label', `${text} : ${mission.titre}`);
                join.addEventListener('click', () => participate(mission));
                actions.append(join);
                card.append(heading, element('p', mission.description, 'ha-mission-description'), meta, actions);
                cards.append(card);
            }
            section.append(cards);
            list.append(section);
        }
    }
    async function refreshMissions() {
        const data = await request(`${page.dataset.campaignUrl}/${selectedCampaign.id}/missions`);
        missions = data.missions;
        renderMissions();
    }
    async function participate(mission) {
        if (participating || !mission.can_participate) return;
        participating = true;
        renderMissions();
        let registered = false;
        let message = '';
        try {
            const data = await request(`${page.dataset.participationUrl}/${mission.id}/participer`, { method: 'POST' });
            registered = true;
            message = data.message;
            mission.participation_statut = data.participation.statut;
            mission.can_participate = false;
            await refreshMissions();
            announce(message);
        } catch (error) {
            if (!registered) {
                try { await refreshMissions(); } catch (_) { /* Preserve the registration error. */ }
            }
            announce(registered ? `${message} Rechargez la page pour actualiser les places.` : error.message, !registered);
        } finally {
            participating = false;
            renderMissions();
        }
    }
    page.querySelectorAll('[data-open-campaign]').forEach(button => button.addEventListener('click', async () => {
        if (participating || opening) return;
        opening = true;
        button.disabled = true;
        const error = page.querySelector('[data-campaign-error]');
        error.hidden = true;
        selectedCampaign = JSON.parse(button.closest('[data-campaign-id]').querySelector('[data-campaign-info]').textContent);
        notice.hidden = true;
        page.querySelector('[data-selected-campaign]').textContent = selectedCampaign.titre;
        page.querySelector('[data-selected-description]').textContent = selectedCampaign.description;
        page.querySelector('[data-campaign-context]').replaceChildren(element('span', selectedCampaign.lieu), element('span', `${date(selectedCampaign.date_debut)} → ${date(selectedCampaign.date_fin)}`), element('span', `Objectif : ${selectedCampaign.objectif}`));
        try {
            await refreshMissions();
            dialog.showModal();
        } catch (failure) {
            error.textContent = failure.message;
            error.hidden = false;
            error.focus();
        } finally {
            button.disabled = false;
            opening = false;
        }
    }));
    page.querySelectorAll('[data-close-dialog]').forEach(button => button.addEventListener('click', () => dialog.close()));
    search.addEventListener('input', () => {
        const query = search.value.trim().toLocaleLowerCase('fr');
        let visible = 0;
        page.querySelectorAll('[data-campaign-id]').forEach(card => {
            card.hidden = !card.dataset.search.toLocaleLowerCase('fr').includes(query);
            if (!card.hidden) visible++;
        });
        
        page.querySelectorAll('[data-status-group], [data-priority-group]').forEach(group => {
            group.hidden = !Array.from(group.querySelectorAll('[data-campaign-id]')).some(card => !card.hidden);
        });
        page.querySelector('[data-campaigns-empty]').hidden = visible > 0;
    });
})();
