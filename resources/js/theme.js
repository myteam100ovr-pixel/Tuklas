const themeKey = 'tuklas-theme';
const root = document.documentElement;

const currentTheme = () => root.dataset.theme === 'dark' ? 'dark' : 'light';

const updateThemeControls = () => {
    const isDark = currentTheme() === 'dark';

    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        const label = isDark ? 'Switch to light mode' : 'Switch to dark mode';

        button.setAttribute('aria-label', label);
        button.setAttribute('title', label);
        button.setAttribute('aria-pressed', String(isDark));

        const icon = button.querySelector('[data-theme-icon]');

        if (icon) {
            icon.textContent = isDark ? '☀' : '☾';
        }
    });
};

updateThemeControls();

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-theme-toggle]');

    if (!button) {
        return;
    }

    const nextTheme = currentTheme() === 'dark' ? 'light' : 'dark';
    root.dataset.theme = nextTheme;

    try {
        localStorage.setItem(themeKey, nextTheme);
    } catch (error) {
        // Keep the current page usable when browser storage is unavailable.
    }

    updateThemeControls();
});

window.addEventListener('storage', (event) => {
    if (event.key === themeKey && (event.newValue === 'dark' || event.newValue === 'light')) {
        root.dataset.theme = event.newValue;
        updateThemeControls();
    }
});
