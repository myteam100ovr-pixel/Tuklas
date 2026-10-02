import './theme.js';
import './career-chat.js';

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

const dob = document.getElementById('date_of_birth');
const guardian = document.querySelector('[data-guardian]');
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

document.querySelector('.tk-tab.is-active')?.scrollIntoView({ inline: 'center', block: 'nearest' });
