const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

// Mobile navigation
const header = document.querySelector('.site-header');
const toggle = document.querySelector('.nav-toggle');

function setNav(open) {
    header.classList.toggle('nav-open', open);
    toggle.setAttribute('aria-expanded', String(open));
    toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
}

toggle?.addEventListener('click', () => setNav(!header.classList.contains('nav-open')));
document.querySelectorAll('#site-nav a').forEach((a) => a.addEventListener('click', () => setNav(false)));
document.addEventListener('keydown', (e) => { if (e.key === 'Escape') setNav(false); });

// Scroll reveal
const items = document.querySelectorAll('.reveal');
if (reduceMotion || !('IntersectionObserver' in window)) {
    items.forEach((el) => el.classList.add('is-in'));
} else {
    const io = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-in');
                io.unobserve(entry.target);
            }
        });
    }, { threshold: 0.15 });
    items.forEach((el) => io.observe(el));
}

// Pin the hero illustration, expand its frame, and pan through it as the page scrolls.
const stack = document.querySelector('.hero-stack');
const hero = document.querySelector('.hero');
if (stack && !reduceMotion) {
    let ticking = false;
    const update = () => {
        const bounds = (hero ?? stack).getBoundingClientRect();
        const travel = Math.max(1, bounds.height - window.innerHeight);
        const progress = Math.min(1, Math.max(0, -bounds.top / travel));
        const widthProgress = Math.min(1, progress / 0.96);
        const widthEase = widthProgress * widthProgress * (3 - (2 * widthProgress));
        const heightProgress = Math.min(1, progress / 0.6);
        const heightEase = heightProgress * heightProgress * (3 - (2 * heightProgress));
        const frameProgress = Math.min(widthEase, heightEase);
        const panProgress = Math.min(1, Math.max(0, (progress - 0.18) / 0.82));
        const isCompact = window.innerWidth <= 720;
        const startWidth = Math.min(window.innerWidth * (isCompact ? 0.94 : 0.94), 1160);
        const startHeight = Math.min(window.innerHeight * (isCompact ? 0.48 : 0.58), isCompact ? 420 : 560);
        const sceneWidth = startWidth + ((window.innerWidth - startWidth) * widthEase);
        const sceneHeight = startHeight + ((window.innerHeight - startHeight) * heightEase);
        const sceneRadius = 38 * (1 - frameProgress);
        const copyOpacity = Math.max(0, 1 - (progress * 1.4));

        stack.style.width = `${sceneWidth}px`;
        stack.style.height = `${sceneHeight}px`;
        stack.style.left = '0px';
        stack.style.marginLeft = `${((hero?.clientWidth ?? window.innerWidth) - sceneWidth) / 2}px`;
        stack.querySelectorAll('.sheet').forEach((sheet) => {
            sheet.style.width = `${88 + (12 * widthEase)}%`;
            sheet.style.left = `${6 * (1 - widthEase)}%`;
        });
        stack.style.setProperty('--scene-top', `${window.innerHeight * (isCompact ? 0.12 : 0.14) * (1 - heightEase)}px`);
        stack.style.setProperty('--scene-y', '0px');
        stack.style.setProperty('--scene-scale', '1');
        stack.style.setProperty('--scene-tilt', '0deg');
        stack.style.setProperty('--scene-radius', `${sceneRadius}px`);
        stack.style.setProperty('--scene-yellow-top', `${-12 * (1 - widthEase)}px`);
        stack.style.setProperty('--scene-pink-top', `${-5 * (1 - widthEase)}px`);
        stack.style.setProperty('--scene-yellow-angle', `${-2.5 * (1 - widthEase)}deg`);
        stack.style.setProperty('--scene-pink-angle', `${-1.2 * (1 - widthEase)}deg`);
        stack.style.setProperty('--scene-violet-angle', `${-0.4 * (1 - widthEase)}deg`);
        stack.querySelector('.sheet-yellow')?.style.setProperty('opacity', `${1 - widthEase}`);
        stack.querySelector('.sheet-pink')?.style.setProperty('opacity', `${1 - widthEase}`);
        stack.style.setProperty('--art-scale', `${1 + (panProgress * 0.28)}`);
        stack.style.setProperty('--art-pan', `${-panProgress * 28}%`);
        hero?.style.setProperty('--scene-progress', `${progress}`);
        hero?.style.setProperty('--copy-opacity', `${copyOpacity}`);
        hero?.style.setProperty('--copy-y', `${-progress * 28}px`);
        ticking = false;
    };
    window.addEventListener('scroll', () => {
        if (!ticking) { requestAnimationFrame(update); ticking = true; }
    }, { passive: true });
    window.addEventListener('resize', () => {
        if (!ticking) { requestAnimationFrame(update); ticking = true; }
    }, { passive: true });
    update();
}
