(() => {
    const page = document.querySelector('[data-campaign-page]');
    if (!page) return;

    const labels = { brouillon: 'Brouillon', publiee: 'Publiée', en_cours: 'En cours', terminee: 'Terminée', annulee: 'Annulée' };
    let campaigns = [
        { id: 1, titre: 'Canicule : les bons réflexes', lieu: 'Tunis, centre-ville', agent: 'Amira Ben Ali', organisateur_id: 'demo-agent-1', date_debut: '2026-10-01', date_fin: '2026-10-15', statut: 'en_cours' },
        { id: 2, titre: 'Points d’eau solidaires', lieu: 'Sfax', agent: 'Youssef Mansour', organisateur_id: 'demo-agent-2', date_debut: '2026-10-12', date_fin: '2026-10-25', statut: 'publiee' },
        { id: 3, titre: 'Sensibilisation auprès des seniors', lieu: 'Sousse', agent: 'Inès Trabelsi', organisateur_id: 'demo-agent-3', date_debut: '2026-11-02', date_fin: '2026-11-10', statut: 'brouillon' },
    ];
    let nextId = 4;
    let pendingDelete = null;
    let deleteOpener = null;
    const list = page.querySelector('[data-campaign-list]');
    const search = page.querySelector('[data-campaign-search]');
    const status = page.querySelector('[data-campaign-status]');
    const createDialog = page.querySelector('[data-campaign-create]');
    const deleteDialog = page.querySelector('[data-campaign-delete]');
    const form = page.querySelector('[data-campaign-form]');
    const startDate = form.elements.date_debut;
    const endDate = form.elements.date_fin;
    const notice = page.querySelector('[data-campaign-notice]');
    const dateFormat = new Intl.DateTimeFormat('fr-TN', { day: '2-digit', month: 'short', year: 'numeric' });
    const formatDate = value => dateFormat.format(new Date(`${value}T12:00:00`));

    function cell(row, text, className) {
        const td = document.createElement('td');
        const content = document.createElement('span');
        content.textContent = text;
        if (className) content.className = className;
        td.append(content);
        row.append(td);
        return td;
    }

    function render() {
        const query = search.value.trim().toLocaleLowerCase('fr');
        const visible = campaigns.filter(campaign => (!status.value || campaign.statut === status.value)
            && `${campaign.titre} ${campaign.lieu} ${campaign.agent}`.toLocaleLowerCase('fr').includes(query));
        list.replaceChildren();
        visible.forEach(campaign => {
            const row = document.createElement('tr');
            const title = cell(row, campaign.titre, 'ha-campaign-title');
            const place = document.createElement('small');
            place.className = 'ha-campaign-place';
            place.textContent = campaign.lieu;
            title.append(place);
            cell(row, campaign.agent);
            cell(row, `${formatDate(campaign.date_debut)} → ${formatDate(campaign.date_fin)}`, 'ha-campaign-period');
            const badge = cell(row, labels[campaign.statut], 'ha-campaign-status').firstChild;
            badge.dataset.status = campaign.statut;
            const actions = document.createElement('td');
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'ha-danger-button';
            remove.textContent = 'Supprimer';
            remove.setAttribute('aria-label', `Supprimer la campagne ${campaign.titre}`);
            remove.addEventListener('click', () => {
                pendingDelete = campaign.id;
                deleteOpener = remove;
                page.querySelector('[data-campaign-delete-name]').textContent = campaign.titre;
                deleteDialog.showModal();
            });
            actions.append(remove);
            row.append(actions);
            list.append(row);
        });
        if (!visible.length) {
            const row = document.createElement('tr');
            const empty = cell(row, campaigns.length ? 'Aucune campagne ne correspond à votre recherche.' : 'Aucune campagne. Ajoutez votre première campagne.');
            empty.colSpan = 5;
            empty.className = 'ha-empty-state';
            list.append(row);
        }
        page.querySelector('[data-campaign-total]').textContent = campaigns.length;
        page.querySelector('[data-campaign-active]').textContent = campaigns.filter(campaign => campaign.statut === 'en_cours').length;
        page.querySelector('[data-campaign-agents]').textContent = new Set(campaigns.map(campaign => campaign.organisateur_id)).size;
        page.querySelector('[data-campaign-results]').textContent = `${visible.length} campagne${visible.length === 1 ? '' : 's'}`;
    }

    function announce(message) {
        notice.hidden = false;
        notice.textContent = message;
    }

    page.querySelector('[data-campaign-add]').addEventListener('click', () => {
        form.reset();
        endDate.min = '';
        endDate.setCustomValidity('');
        createDialog.showModal();
    });
    page.querySelectorAll('[data-dialog-close]').forEach(button => {
        button.addEventListener('click', () => button.closest('dialog').close());
    });
    deleteDialog.addEventListener('close', () => {
        pendingDelete = null;
        if (deleteOpener?.isConnected) deleteOpener.focus();
        else page.querySelector('[data-campaign-add]').focus();
    });
    function validateDates() {
        endDate.min = startDate.value;
        endDate.setCustomValidity(endDate.value && startDate.value && endDate.value < startDate.value
            ? 'La date de fin doit être postérieure ou égale à la date de début.' : '');
    }
    startDate.addEventListener('input', validateDates);
    endDate.addEventListener('input', validateDates);
    form.addEventListener('submit', event => {
        event.preventDefault();
        validateDates();
        if (!form.reportValidity()) return;
        for (const name of ['titre', 'description', 'objectif', 'lieu']) {
            if (!form.elements[name].value.trim()) {
                form.elements[name].value = '';
                form.elements[name].reportValidity();
                return;
            }
        }
        const data = new FormData(form);
        campaigns.unshift({
            id: nextId++, titre: data.get('titre').trim(), description: data.get('description').trim(),
            objectif: data.get('objectif').trim(), lieu: data.get('lieu').trim(),
            organisateur_id: data.get('organisateur_id'), agent: form.elements.organisateur_id.selectedOptions[0].textContent,
            date_debut: data.get('date_debut'), date_fin: data.get('date_fin'), statut: data.get('statut'),
            image: form.elements.image.files[0]?.name || null,
        });
        search.value = '';
        status.value = '';
        render();
        createDialog.close();
        announce('La campagne a été ajoutée à la liste.');
    });
    page.querySelector('[data-campaign-confirm-delete]').addEventListener('click', () => {
        if (pendingDelete === null) return;
        campaigns = campaigns.filter(campaign => campaign.id !== pendingDelete);
        render();
        deleteDialog.close();
        announce('La campagne a été supprimée de la liste.');
    });
    search.addEventListener('input', render);
    status.addEventListener('change', render);
    render();
})();
