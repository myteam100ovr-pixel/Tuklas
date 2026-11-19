const passwordFeedbackForms = () => document.querySelectorAll('[data-password-strength-form]');

const passwordStrength = (value) => {
    const hasLowercase = /[a-z]/.test(value);
    const hasUppercase = /[A-Z]/.test(value);
    const hasNumber = /[0-9]/.test(value);
    const hasSymbol = /[^a-zA-Z0-9]/.test(value);

    if (value.length < 12 || value.length > 72 || !hasLowercase || !hasUppercase || !hasNumber || !hasSymbol) {
        return 'weak';
    }

    return value.length >= 16 ? 'strong' : 'okay';
};

const updatePasswordFeedback = (form) => {
    const password = form.querySelector('[data-password-strength-input]');
    const confirmation = form.querySelector('[data-password-confirmation]');
    const confirmationMessage = form.querySelector('[data-password-confirmation-message]');
    const meter = form.querySelector('[data-password-strength-meter]');
    const label = form.querySelector('[data-password-strength-label]');
    const message = form.querySelector('[data-password-strength-message]');
    const submit = form.querySelector('[data-password-submit]') || form.closest('form')?.querySelector('[type="submit"]');

    if (!password || !confirmation || !confirmationMessage || !meter || !label || !message) {
        return;
    }

    const value = password.value;
    const strength = value ? passwordStrength(value) : 'empty';
    const confirmationHasValue = confirmation.value.length > 0;
    const passwordsMatch = confirmationHasValue && value === confirmation.value;
    const passwordsMismatch = confirmationHasValue && !passwordsMatch;
    const messages = {
        empty: 'Enter a password to check its strength.',
        weak: 'Weak. Use 12 to 72 characters with uppercase and lowercase letters, a number, and a symbol.',
        okay: 'Okay. It meets the password requirements.',
        strong: 'Strong. This password meets the requirements with extra length.',
    };

    meter.dataset.strength = strength;
    meter.querySelector('.users-form__strength-track')?.setAttribute(
        'aria-label',
        strength === 'empty' ? 'Password strength not checked' : `Password strength: ${strength}`,
    );
    label.textContent = strength === 'empty'
        ? 'Not rated'
        : strength.charAt(0).toUpperCase() + strength.slice(1);
    message.textContent = messages[strength];
    confirmationMessage.hidden = !confirmationHasValue;
    confirmationMessage.textContent = passwordsMatch ? 'Passwords match.' : 'Passwords do not match.';
    confirmationMessage.classList.toggle('users-form__confirmation-match', passwordsMatch);
    confirmationMessage.classList.toggle('users-form__confirmation-error', passwordsMismatch);
    confirmation.setAttribute('aria-invalid', String(passwordsMismatch));

    if (submit) {
        submit.disabled = !['okay', 'strong'].includes(strength) || !passwordsMatch;
    }
};

passwordFeedbackForms().forEach(updatePasswordFeedback);

document.addEventListener('input', (event) => {
    if (!event.target.matches('[data-password-strength-input], [data-password-confirmation]')) {
        return;
    }

    const form = event.target.closest('[data-password-strength-form]');
    if (form) {
        updatePasswordFeedback(form);
    }
});

document.addEventListener('click', (event) => {
    const visibility = event.target.closest('[data-password-visibility]');
    if (!visibility) {
        return;
    }

    const form = visibility.closest('[data-password-strength-form]');
    const password = form?.querySelector('[data-password-strength-input]');
    const confirmation = form?.querySelector('[data-password-confirmation]');
    const eyeOpen = visibility.querySelector('[data-password-eye-open]');
    const eyeClosed = visibility.querySelector('[data-password-eye-closed]');

    if (!password || !confirmation || !eyeOpen || !eyeClosed) {
        return;
    }

    const showing = password.type === 'password';
    password.type = showing ? 'text' : 'password';
    confirmation.type = showing ? 'text' : 'password';
    eyeOpen.hidden = showing;
    eyeClosed.hidden = !showing;
    visibility.setAttribute('aria-pressed', String(showing));
    visibility.setAttribute('aria-label', `${showing ? 'Hide' : 'Show'} password`);
    visibility.setAttribute('title', `${showing ? 'Hide' : 'Show'} password`);
});
