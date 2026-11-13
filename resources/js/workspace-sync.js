const notificationLink = document.querySelector('[data-notifications-url]');
const jobsPage = document.querySelector('[data-live-jobs-url]');
const jobApplicationsPage = document.querySelector('[data-live-job-applications-url]');
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
    const signature = JSON.stringify(users.map((user) => [user.id, user.pivot?.hired_at, user.pivot?.not_selected_at, user.pivot?.not_selected_reason]));
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
        const person = document.createElement('div');
        person.className = 'job-candidate-workflow__person';
        const name = document.createElement('strong');
        name.textContent = user.name;
        const emailAddress = document.createElement('span');
        emailAddress.textContent = user.email;
        person.append(name, emailAddress);
        row.append(person);
        if (user.pivot?.hired_at) {
            const hired = document.createElement('strong');
            hired.className = 'operations-status';
            hired.textContent = `Hired ${new Intl.DateTimeFormat('en', { month: 'short', day: 'numeric', year: 'numeric' }).format(new Date(user.pivot.hired_at))}`;
            row.append(hired);
        } else if (user.pivot?.not_selected_at) {
            const decision = document.createElement('strong');
            decision.className = 'operations-status';
            decision.textContent = `Not selected · ${user.pivot.not_selected_reason || 'No reason recorded'}`;
            row.append(decision);
        } else {
            const decisions = document.createElement('div');
            decisions.className = 'job-candidate-workflow__decisions';
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
            decisions.append(form);
            const decline = document.createElement('form');
            decline.method = 'POST';
            decline.action = `${container.closest('[data-job-id]').dataset.hireUrl}/not-selected`;
            decline.dataset.hiringDecision = 'not-selected';
            const declineCsrf = csrf.cloneNode();
            decline.append(declineCsrf, email.cloneNode());
            const reasonLabel = document.createElement('label');
            reasonLabel.append(document.createTextNode('Reason they were not selected'));
            const reason = document.createElement('textarea');
            reason.name = 'reason';
            reason.required = true;
            reason.minLength = 5;
            reason.maxLength = 3000;
            reason.placeholder = 'Reason they were not selected';
            reasonLabel.append(reason);
            const declineButton = document.createElement('button');
            declineButton.type = 'submit';
            declineButton.textContent = 'Mark not selected';
            decline.append(reasonLabel, declineButton);
            decisions.append(decline);
            row.append(decisions);
        }
        list.append(row);
    });
    container.replaceChildren(heading, list);
};

