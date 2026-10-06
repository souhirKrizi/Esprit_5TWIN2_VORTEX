(() => {
    const page = document.querySelector('[data-missions-page]');
    if (!page) return;

    const isAgent = page.dataset.mode === 'agent-campagne';
    const userId = page.dataset.userId;
    const campaigns = [
        { id: 1, titre: 'Canicule : les bons réflexes', description: 'Informons les habitants sur les gestes essentiels pour se protéger pendant les fortes chaleurs.', objectif: 'Sensibiliser 300 habitants aux risques de la chaleur.', lieu: 'Tunis', date_debut: '2026-10-01', date_fin: '2026-10-15', statut: 'en_cours', tone: 'sun', theme: 'Prévention & sensibilisation' },
        { id: 2, titre: 'Points d’eau solidaires', description: 'Aidons les habitants à accéder à l’eau et aux espaces de fraîcheur dans leur quartier.', objectif: 'Installer et animer trois points d’eau.', lieu: 'Sfax', date_debut: '2026-10-12', date_fin: '2026-10-25', statut: 'publiee', tone: 'water', theme: 'Solidarité & hydratation' },
        { id: 3, titre: 'Veiller sur nos aînés', description: 'Accompagnons les personnes âgées isolées et partageons les conseils de prévention adaptés.', objectif: 'Accompagner 50 personnes âgées.', lieu: 'Sousse', date_debut: '2026-11-02', date_fin: '2026-11-10', statut: 'publiee', tone: 'care', theme: 'Accompagnement & entraide' },
    ];
    let missions = [
        { id: 1, campagne_id: 1, titre: 'Distribution de kits de prévention', description: 'Accueillir les habitants et distribuer des fiches conseils, des bouteilles d’eau et des casquettes.', lieu: 'Place de la Kasbah, Tunis', date_debut: '2026-10-08T09:00', date_fin: '2026-10-08T12:30', nombre_places: 12, statut: 'ouverte' },
        { id: 2, campagne_id: 1, titre: 'Atelier : reconnaître les signes d’alerte', description: 'Accompagner l’animateur et orienter les participants pendant l’atelier de sensibilisation.', lieu: 'Maison de quartier, Tunis', date_debut: '2026-10-10T14:00', date_fin: '2026-10-10T16:00', nombre_places: 2, statut: 'complete' },
        { id: 3, campagne_id: 2, titre: 'Installation d’un point d’eau', description: 'Installer le stand, préparer les réserves d’eau et informer les passants sur les lieux de fraîcheur.', lieu: 'Centre-ville, Sfax', date_debut: '2026-10-13T08:00', date_fin: '2026-10-13T11:00', nombre_places: 8, statut: 'ouverte' },
        { id: 4, campagne_id: 2, titre: 'Accueil et distribution d’eau', description: 'Assurer l’accueil au stand et distribuer de l’eau aux habitants du quartier.', lieu: 'Bab Bhar, Sfax', date_debut: '2026-10-16T10:00', date_fin: '2026-10-16T14:00', nombre_places: 10, statut: 'ouverte' },
        { id: 5, campagne_id: 3, titre: 'Visites de prévention à domicile', description: 'Rendre visite aux aînés en binôme, partager les conseils de prévention et identifier leurs besoins.', lieu: 'Quartier Bou Jaafar, Sousse', date_debut: '2026-11-03T09:00', date_fin: '2026-11-03T13:00', nombre_places: 6, statut: 'ouverte' },
    ];
    let participations = [
        { id: 1, mission_id: 1, user_id: 'demo-user-1', name: 'Salma Ben Salah', motivation: 'Je souhaite aider les habitants de mon quartier.', statut: 'acceptee', presence: 'non_renseignee' },
        { id: 2, mission_id: 1, user_id: 'demo-user-2', name: 'Mehdi Ayari', motivation: 'Disponible pour la distribution et l’accueil.', statut: 'en_attente', presence: 'non_renseignee' },
        { id: 3, mission_id: 2, user_id: 'demo-user-1', name: 'Salma Ben Salah', motivation: 'Participer à la prévention.', statut: 'acceptee', presence: 'non_renseignee' },
        { id: 4, mission_id: 2, user_id: 'demo-user-3', name: 'Nour Jaziri', motivation: 'Accompagner les participants.', statut: 'acceptee', presence: 'non_renseignee' },
        { id: 5, mission_id: 3, user_id: 'demo-user-4', name: 'Aziz Trabelsi', motivation: 'Aider à installer les points d’eau.', statut: 'en_attente', presence: 'non_renseignee' },
    ];
    let nextMissionId = 6;
    let nextParticipationId = 6;
    let selectedCampaign = null;
    let editingMissionId = null;
    let deletingMissionId = null;
    const labels = { brouillon: 'Brouillon', publiee: 'Publiée', ouverte: 'Ouverte', complete: 'Complète', en_cours: 'En cours', terminee: 'Terminée', annulee: 'Annulée', en_attente: 'En attente', acceptee: 'Acceptée', refusee: 'Refusée', non_renseignee: 'Non renseignée', present: 'Présent', absent: 'Absent' };
    const rootList = page.querySelector('[data-campaigns]');
    const search = page.querySelector('[data-campaign-search]');
    const campaignDialog = page.querySelector('[data-missions-dialog]');
    const missionList = page.querySelector('[data-mission-list]');
    const notice = page.querySelector('[data-mission-notice]');
    const dateFormat = new Intl.DateTimeFormat('fr-TN', { day: '2-digit', month: 'short', year: 'numeric' });
    const timeFormat = new Intl.DateTimeFormat('fr-TN', { hour: '2-digit', minute: '2-digit' });
    const date = value => dateFormat.format(new Date(value.length === 10 ? `${value}T12:00:00` : value));
    const dateTime = value => `${date(value)} à ${timeFormat.format(new Date(value))}`;
    const campaignMissions = id => missions.filter(mission => mission.campagne_id === id);
    const registrations = id => participations.filter(participation => participation.mission_id === id);
    const occupied = id => registrations(id).filter(participation => ['en_attente', 'acceptee'].includes(participation.statut)).length;

    function element(tag, text, className) {
        const node = document.createElement(tag);
        if (text !== undefined) node.textContent = text;
        if (className) node.className = className;
        return node;
    }
    function button(text, className, action) {
        const node = element('button', text, className);
        node.type = 'button';
        node.addEventListener('click', action);
        return node;
    }
    function badge(status, campaign = false) {
        const node = element('span', labels[status], campaign ? 'ha-campaign-status' : 'ha-mission-badge');
        node.dataset.status = status;
        return node;
    }
    function announce(text) {
        notice.textContent = text;
        notice.hidden = false;
    }
    function duration(mission) {
        const minutes = Math.round((new Date(mission.date_fin) - new Date(mission.date_debut)) / 60000);
        const days = Math.floor(minutes / 1440);
        const hours = Math.floor((minutes % 1440) / 60);
        const remainder = minutes % 60;
        return [days ? `${days} j` : '', hours ? `${hours} h` : '', remainder ? `${remainder} min` : ''].filter(Boolean).join(' ') || '0 min';
    }
    function tableCell(row, content) {
        const cell = element('td');
        cell.append(typeof content === 'string' ? element('span', content) : content);
        row.append(cell);
        return cell;
    }

    function renderCampaigns() {
        rootList.replaceChildren();
        const query = search.value.trim().toLocaleLowerCase('fr');
        const visible = campaigns.filter(campaign => `${campaign.titre} ${campaign.lieu}`.toLocaleLowerCase('fr').includes(query));
        page.querySelector('[data-campaign-count]').textContent = campaigns.length;
        page.querySelector('[data-campaigns-empty]').hidden = visible.length > 0;
        visible.forEach(campaign => {
            const count = campaignMissions(campaign.id).length;
            const open = button(isAgent ? 'Gérer les missions' : 'Découvrir les missions', 'ha-primary-button', () => openCampaign(campaign));
            open.setAttribute('aria-haspopup', 'dialog');
            open.setAttribute('aria-label', `${open.textContent} : ${campaign.titre}`);
            if (isAgent) {
                const row = element('tr');
                const title = element('div');
                title.append(element('strong', campaign.titre, 'ha-campaign-title'), element('small', campaign.lieu, 'ha-campaign-place'));
                tableCell(row, title);
                tableCell(row, `${date(campaign.date_debut)} → ${date(campaign.date_fin)}`);
                tableCell(row, badge(campaign.statut, true));
                tableCell(row, `${count} mission${count === 1 ? '' : 's'}`);
                tableCell(row, open);
                rootList.append(row);
            } else {
                const card = element('article', undefined, 'ha-discovery-card');
                const art = element('div', undefined, 'ha-card-art');
                art.dataset.tone = campaign.tone;
                art.append(element('strong', campaign.lieu), element('span', campaign.theme));
                const body = element('div', undefined, 'ha-card-body');
                const meta = element('div', undefined, 'ha-card-meta');
                meta.append(element('span', `${date(campaign.date_debut)} → ${date(campaign.date_fin)}`), element('span', `${count} mission${count === 1 ? '' : 's'} à découvrir`));
                body.append(badge(campaign.statut, true), element('h2', campaign.titre), element('p', campaign.description), meta, open);
                card.append(art, body);
                rootList.append(card);
            }
        });
    }

    function openCampaign(campaign) {
        selectedCampaign = campaign;
        notice.hidden = true;
        page.querySelector('[data-selected-campaign]').textContent = campaign.titre;
        page.querySelector('[data-selected-description]').textContent = campaign.description;
        const context = page.querySelector('[data-campaign-context]');
        context.replaceChildren(element('span', campaign.lieu), element('span', `${date(campaign.date_debut)} → ${date(campaign.date_fin)}`), element('span', `Objectif : ${campaign.objectif}`));
        renderMissions();
        campaignDialog.showModal();
    }

    function renderMissions() {
        missionList.replaceChildren();
        const entries = campaignMissions(selectedCampaign.id);
        if (!entries.length) missionList.append(element('p', isAgent ? 'Cette campagne ne contient aucune mission. Ajoutez la première mission.' : 'Les missions de cette campagne seront bientôt disponibles.', 'ha-empty-state'));
        entries.forEach(mission => {
            const card = element('article', undefined, 'ha-mission-card');
            const heading = element('header');
            heading.append(element('h4', mission.titre), badge(mission.statut));
            const details = element('dl', undefined, 'ha-mission-meta');
            const free = Math.max(0, mission.nombre_places - occupied(mission.id));
            for (const [label, value] of [['Lieu', mission.lieu], ['Places', `${free} disponibles sur ${mission.nombre_places}`], ['Début', dateTime(mission.date_debut)], ['Fin', dateTime(mission.date_fin)], ['Durée', duration(mission)]]) {
                const item = element('div');
                item.append(element('dt', label), element('dd', value));
                details.append(item);
            }
            const actions = element('div', undefined, 'ha-mission-actions');
            if (isAgent) {
                actions.append(
                    button(`Détails · ${registrations(mission.id).length} participant(s)`, 'ha-secondary-button', () => showParticipants(mission)),
                    button('Modifier', 'ha-secondary-button', () => editMission(mission)),
                    button('Supprimer', 'ha-danger-button', () => confirmDelete(mission))
                );
            } else {
                const participation = registrations(mission.id).find(entry => entry.user_id === userId && ['en_attente', 'acceptee'].includes(entry.statut));
                const canJoin = mission.statut === 'ouverte' && free > 0 && !participation;
                const join = button(participation ? 'Inscription en attente' : mission.statut === 'ouverte' && free > 0 ? 'Participer' : 'Inscriptions fermées', 'ha-primary-button', () => participate(mission));
                join.disabled = !canJoin;
                actions.append(join);
            }
            card.append(heading, element('p', mission.description, 'ha-mission-description'), details, actions);
            missionList.append(card);
        });
    }

    function participate(mission) {
        if (mission.statut !== 'ouverte' || occupied(mission.id) >= mission.nombre_places
            || registrations(mission.id).some(entry => entry.user_id === userId && ['en_attente', 'acceptee'].includes(entry.statut))) return;
        participations.push({ id: nextParticipationId++, mission_id: mission.id, user_id: userId, name: page.dataset.userName, motivation: '', statut: 'en_attente', presence: 'non_renseignee' });
        renderMissions();
        announce(`Votre participation à « ${mission.titre} » est en attente de validation.`);
        notice.tabIndex = -1;
        notice.focus();
    }

    page.querySelectorAll('[data-close-dialog]').forEach(node => node.addEventListener('click', () => node.closest('dialog').close()));
    search.addEventListener('input', renderCampaigns);
    campaignDialog.addEventListener('close', () => search.focus());

    const editor = page.querySelector('[data-editor-dialog]');
    const form = page.querySelector('[data-mission-form]');
    const participantsDialog = page.querySelector('[data-participants-dialog]');
    const deleteDialog = page.querySelector('[data-delete-dialog]');

    function editMission(mission = null) {
        editingMissionId = mission?.id ?? null;
        form.reset();
        page.querySelector('[data-editor-title]').textContent = mission ? 'Modifier la mission' : 'Ajouter une mission';
        if (mission) {
            for (const name of ['titre', 'description', 'lieu', 'date_debut', 'date_fin', 'nombre_places', 'statut']) form.elements[name].value = mission[name];
        }
        const start = form.elements.date_debut;
        const end = form.elements.date_fin;
        start.min = `${selectedCampaign.date_debut}T00:00`;
        start.max = `${selectedCampaign.date_fin}T23:59`;
        end.max = start.max;
        form.elements.nombre_places.min = String(Math.max(1, mission ? occupied(mission.id) : 1));
        validateDates();
        editor.showModal();
    }
    function validateDates() {
        const start = form.elements.date_debut;
        const end = form.elements.date_fin;
        end.min = start.value || `${selectedCampaign.date_debut}T00:00`;
        end.setCustomValidity(end.value && start.value && end.value <= start.value ? 'La fin doit être postérieure au début de la mission.' : '');
    }
    function showParticipants(mission) {
        page.querySelector('[data-participants-title]').textContent = mission.titre;
        const entries = registrations(mission.id);
        page.querySelector('[data-participants-summary]').textContent = `${entries.length} inscription(s) · ${mission.nombre_places} places · ${mission.lieu}`;
        const list = page.querySelector('[data-participants-list]');
        list.replaceChildren();
        entries.forEach(entry => {
            const row = element('tr');
            tableCell(row, entry.name);
            tableCell(row, entry.motivation || 'Aucune motivation renseignée');
            tableCell(row, labels[entry.statut]);
            tableCell(row, labels[entry.presence]);
            list.append(row);
        });
        if (!entries.length) {
            const row = element('tr');
            const empty = tableCell(row, 'Aucun participant inscrit à cette mission.');
            empty.colSpan = 4;
            empty.className = 'ha-empty-state';
            list.append(row);
        }
        participantsDialog.showModal();
    }
    function confirmDelete(mission) {
        deletingMissionId = mission.id;
        page.querySelector('[data-delete-title]').textContent = mission.titre;
        deleteDialog.showModal();
    }

    if (isAgent) {
        page.querySelector('[data-add-mission]').addEventListener('click', () => editMission());
        form.elements.date_debut.addEventListener('input', validateDates);
        form.elements.date_fin.addEventListener('input', validateDates);
        form.addEventListener('submit', event => {
            event.preventDefault();
            validateDates();
            for (const name of ['titre', 'description', 'lieu']) form.elements[name].value = form.elements[name].value.trim();
            if (!form.reportValidity()) return;
            const data = new FormData(form);
            const values = {
                campagne_id: selectedCampaign.id, titre: data.get('titre'), description: data.get('description'), lieu: data.get('lieu'),
                date_debut: data.get('date_debut'), date_fin: data.get('date_fin'), nombre_places: Number(data.get('nombre_places')), statut: data.get('statut'),
            };
            if (editingMissionId !== null) {
                const mission = missions.find(entry => entry.id === editingMissionId && entry.campagne_id === selectedCampaign.id);
                if (!mission) return;
                Object.assign(mission, values);
            } else missions.push({ id: nextMissionId++, ...values });
            editor.close();
            renderMissions();
            renderCampaigns();
            page.querySelector('[data-add-mission]').focus();
            announce(editingMissionId !== null ? 'La mission a été modifiée.' : 'La mission a été ajoutée à cette campagne.');
        });
        page.querySelector('[data-confirm-delete]').addEventListener('click', () => {
            if (deletingMissionId === null) return;
            missions = missions.filter(mission => mission.id !== deletingMissionId);
            participations = participations.filter(entry => entry.mission_id !== deletingMissionId);
            deletingMissionId = null;
            deleteDialog.close();
            renderMissions();
            renderCampaigns();
            page.querySelector('[data-add-mission]').focus();
            announce('La mission a été supprimée de cette campagne.');
        });
        deleteDialog.addEventListener('close', () => { deletingMissionId = null; });
    }
    renderCampaigns();
})();
