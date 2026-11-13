const initializeTesdaCatalog = (root = document) => {
root.querySelectorAll('[data-qualification-catalog]').forEach((catalog) => {
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
            const location = card.querySelector('.qualification-location')?.textContent.toLocaleLowerCase() || '';
            const searchableText = `${card.dataset.title} ${location}`.toLocaleLowerCase();
            const matches = (!level.value || card.dataset.level === level.value)
                && (!status.value || card.dataset.status === status.value)
                && words.every((word) => searchableText.includes(word));
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

root.querySelectorAll('[data-provider-directory]').forEach((directory) => {
    const search = document.querySelector('[data-provider-search]');
    const cards = [...directory.querySelectorAll('[data-provider-card]')];
    const count = document.querySelector('[data-provider-count]');
    const empty = document.querySelector('[data-provider-empty]');
    const filter = () => {
        const words = search.value.toLocaleLowerCase().trim().split(/\s+/).filter(Boolean);
        let visible = 0;
        cards.forEach((card) => {
            const matches = words.every((word) => card.textContent.toLocaleLowerCase().includes(word));
            card.hidden = !matches;
            if (matches) visible++;
        });
        count.textContent = `${visible} ${visible === 1 ? 'provider location' : 'provider locations'} found`;
        empty.hidden = visible > 0;
    };
    search.addEventListener('input', filter);
});
};

initializeTesdaCatalog();
window.addEventListener('tuklas:content-refreshed', (event) => {
    initializeTesdaCatalog(event.detail?.main || document);
});
