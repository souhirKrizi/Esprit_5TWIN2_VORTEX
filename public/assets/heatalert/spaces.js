(() => {
    const shell = document.querySelector('[data-space-shell]');
    if (!shell) return;

    const sidebar = shell.querySelector('.ha-sidebar');
    const main = shell.querySelector('main');
    const opener = shell.querySelector('[data-mobile-open]');
    const closer = shell.querySelector('[data-mobile-close]');
    const overlay = shell.querySelector('[data-sidebar-overlay]');
    const collapse = shell.querySelector('[data-desktop-collapse]');
    const mobile = window.matchMedia('(max-width: 767px)');
    let desktopCollapsed = false;

    function setMobileOpen(open, restoreFocus = true) {
        shell.classList.toggle('is-mobile-open', open);
        overlay.hidden = !open;
        opener.setAttribute('aria-expanded', String(open));
        sidebar.inert = mobile.matches && !open;
        main.inert = open;
        opener.inert = open;
        if (open) closer.focus();
        else if (restoreFocus) opener.focus();
    }

    function syncViewport() {
        setMobileOpen(false, false);
        shell.classList.toggle('is-collapsed', !mobile.matches && desktopCollapsed);
    }

    opener.addEventListener('click', () => setMobileOpen(true));
    closer.addEventListener('click', () => setMobileOpen(false));
    overlay.addEventListener('click', () => setMobileOpen(false));
    mobile.addEventListener('change', syncViewport);
    syncViewport();

    collapse.addEventListener('click', () => {
        desktopCollapsed = !desktopCollapsed;
        shell.classList.toggle('is-collapsed', desktopCollapsed);
        collapse.setAttribute('aria-expanded', String(!desktopCollapsed));
        const label = desktopCollapsed ? 'Développer la sidebar' : 'Réduire la sidebar';
        collapse.setAttribute('aria-label', label);
        collapse.title = label;
    });

    shell.querySelectorAll('[data-section-toggle]').forEach((toggle) => {
        toggle.addEventListener('click', () => {
            const expanded = toggle.getAttribute('aria-expanded') === 'true';
            toggle.setAttribute('aria-expanded', String(!expanded));
            document.getElementById(toggle.getAttribute('aria-controls')).hidden = expanded;
        });
    });

    const items = [...shell.querySelectorAll('[data-menu-item]')];
    items.filter((item) => item.tagName === 'BUTTON').forEach((item) => {
        item.addEventListener('click', () => {
            items.forEach((entry) => {
                entry.classList.toggle('is-active', entry === item);
                if (entry.tagName === 'BUTTON') entry.setAttribute('aria-pressed', String(entry === item));
                else entry.removeAttribute('aria-current');
            });
            if (mobile.matches) setMobileOpen(false);
        });
    });

    document.addEventListener('keydown', (event) => {
        if (!shell.classList.contains('is-mobile-open')) return;
        if (event.key === 'Escape') {
            event.preventDefault();
            setMobileOpen(false);
        }
        if (event.key === 'Tab') {
            const focusable = [...sidebar.querySelectorAll('a[href], button')]
                .filter((element) => element.getClientRects().length > 0);
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }
    });
})();
