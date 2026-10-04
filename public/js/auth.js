// Frontend preview: navigate without sending or storing form values.
document.querySelectorAll('[data-auth-form]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        event.preventDefault();
        window.location.assign(form.action);
    });
});

document.querySelectorAll('[data-toggle-password]').forEach((button) => {
    button.setAttribute('aria-pressed', 'false');
    button.addEventListener('click', () => {
        const input = document.getElementById(button.dataset.togglePassword);
        const visible = input.type === 'password';
        input.type = visible ? 'text' : 'password';
        button.setAttribute('aria-pressed', String(visible));
        button.setAttribute('aria-label', visible ? 'Masquer le mot de passe' : 'Afficher le mot de passe');
        button.querySelector('span').textContent = visible ? 'visibility_off' : 'visibility';
    });
});

const signInForm = document.getElementById('signInForm');
const errorDialog = document.getElementById('signin-error-dialog');
if (signInForm && errorDialog) {
    if (errorDialog.hasAttribute('data-show-error')) errorDialog.showModal();
    errorDialog.addEventListener('close', () => document.getElementById('email').focus());
    // Server-side validation remains authoritative; local errors use the same popup.
    signInForm.noValidate = true;
    signInForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const showError = (message) => {
            document.getElementById('signin-error-message').textContent = message;
            errorDialog.showModal();
        };
        if (!signInForm.checkValidity()) {
            const email = document.getElementById('email');
            showError(!email.validity.valid
                ? 'Veuillez saisir une adresse e-mail valide.'
                : 'Veuillez saisir votre mot de passe.');
            return;
        }
        const submit = document.getElementById('submitButton');
        if (submit.disabled) return;
        submit.disabled = true;
        document.getElementById('buttonSpinner').classList.remove('hidden');
        try {
            const response = await fetch(signInForm.action, {
                method: 'POST',
                body: new FormData(signInForm),
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            if (response.status === 422) {
                showError('Adresse e-mail ou mot de passe incorrect.');
            } else if (response.status === 429) {
                showError('Trop de tentatives. Patientez une minute avant de réessayer.');
            } else if (response.status === 419) {
                showError('Votre session a expiré. Rechargez la page puis réessayez.');
            } else if (response.ok) {
                const result = await response.json();
                const target = new URL(result.redirect, window.location.origin);
                if (!result.redirect || target.origin !== window.location.origin) throw new Error('Invalid redirect');
                window.location.assign(target.href);
            } else {
                showError('La connexion est indisponible. Réessayez dans quelques instants.');
            }
        } catch {
            showError('Impossible de se connecter. Vérifiez votre connexion puis réessayez.');
        } finally {
            submit.disabled = false;
            document.getElementById('buttonSpinner').classList.add('hidden');
        }
    });
}
