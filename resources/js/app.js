import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.data('adminProfile', (initialPhoto = '') => ({
    photoPreview: initialPhoto,
    password: '',
    visiblePasswords: {
        current: false,
        new: false,
        confirmation: false,
    },
    get passwordStrength() {
        if (!this.password) {
            return { level: '', label: 'belum diisi', bars: 0 };
        }

        const score = [
            this.password.length >= 8,
            this.password.length >= 12,
            /[a-z]/.test(this.password) && /[A-Z]/.test(this.password),
            /\d/.test(this.password),
            /[^A-Za-z0-9]/.test(this.password),
        ].filter(Boolean).length;
        const level = score <= 2 ? 'lemah' : score <= 4 ? 'sedang' : 'kuat';

        return { level, label: level, bars: level === 'lemah' ? 1 : level === 'sedang' ? 2 : 3 };
    },
    previewPhoto(event) {
        const file = event.target.files?.[0];
        if (!file) return;
        if (this.photoPreview.startsWith('blob:')) URL.revokeObjectURL(this.photoPreview);
        this.photoPreview = URL.createObjectURL(file);
    },
    togglePassword(field) {
        this.visiblePasswords[field] = !this.visiblePasswords[field];
    },
}));

Alpine.start();

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
