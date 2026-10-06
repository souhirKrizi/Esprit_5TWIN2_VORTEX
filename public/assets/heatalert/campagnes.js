(() => {
    const page = document.querySelector('[data-campaign-page]');
    if (!page) return;

    const labels = { brouillon: 'Brouillon', publiee: 'Publiée', en_cours: 'En cours', terminee: 'Terminée', annulee: 'Annulée' };
    let campaigns = JSON.parse(page.querySelector('[data-campaign-data]').textContent);
    let editingId = null;
    let saving = false;
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
    const formError = page.querySelector('[data-campaign-error]');
    const deleteError = page.querySelector('[data-campaign-delete-error]');
    const detailsDialog = page.querySelector('[data-campaign-details]');
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
            && `${campaign.titre} ${campaign.lieu} ${campaign.organisateur?.name || ''}`.toLocaleLowerCase('fr').includes(query));
        list.replaceChildren();
        visible.forEach(campaign => {
            const row = document.createElement('tr');
            const title = cell(row, campaign.titre, 'ha-campaign-title');
            const place = document.createElement('small');
            place.className = 'ha-campaign-place';
            place.textContent = campaign.lieu;
            title.append(place);
            cell(row, campaign.organisateur?.name || 'Agent indisponible');
            cell(row, `${formatDate(campaign.date_debut)} → ${formatDate(campaign.date_fin)}`, 'ha-campaign-period');
            const badge = cell(row, labels[campaign.statut], 'ha-campaign-status').firstChild;
            badge.dataset.status = campaign.statut;
            const actions = document.createElement('td');
            actions.className = 'ha-campaign-actions';
            const details = document.createElement('button');
            details.type = 'button';
            details.className = 'ha-secondary-button';
            details.textContent = 'Consulter';
            details.addEventListener('click', async () => {
                details.disabled = true;
                try {
                    const current = await request(`${page.dataset.campaignUrl}/${campaign.id}`);
                    page.querySelector('#campaign-details-title').textContent = current.titre;
                    const content = page.querySelector('[data-campaign-details-content]');
                    content.replaceChildren();
                    for (const [label, value] of [
                        ['Description', current.description], ['Objectif', current.objectif],
                        ['Lieu', current.lieu], ['Agent responsable', current.organisateur?.name || 'Agent indisponible'],
                        ['Période', `${formatDate(current.date_debut)} → ${formatDate(current.date_fin)}`],
                        ['Statut', labels[current.statut]],
                    ]) {
                        const term = document.createElement('dt');
                        const description = document.createElement('dd');
                        term.textContent = label;
                        description.textContent = value;
                        description.style.whiteSpace = 'pre-wrap';
                        content.append(term, description);
                    }
                    if (current.image) {
                        const image = document.createElement('img');
                        image.src = `/storage/${current.image}`;
                        image.alt = current.titre;
                        image.style.maxWidth = '100%';
                        content.append(image);
                    }
                    detailsDialog.showModal();
                } catch (error) {
                    announce(error.message, true);
                } finally {
                    details.disabled = false;
                }
            });
            const edit = document.createElement('button');
            edit.type = 'button';
            edit.className = 'ha-secondary-button';
            edit.textContent = 'Modifier';
            edit.addEventListener('click', () => openForm(campaign));
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'ha-danger-button';
            remove.textContent = 'Supprimer';
            remove.setAttribute('aria-label', `Supprimer la campagne ${campaign.titre}`);
            remove.addEventListener('click', () => {
                pendingDelete = campaign.id;
                deleteOpener = remove;
                deleteError.hidden = true;
                page.querySelector('[data-campaign-delete-name]').textContent = campaign.titre;
                deleteDialog.showModal();
            });
            actions.append(details, edit, remove);
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

    function announce(message, error = false) {
        notice.hidden = false;
        notice.className = `ha-notice ${error ? 'ha-notice-error' : 'ha-notice-success'}`;
        notice.textContent = message;
    }

    async function request(url, options = {}) {
        const response = await fetch(url, {
            ...options,
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': page.dataset.csrfToken },
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            const errors = Object.values(data.errors || {}).flat();
            throw new Error(errors.length ? errors.join('\n') : (
                response.status === 419 ? 'Votre session a expiré. Rechargez la page.' :
                response.status === 403 ? 'Vous n’avez pas accès à cette action.' :
                response.status === 404 ? 'Cette campagne n’existe plus. Rechargez la page.' :
                'L’action a échoué. Veuillez réessayer.'
            ));
        }
        return data;
    }

    function openForm(campaign = null) {
        if (saving) return;
        editingId = campaign?.id ?? null;
        form.reset();
        formError.hidden = true;
        page.querySelector('#campaign-create-title').textContent = campaign ? 'Modifier la campagne' : 'Ajouter une campagne';
        page.querySelector('#campaign-create-description').textContent = campaign
            ? 'Modifiez les informations et l’agent responsable de la campagne.'
            : 'Définissez la campagne et choisissez son agent responsable.';
        form.querySelector('[type="submit"]').textContent = campaign ? 'Enregistrer les modifications' : 'Ajouter la campagne';
        if (campaign) {
            for (const name of ['titre', 'description', 'objectif', 'lieu', 'organisateur_id', 'date_debut', 'date_fin', 'statut']) {
                form.elements[name].value = campaign[name];
            }
        }
        endDate.min = '';
        endDate.setCustomValidity('');
        validateDates();
        createDialog.showModal();
    }
    page.querySelector('[data-campaign-add]').addEventListener('click', () => openForm());
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
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (saving) return;
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
        if (!form.elements.image.files.length) data.delete('image');
        const id = editingId;
        // PHP parses multipart uploads through POST, with Laravel's method override for PUT.
        if (id !== null) data.set('_method', 'PUT');
        saving = true;
        const submit = form.querySelector('[type="submit"]');
        submit.disabled = true;
        formError.hidden = true;
        try {
            const campaign = await request(`${page.dataset.campaignUrl}${id === null ? '' : `/${id}`}`, { method: 'POST', body: data });
            if (id === null) campaigns.unshift(campaign);
            else campaigns = campaigns.map(item => item.id === id ? campaign : item);
            search.value = '';
            status.value = '';
            render();
            createDialog.close();
            announce(id === null ? 'La campagne a été créée.' : 'La campagne a été modifiée.');
        } catch (error) {
            formError.textContent = error.message;
            formError.style.whiteSpace = 'pre-line';
            formError.hidden = false;
            formError.focus();
        } finally {
            saving = false;
            submit.disabled = false;
        }
    });
    page.querySelector('[data-campaign-confirm-delete]').addEventListener('click', async event => {
        if (pendingDelete === null) return;
        const id = pendingDelete;
        const button = event.currentTarget;
        button.disabled = true;
        deleteError.hidden = true;
        try {
            await request(`${page.dataset.campaignUrl}/${id}`, { method: 'DELETE' });
            campaigns = campaigns.filter(campaign => campaign.id !== id);
            render();
            deleteDialog.close();
            announce('La campagne a été supprimée.');
        } catch (error) {
            deleteError.textContent = error.message;
            deleteError.hidden = false;
            deleteError.focus();
        } finally {
            button.disabled = false;
        }
    });
    search.addEventListener('input', render);
    status.addEventListener('change', render);
    render();
})();
