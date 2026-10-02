const KEY = 'tuklas-theme';
const root = document.documentElement;

const stored = () => { try { return localStorage.getItem(KEY); } catch { return null; } };

function apply(theme, save) {
    root.dataset.theme = theme;
    if (save) { try { localStorage.setItem(KEY, theme); } catch { /* storage unavailable */ } }
    document.querySelectorAll('[data-theme-toggle]').forEach((b) => b.setAttribute('aria-pressed', String(theme === 'dark')));
}

apply(root.dataset.theme || (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'), false);

document.addEventListener('click', (e) => {
    if (!e.target.closest('[data-theme-toggle]')) return;
    apply(root.dataset.theme === 'dark' ? 'light' : 'dark', true);
});

matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
    if (!stored()) apply(e.matches ? 'dark' : 'light', false);
});