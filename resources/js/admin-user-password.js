const form = document.querySelector('[data-password-strength-form]');

if (form) {
    const password = form.querySelector('[data-password-strength-input]');
    const confirmation = form.querySelector('[data-password-confirmation]');
    const confirmationMessage = form.querySelector('[data-password-confirmation-message]');
    const meter = form.querySelector('[data-password-strength-meter]');
    const label = form.querySelector('[data-password-strength-label]');
    const message = form.querySelector('[data-password-strength-message]');
    const submit = form.querySelector('[data-password-submit]');
    const visibility = form.querySelector('[data-password-visibility]');
    const levels = ['bad', 'moderate', 'good', 'secure'];

    function getStrength(value) {
        const hasLowercase = /[a-z]/.test(value);
        const hasUppercase = /[A-Z]/.test(value);
        const hasNumber = /[0-9]/.test(value);
        const hasSymbol = /[^a-zA-Z0-9]/.test(value);
        const characterTypes = [hasLowercase, hasUppercase, hasNumber, hasSymbol].filter(Boolean).length;

        if (value.length < 8 || value.length > 72 || characterTypes < 2 || /^(.)\1+$/.test(value)) {
            return 'bad';
        }

        if (value.length < 12 || !hasLowercase || !hasUppercase || !hasNumber || !hasSymbol) {
            return 'moderate';
        }

        return value.length >= 16 ? 'secure' : 'good';
    }

    function updateStrength() {
        const value = password.value;
        const strength = getStrength(value);
        const strengthIndex = levels.indexOf(strength);
        const messages = {
            bad: 'Too weak. Change it before creating the account.',
            moderate: 'Almost there. Use at least 12 characters and include every required character type.',
            good: 'Good. It meets the account password requirements.',
            secure: 'Secure. This password is ready to use.',
        };

        meter.dataset.strength = strength;
        meter.querySelector('.users-form__strength-track').setAttribute('aria-label', `Password strength: ${strength}`);
        label.textContent = strength.charAt(0).toUpperCase() + strength.slice(1);
        message.textContent = messages[strength];
        const passwordsMatch = password.value === confirmation.value;
        confirmationMessage.hidden = passwordsMatch;
        confirmation.setAttribute('aria-invalid', String(!passwordsMatch));
        submit.disabled = strengthIndex < 2 || !passwordsMatch;
    }

    password.addEventListener('input', updateStrength);
    confirmation.addEventListener('input', updateStrength);
    visibility.addEventListener('click', () => {
        const showing = password.type === 'password';
        password.type = showing ? 'text' : 'password';
        confirmation.type = showing ? 'text' : 'password';
        visibility.textContent = showing ? 'Hide' : 'Show';
        visibility.setAttribute('aria-label', `${showing ? 'Hide' : 'Show'} initial password`);
    });

    updateStrength();
}
