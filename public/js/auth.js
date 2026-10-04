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
