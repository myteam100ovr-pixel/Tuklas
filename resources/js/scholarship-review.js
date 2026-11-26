document.addEventListener('click', (event) => {
    const editor = event.target.closest('[data-review-edit]');
    if (editor) {
        const group = editor.closest('.scholarship-review-action-group');
        const recorded = group?.querySelector('[data-review-recorded]');
        const form = group?.querySelector('[data-scholarship-review-form]');
        const controls = form?.querySelector('[data-review-controls]');

        if (!recorded || !form || !controls) return;

        recorded.hidden = true;
        form.hidden = false;
        controls.hidden = false;
        controls.querySelector('button')?.focus();
        return;
    }

    const opener = event.target.closest('[data-review-decline-open]');

    if (opener) {
        const form = opener.closest('[data-scholarship-review-form]');
        const dialog = form?.querySelector('[data-review-decline-dialog]');
        const reason = dialog?.querySelector('[data-review-decline-reason]');

        if (!dialog || !reason || typeof dialog.showModal !== 'function') return;

        reason.disabled = false;
        dialog.showModal();
        reason.focus();
        return;
    }

    const cancel = event.target.closest('[data-review-decline-cancel]');
    if (cancel) {
        cancel.closest('[data-review-decline-dialog]')?.close();
    }
});

document.addEventListener('close', (event) => {
    const dialog = event.target;
    if (!dialog.matches?.('[data-review-decline-dialog]')) return;

    const reason = dialog.querySelector('[data-review-decline-reason]');
    if (!reason) return;

    reason.disabled = true;
}, true);
