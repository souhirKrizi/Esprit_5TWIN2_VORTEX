// Module « Conseils de prévention » : confirmation avant une suppression, attente de l'IA et choix des gestes proposés.
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm)) {
                event.preventDefault();
            }
        });
    });

    // Le bouton indique combien de gestes proposés sont cochés.
    const formulaireGestes = document.querySelector('[data-gestes-ia]');
    if (formulaireGestes) {
        const cases = Array.from(formulaireGestes.querySelectorAll('input[type=checkbox]'));
        const boutonGestes = formulaireGestes.querySelector('[data-gestes-ia-bouton]');
        const compte = formulaireGestes.querySelector('[data-gestes-ia-compte]');
        const actualiser = () => {
            const coches = cases.filter((caseGeste) => caseGeste.checked).length;
            boutonGestes.textContent = coches > 1 ? `Ajouter les ${coches} gestes cochés` : (coches === 1 ? 'Ajouter le geste coché' : 'Ajouter les gestes cochés');
            boutonGestes.disabled = coches === 0;
            compte.textContent = `${coches} sur ${cases.length} sélectionné${coches > 1 ? 's' : ''}`;
        };
        cases.forEach((caseGeste) => caseGeste.addEventListener('change', actualiser));
        actualiser();
    }

    const formulaireIa = document.querySelector('[data-ia-formulaire]');
    if (!formulaireIa) {
        return;
    }

    const contenu = document.querySelector('[data-ia-contenu]');
    const chargement = document.querySelector('[data-ia-chargement]');
    const message = document.querySelector('[data-ia-message]');
    const bouton = formulaireIa.querySelector('button');
    const etapes = formulaireIa.dataset.iaEtapes
        ? JSON.parse(formulaireIa.dataset.iaEtapes)
        : ['L’IA étudie votre équipement…', 'Analyse de son autonomie et des risques…', 'Rédaction de vos conseils…'];
    let minuteur = null;

    formulaireIa.addEventListener('submit', () => {
        let etape = 0;
        contenu.hidden = true;
        chargement.hidden = false;
        bouton.disabled = true;
        minuteur = setInterval(() => {
            etape = Math.min(etape + 1, etapes.length - 1);
            message.textContent = etapes[etape];
        }, 1800);
    });

    // Retour arrière du navigateur : on réaffiche la page normalement.
    window.addEventListener('pageshow', () => {
        clearInterval(minuteur);
        contenu.hidden = false;
        chargement.hidden = true;
        bouton.disabled = false;
        message.textContent = etapes[0];
    });
});
