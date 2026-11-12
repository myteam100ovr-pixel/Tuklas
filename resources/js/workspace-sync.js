const notificationLink = document.querySelector('[data-notifications-url]');
const jobsPage = document.querySelector('[data-live-jobs-url]');
let syncing = false;

const updateNotifications = async () => {
    if (!notificationLink) return;
    const response = await fetch(notificationLink.dataset.notificationsUrl, {
        credentials: 'same-origin',
        headers: { Accept: 'application/json' },
    });
    if (!response.ok) return;
    const data = await response.json();
    const count = Number(data.unread_count) || 0;
    notificationLink.setAttribute('aria-label', `Notifications${count ? ` (${count} unread)` : ''}`);
    notificationLink.querySelector('.tk-notification-count')?.remove();
    if (count) {
        const badge = document.createElement('span');
        badge.className = 'tk-notification-count';
        badge.textContent = String(count);
        notificationLink.append(badge);
    }

    const list = document.querySelector('.notifications-list');
    if (!list) return;
    const pageResponse = await fetch(location.href, { credentials: 'same-origin', headers: { Accept: 'text/html' } });
    if (!pageResponse.ok) return;
    const page = new DOMParser().parseFromString(await pageResponse.text(), 'text/html');
    const nextList = page.querySelector('.notifications-list');
    if (nextList) list.replaceWith(nextList);
    const unread = document.querySelector('[data-unread-count]');
    if (unread) unread.textContent = `${count} unread`;
};

const renderHiringList = (container, job, token) => {
    const users = job.recommended_users || [];
    const signature = JSON.stringify(users.map((user) => [user.id, user.pivot?.hired_at]));
    if (container.dataset.signature === signature) return;
    container.dataset.signature = signature;
    const heading = document.createElement('h3');
    heading.textContent = 'Career seeker matches';
    const list = document.createElement('ul');
    if (!users.length) {
        const empty = document.createElement('li');
        empty.textContent = 'No career seekers matched yet.';
        list.append(empty);
    }
    users.forEach((user) => {
        const row = document.createElement('li');
        row.append(document.createTextNode(`${user.name} (${user.email}) `));
        if (user.pivot?.hired_at) {
            const hired = document.createElement('strong');
            hired.textContent = `Hired ${new Intl.DateTimeFormat('en', { month: 'short', day: 'numeric', year: 'numeric' }).format(new Date(user.pivot.hired_at))}`;
            row.append(hired);
        } else {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = container.closest('[data-job-id]').dataset.hireUrl;
            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = token;
            const email = document.createElement('input');
            email.type = 'hidden';
            email.name = 'youth_email';
            email.value = user.email;
            const button = document.createElement('button');
            button.type = 'submit';
            button.textContent = 'Mark hired';
            form.append(csrf, email, button);
            row.append(form);
        }
        list.append(row);
    });
    container.replaceChildren(heading, list);
};

const updateJobs = async () => {
    if (!jobsPage) return;
    const response = await fetch(jobsPage.dataset.liveJobsUrl, {
        credentials: 'same-origin',
        headers: { Accept: 'application/json' },
    });
    if (!response.ok) return;
    const data = await response.json();
    (data.jobs?.data || []).forEach((job) => {
        const article = [...document.querySelectorAll('[data-job-id]')].find((element) => Number(element.dataset.jobId) === job.id);
        if (!article) return;
        const hiredBadge = article.querySelector('[data-my-hired]');
        if (hiredBadge) hiredBadge.hidden = !(job.recommended_users || []).some((user) => user.pivot?.hired_at);
        const list = article.querySelector('[data-hiring-list]');
        if (list) renderHiringList(list, job, jobsPage.dataset.hireCsrf);
    });
};

const sync = async () => {
    if (syncing || document.hidden) return;
    syncing = true;
    try {
        await Promise.allSettled([updateNotifications(), updateJobs()]);
    } finally {
        syncing = false;
    }
};

if (notificationLink) {
    setInterval(sync, 4000);
    document.addEventListener('visibilitychange', sync);
    window.addEventListener('focus', sync);
}

jobsPage?.addEventListener('submit', async (event) => {
    const form = event.target;
    const article = form.closest('[data-job-id]');
    if (!article || form.action !== article.dataset.hireUrl) return;
    event.preventDefault();
    const button = form.querySelector('button[type="submit"]');
    const status = jobsPage.querySelector('[data-live-status]');
    button.disabled = true;
    try {
        const response = await fetch(form.action, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
            body: new FormData(form),
        });
        const result = await response.json();
        if (!response.ok) throw new Error(result.message || 'Could not record hiring.');
        status.textContent = result.message;
        status.hidden = false;
        await sync();
    } catch (error) {
        status.textContent = error.message || 'Could not record hiring.';
        status.hidden = false;
        button.disabled = false;
    }
});
