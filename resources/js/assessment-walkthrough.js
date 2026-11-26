const page = document.querySelector('[data-assessment-walkthrough]');

if (page) {
    const dialog = page.querySelector('[data-walkthrough-dialog]');
    const title = page.querySelector('[data-walkthrough-title]');
    const copy = page.querySelector('[data-walkthrough-copy]');
    const progress = page.querySelector('[data-walkthrough-progress]');
    const next = page.querySelector('[data-walkthrough-next]');
    const back = page.querySelector('[data-walkthrough-back]');
    const steps = [
        {
            title: 'Start with a few choices about yourself.',
            copy: 'This is not a test. Your answers help Tuklas suggest directions that fit what you like and what you have tried.',
            target: null,
        },
        {
            title: 'Choose skills you have practiced, or choose no prior experience.',
            copy: 'You can begin without a resume or certificate. Only select skills you have actually tried.',
            target: '#assessment-skills',
        },
        {
            title: 'Pick interests and answer each required choice.',
            copy: 'Choose fields you want to explore. If you are unsure, use the flexible or “not sure yet” option.',
            target: '#assessment-interests',
        },
        {
            title: 'Save to see your matches, then choose a next step.',
            copy: 'Work, career, and training suggestions appear above this form. Scanning a resume or certificate is optional; you can explore listings and apply when ready.',
            target: '#assessment-goals',
        },
    ];
    const storageKey = `tuklas-assessment-walkthrough:v1:${document.body.dataset.workspaceUser || 'visitor'}`;
    let step = 0;

    const showStep = () => {
        const current = steps[step];
        title.textContent = current.title;
        copy.textContent = current.copy;
        progress.textContent = `Step ${step + 1} of ${steps.length}`;
        back.hidden = step === 0;
        next.textContent = step === steps.length - 1 ? 'Show me step 1' : 'Next';
    };

    const finish = (goToFirstStep = false) => {
        dialog.close();
        try {
            localStorage.setItem(storageKey, 'done');
        } catch {
            // The tour remains usable when browser storage is unavailable.
        }
        if (goToFirstStep) {
            document.querySelector('#assessment-skills')?.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' });
            document.querySelector('#assessment-skills input')?.focus({ preventScroll: true });
        }
    };

    const open = () => {
        step = 0;
        showStep();
        if (!dialog.open) dialog.showModal();
    };

    page.querySelector('[data-walkthrough-open]')?.addEventListener('click', open);
    page.querySelectorAll('[data-walkthrough-skip]').forEach((button) => {
        button.addEventListener('click', () => finish(false));
    });
    next?.addEventListener('click', () => {
        if (step === steps.length - 1) {
            finish(true);
            return;
        }
        step++;
        showStep();
        const target = steps[step].target;
        if (target) document.querySelector(target)?.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' });
    });
    back?.addEventListener('click', () => {
        if (step === 0) return;
        step--;
        showStep();
        const target = steps[step].target;
        if (target) document.querySelector(target)?.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' });
    });

    try {
        if (page.dataset.autoOpen === 'true' && localStorage.getItem(storageKey) !== 'done') {
            window.setTimeout(open, 250);
        }
    } catch {
        if (page.dataset.autoOpen === 'true') window.setTimeout(open, 250);
    }
}
