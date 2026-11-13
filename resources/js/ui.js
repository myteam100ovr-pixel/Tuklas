import './theme.js';
import './tesda-catalog.js';
import './career-chat.js';
import './echo.js';
import './direct-messages.js';
import './auth-carousel.js';
import './auth-legal-dialog.js';
import './workspace-sync.js';

const showNavigationLoading = (message) => {
    document.body.dataset.navigationLoading = message;
    const status = document.getElementById('tk-navigation-status');
    if (status) {
        status.textContent = message;
        status.hidden = false;
    }
    document.querySelector('main')?.setAttribute('aria-busy', 'true');
};
window.addEventListener('pageshow', () => {
    delete document.body.dataset.navigationLoading;
    const status = document.getElementById('tk-navigation-status');
    if (status) status.hidden = true;
    document.querySelector('main')?.removeAttribute('aria-busy');
});
document.addEventListener('click', (event) => {
    const link = event.target.closest('a[href]');
    if (!link || !document.body.classList.contains('tk-workspace') || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || link.hasAttribute('download') || (link.target && link.target !== '_self')) return;
    const destination = new URL(link.href, location.href);
    if (!['http:', 'https:'].includes(destination.protocol) || destination.origin !== location.origin || destination.href === location.href || (destination.pathname === location.pathname && destination.search === location.search)) return;
    event.preventDefault();
    showNavigationLoading('Opening page…');
    requestAnimationFrame(() => requestAnimationFrame(() => { location.href = destination.href; }));
});
document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-menu-btn]');
    document.querySelectorAll('[data-menu]').forEach((menu) => {
        const pop = menu.querySelector('[data-menu-pop]');
        const toggle = menu.querySelector('[data-menu-btn]');
        const mine = btn && menu.contains(btn);
        const open = mine ? pop.hidden : false;
        pop.hidden = !open;
        toggle.setAttribute('aria-expanded', String(open));
    });
});
document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    document.querySelectorAll('[data-menu-pop]').forEach((p) => { p.hidden = true; });
    document.querySelectorAll('[data-menu-btn]').forEach((b) => b.setAttribute('aria-expanded', 'false'));
});

let activeWorkspaceTabs;
let revealActiveTab;

const initializeWorkspaceContent = (root = document) => {
const dob = root.querySelector('#date_of_birth');
const ageOutput = root.querySelector('[data-calculated-age]');
if (dob && ageOutput) {
    const updateAge = () => {
        const parts = dob.value.split('-').map(Number);
        const birth = parts.length === 3 ? new Date(parts[0], parts[1] - 1, parts[2]) : null;
        const today = new Date();
        if (!birth || Number.isNaN(birth.getTime()) || birth > today) {
            ageOutput.textContent = '';
            return;
        }
        let age = today.getFullYear() - birth.getFullYear();
        if (today.getMonth() < birth.getMonth() || (today.getMonth() === birth.getMonth() && today.getDate() < birth.getDate())) age -= 1;
        ageOutput.textContent = `Age: ${age} years old`;
    };
    dob.addEventListener('input', updateAge);
    updateAge();
}
const guardian = root.querySelector('[data-guardian]');
if (dob && guardian) {
    const update = () => {
        const v = dob.value ? new Date(dob.value) : null;
        let minor = false;
        if (v && !Number.isNaN(v.getTime())) {
            const now = new Date();
            let age = now.getFullYear() - v.getFullYear();
            if (now < new Date(now.getFullYear(), v.getMonth(), v.getDate())) age -= 1;
            minor = age < 18;
        }
        guardian.hidden = !minor;
        guardian.querySelectorAll('input').forEach((i) => { i.required = minor; });
    };
    dob.addEventListener('input', update);
    update();
}

if (revealActiveTab) window.removeEventListener('resize', revealActiveTab);
activeWorkspaceTabs = root.querySelector('.tk-tabs--workspace');
const workspaceTabs = activeWorkspaceTabs;
if (workspaceTabs) {
    revealActiveTab = () => {
        const activeTab = workspaceTabs.querySelector('.tk-tab.is-active');
        if (!activeTab || workspaceTabs.scrollWidth <= workspaceTabs.clientWidth) return;
        const tabBounds = activeTab.getBoundingClientRect();
        const navBounds = workspaceTabs.getBoundingClientRect();
        workspaceTabs.scrollLeft += tabBounds.left - navBounds.left
            - (workspaceTabs.clientWidth - tabBounds.width) / 2;
    };
    document.fonts.ready.then(revealActiveTab);
    window.addEventListener('resize', revealActiveTab);
}
};

initializeWorkspaceContent();
window.addEventListener('tuklas:content-refreshed', (event) => {
    initializeWorkspaceContent(event.detail?.main || document);
});