const renderJobApplications = (container, applications) => {
    const signature = JSON.stringify(applications.map((application) => [application.id, application.status, application.decision_reason, application.reviewed_at]));
    if (container.dataset.signature === signature) return;
    container.dataset.signature = signature;

    const cards = applications.map((application) => {
        const card = document.createElement('article');
        card.className = 'operations-card job-application-card';

        const heading = document.createElement('div');
        heading.className = 'job-application-card__header';
        const identity = document.createElement('div');
        const employer = document.createElement('p');
        employer.className = 'workspace-eyebrow';
        employer.textContent = application.job?.employer_name || 'Employer not listed';
        const title = document.createElement('h2');
        title.textContent = application.job?.title || 'Job opening';
        const seeker = document.createElement('p');
        seeker.textContent = `${application.user?.name || 'Career Seeker'} · ${application.user?.email || ''}`;
        const status = document.createElement('span');
        status.className = 'operations-status';
        status.textContent = application.status === 'reviewing' ? 'Under review' : application.status.replace('_', ' ').replace(/^\w/, (letter) => letter.toUpperCase());
        identity.append(employer, title, seeker);
        heading.append(identity, status);
        card.append(heading);

        const submitted = document.createElement('p');
        submitted.className = 'job-application-card__date';
        submitted.textContent = `Submitted ${new Intl.DateTimeFormat('en', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(application.created_at))}`;
        card.append(submitted);

        const addResponse = (label, value) => {
            if (!value) return;
            const section = document.createElement('section');
            section.className = 'job-application-card__response';
            const responseHeading = document.createElement('h3');
            responseHeading.textContent = label;
            const responseText = document.createElement('p');
            responseText.textContent = value;
            section.append(responseHeading, responseText);
            card.append(section);
        };
        addResponse('Qualifications for this job', application.qualification_summary);
        addResponse('Message to PESO', application.application_message);
        addResponse('Reason provided by PESO', application.decision_reason);

        if (['submitted', 'reviewing'].includes(application.status)) {
            const form = document.createElement('form');
            form.className = 'job-application-card__review';
            form.method = 'POST';
            form.action = container.dataset.reviewUrlTemplate.replace('JOB_APPLICATION_ID', String(application.id));
            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = container.dataset.reviewCsrf;
            const method = document.createElement('input');
            method.type = 'hidden';
            method.name = '_method';
            method.value = 'PUT';
            form.append(csrf, method);

            const statusLabel = document.createElement('label');
            statusLabel.append(document.createTextNode('Status'));
            const statusSelect = document.createElement('select');
            statusSelect.name = 'status';
            statusSelect.required = true;
            [['reviewing', 'Under review'], ['hired', 'Hired'], ['not_selected', 'Not selected']].forEach(([value, label]) => {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = label;
                option.selected = value === application.status;
                statusSelect.append(option);
            });
            statusLabel.append(statusSelect);

            const reasonLabel = document.createElement('label');
            reasonLabel.append(document.createTextNode('Feedback for the Career Seeker (required if not selected)'));
            const reason = document.createElement('textarea');
            reason.name = 'decision_reason';
            reason.minLength = 5;
            reason.maxLength = 3000;
            reason.value = application.decision_reason || '';
            reasonLabel.append(reason);

            const save = document.createElement('button');
            save.type = 'submit';
            save.textContent = 'Save application decision';
            form.append(statusLabel, reasonLabel, save);
            card.append(form);
        } else if (application.reviewer) {
            const reviewed = document.createElement('p');
            reviewed.className = 'job-application-card__reviewed';
            reviewed.textContent = `Updated by ${application.reviewer.name}${application.reviewed_at ? ` · ${new Intl.DateTimeFormat('en', { dateStyle: 'medium' }).format(new Date(application.reviewed_at))}` : ''}`;
            card.append(reviewed);
        }

        return card;
    });

    if (!cards.length) {
        const empty = document.createElement('p');
        empty.className = 'operations-empty';
        empty.textContent = 'No job applications have been submitted yet. Applications from Career Seekers will appear here.';
        cards.push(empty);
    }
    container.replaceChildren(...cards);
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
        if (hiredBadge) {
            const decision = (job.recommended_users || []).find((user) => user.pivot?.hired_at || user.pivot?.not_selected_at)?.pivot;
            hiredBadge.hidden = !decision;
            if (decision) hiredBadge.textContent = decision.hired_at ? 'Hired · recorded by PESO' : `Not selected · ${decision.not_selected_reason || ''}`;
        }
        const applicationBadge = article.querySelector('[data-my-job-application]');
        const myApplication = (job.applications || [])[0];
        if (applicationBadge && myApplication) {
            const status = myApplication.status === 'reviewing' ? 'Under review' : myApplication.status.replace('_', ' ').replace(/^\w/, (letter) => letter.toUpperCase());
            applicationBadge.textContent = `Application ${status}${myApplication.decision_reason ? ` · PESO feedback: ${myApplication.decision_reason}` : ''}`;
        }
        const list = article.querySelector('[data-hiring-list]');
        if (list) renderHiringList(list, job, jobsPage.dataset.hireCsrf);
    });
};

const updateJobApplications = async () => {
    if (!jobApplicationsPage) return;
    const response = await fetch(jobApplicationsPage.dataset.liveJobApplicationsUrl, {
        credentials: 'same-origin',
        headers: { Accept: 'application/json' },
    });
    if (!response.ok) return;
    const data = await response.json();
    const applications = data.applications?.data || [];
    const container = jobApplicationsPage.querySelector('[data-job-applications-list]');
    if (!container) return;
    renderJobApplications(container, applications);
    const count = jobApplicationsPage.querySelector('[data-job-application-count]');
    if (count) count.textContent = `${data.applications?.total || 0} ${(data.applications?.total || 0) === 1 ? 'application' : 'applications'}`;
};

const sync = async () => {
    if (syncing || document.hidden) return;
    syncing = true;
    try {
        await Promise.allSettled([updateNotifications(), updateJobs(), updateJobApplications()]);
    } finally {
        syncing = false;
    }
};

if (notificationLink || jobsPage || jobApplicationsPage) {
    setInterval(sync, 4000);
    document.addEventListener('visibilitychange', sync);
    window.addEventListener('focus', sync);
}

jobsPage?.addEventListener('submit', async (event) => {
    const form = event.target;
    const article = form.closest('[data-job-id]');
    if (!article || (form.action !== article.dataset.hireUrl && form.action !== `${article.dataset.hireUrl}/not-selected`)) return;
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
