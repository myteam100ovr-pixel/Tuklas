document.querySelectorAll('[data-qualification-catalog]').forEach((catalog) => {
    const search = catalog.querySelector('[data-qualification-search]');
    const level = catalog.querySelector('[data-qualification-level]');
    const status = catalog.querySelector('[data-qualification-status]');
    const cards = [...catalog.querySelectorAll('[data-qualification]')];
    const initialLevel = new URLSearchParams(window.location.search).get('level');
    if ([...level.options].some((option) => option.value === initialLevel)) level.value = initialLevel;
    const filter = () => {
        const words = search.value.toLocaleLowerCase().trim().split(/\s+/).filter(Boolean);
        let count = 0;
        cards.forEach((card) => {
            const matches = (!level.value || card.dataset.level === level.value)
                && (!status.value || card.dataset.status === status.value)
                && words.every((word) => card.dataset.title.toLocaleLowerCase().includes(word));
            card.hidden = !matches;
            if (matches) count++;
        });
        catalog.querySelector('[data-qualification-count]').textContent = `${count} ${count === 1 ? 'qualification' : 'qualifications'} found`;
        catalog.querySelector('[data-qualification-empty]').hidden = count > 0;
    };
    search.addEventListener('input', filter);
    level.addEventListener('change', filter);
    status.addEventListener('change', filter);
    filter();
});
