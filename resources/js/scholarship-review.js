document.addEventListener('click', (event) => {
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

    reason.value = '';
    reason.disabled = true;
}, true);
