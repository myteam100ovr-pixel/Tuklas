document.addEventListener('click', (event) => {
    const opener = event.target.closest('[data-legal-open]');

    if (opener) {
        const dialog = document.getElementById(`auth-${opener.dataset.legalOpen}-dialog`);

        if (dialog?.showModal) {
            event.preventDefault();
            dialog.showModal();
        }

        return;
    }

    const closer = event.target.closest('[data-legal-close]');

    if (closer) {
        closer.closest('dialog')?.close();
    }
});

document.querySelectorAll('.auth-legal-dialog').forEach((dialog) => {
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            dialog.close();
        }
    });
});
