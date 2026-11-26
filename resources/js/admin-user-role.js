document.querySelectorAll('[data-tesda-account-form]').forEach((form) => {
    const role = form.querySelector('[data-user-role]');
    const accountTypeField = form.querySelector('[data-tesda-account-type-field]');
    const accountType = form.querySelector('[data-tesda-account-type]');
    const centerField = form.querySelector('[data-tesda-center-field]');
    const center = form.querySelector('[data-tesda-center]');

    if (!role || !accountTypeField || !accountType || !centerField || !center) {
        return;
    }

    const updateFields = () => {
        const isTesda = role.value === 'tesda_admin';
        const isCenter = isTesda && accountType.value === 'center';

        accountTypeField.hidden = !isTesda;
        accountType.disabled = !isTesda;
        accountType.required = isTesda;

        centerField.hidden = !isCenter;
        center.disabled = !isCenter;
        center.required = isCenter;

        if (!isCenter) {
            center.value = '';
        }
    };

    role.addEventListener('change', updateFields);
    accountType.addEventListener('change', updateFields);
    updateFields();
});
