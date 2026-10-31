// Module « Conseils de prévention » : confirmation avant une suppression et attente des conseils de l'IA.
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm)) {
                event.preventDefault();
            }
        });
    });

    const formulaireIa = document.querySelector('[data-ia-formulaire]');
    if (!formulaireIa) {
        return;
    }

    const contenu = document.querySelector('[data-ia-contenu]');
    const chargement = document.querySelector('[data-ia-chargement]');
    const message = document.querySelector('[data-ia-message]');
    const bouton = formulaireIa.querySelector('button');
    const etapes = ['L’IA étudie votre équipement…', 'Analyse de son autonomie et des risques…', 'Rédaction de vos conseils…'];
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
