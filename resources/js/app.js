import './bootstrap';

document.querySelectorAll('[data-password-toggle]').forEach((toggle) => {
    const inputId = toggle.getAttribute('aria-controls');
    const password = inputId ? document.getElementById(inputId) : null;

    if (!(password instanceof HTMLInputElement)) {
        return;
    }

    const visibleIcon = toggle.querySelector('[data-password-visible-icon]');
    const hiddenIcon = toggle.querySelector('[data-password-hidden-icon]');

    toggle.addEventListener('click', () => {
        const isVisible = password.type === 'password';

        password.type = isVisible ? 'text' : 'password';
        toggle.setAttribute('aria-label', isVisible ? 'Sembunyikan password' : 'Tampilkan password');
        toggle.setAttribute('aria-pressed', String(isVisible));
        toggle.classList.add('scale-110');
        window.setTimeout(() => toggle.classList.remove('scale-110'), 160);
        visibleIcon?.classList.toggle('hidden', !isVisible);
        hiddenIcon?.classList.toggle('hidden', isVisible);
    });
});

document.querySelectorAll('[data-login-form]').forEach((form) => {
    if (!(form instanceof HTMLFormElement)) {
        return;
    }

    form.addEventListener('submit', () => {
        const button = form.querySelector('[data-login-submit]');
        const label = form.querySelector('[data-login-label]');
        const spinner = form.querySelector('[data-login-spinner]');

        if (!(button instanceof HTMLButtonElement)) {
            return;
        }

        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        if (label) {
            label.textContent = 'Memeriksa akun…';
        }
        spinner?.classList.remove('hidden');
    });
});
