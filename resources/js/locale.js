const supportedLocales = ['en', 'tl'];
const nativeFetch = window.fetch.bind(window);

const getPreferredLocale = () => {
    try {
        const storedLocale = window.localStorage.getItem('tuklas.locale');
        if (supportedLocales.includes(storedLocale)) return storedLocale;
    } catch {
        // Continue with the server-rendered locale when storage is unavailable.
    }

    const documentLocale = document.documentElement.lang.split('-')[0];
    return supportedLocales.includes(documentLocale) ? documentLocale : 'en';
};

window.fetch = (input, init = {}) => {
    let requestUrl;

    try {
        requestUrl = new URL(input instanceof Request ? input.url : input, window.location.href);
    } catch {
        return nativeFetch(input, init);
    }

    if (requestUrl.origin !== window.location.origin) {
        return nativeFetch(input, init);
    }

    const headers = new Headers(init.headers ?? (input instanceof Request ? input.headers : undefined));
    if (!headers.has('Accept-Language')) {
        headers.set('Accept-Language', getPreferredLocale());
    }

    return nativeFetch(input, { ...init, headers });
};

document.addEventListener('DOMContentLoaded', () => {
    const localeCard = document.querySelector('[data-locale-switcher-card]');
    const localeSelect = localeCard?.querySelector('[data-locale-switcher]');
    if (!localeCard || !localeSelect) return;

    const localeFeedback = localeCard.querySelector('[data-locale-feedback]');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const serverLocale = supportedLocales.includes(localeCard.dataset.locale)
        ? localeCard.dataset.locale
        : 'en';

    try {
        window.localStorage.setItem('tuklas.locale', serverLocale);
    } catch {
        // The authenticated server preference remains available without storage.
    }

    localeSelect.addEventListener('change', async () => {
        const nextLocale = localeSelect.value;
        if (!supportedLocales.includes(nextLocale) || nextLocale === serverLocale) return;

        localeSelect.disabled = true;
        localeFeedback.textContent = localeCard.dataset.savingText;

        try {
            window.localStorage.setItem('tuklas.locale', nextLocale);
            document.documentElement.lang = nextLocale;

            const response = await window.fetch(localeCard.dataset.updateUrl, {
                method: 'PUT',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ locale: nextLocale }),
            });

            if (!response.ok) throw new Error('Locale update failed');

            window.location.reload();
        } catch {
            try {
                window.localStorage.setItem('tuklas.locale', serverLocale);
            } catch {
                // Keep the page in its server-rendered language.
            }

            document.documentElement.lang = serverLocale;
            localeSelect.value = serverLocale;
            localeSelect.disabled = false;
            localeFeedback.textContent = localeCard.dataset.errorText;
        }
    });
});
