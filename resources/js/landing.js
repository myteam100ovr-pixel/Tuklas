import './theme.js';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import Lenis from 'lenis';

gsap.registerPlugin(ScrollTrigger);

const $ = (s, r = document) => r.querySelector(s);
const $$ = (s, r = document) => [...r.querySelectorAll(s)];
const root = document.documentElement;

const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
const narrowMq = matchMedia('(max-width: 900px)');
const noPin = reduce || narrowMq.matches;
if (noPin) root.classList.add('no-pin');
if (reduce) root.classList.add('no-motion');

// Crossing the breakpoint changes the whole scroll model, so start clean.
narrowMq.addEventListener('change', () => location.reload());

/* ------------------------------------------------------------------ */
/* Smooth scrolling (Lenis) synced with ScrollTrigger                  */
/* ------------------------------------------------------------------ */
let lenis = null;
if (!reduce) {
    lenis = new Lenis({ lerp: 0.1, smoothWheel: true });
    lenis.on('scroll', ScrollTrigger.update);
    gsap.ticker.add((t) => lenis.raf(t * 1000));
    gsap.ticker.lagSmoothing(0);
}

/* ------------------------------------------------------------------ */
/* Header: mobile menu, anchor links, light/dark theme                 */
/* ------------------------------------------------------------------ */
const hdr = $('#hdr');
const burger = $('.burger');

function setMenu(open) {
    hdr.classList.toggle('open', open);
    burger.setAttribute('aria-expanded', String(open));
    burger.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
}
burger.addEventListener('click', () => setMenu(!hdr.classList.contains('open')));
document.addEventListener('keydown', (e) => { if (e.key === 'Escape') setMenu(false); });

document.addEventListener('click', (e) => {
    const a = e.target.closest('a[href^="#"]');
    if (!a) return;
    const id = a.getAttribute('href');
    const target = id === '#top' ? 0 : $(id);
    if (target === null || target === undefined) return;
    e.preventDefault();
    setMenu(false);
    if (lenis) lenis.scrollTo(target, { duration: 1.4 });
    else if (target === 0) window.scrollTo({ top: 0 });
    else target.scrollIntoView();
});

// Set once the hero timeline exists. The header only turns dark after the purple
// card has finished growing to the screen edges (the timeline reaches 1.3).
let heroTl = null;

function updateNav() {
    let dark = false;
    if (!noPin && heroTl) {
        const filled = heroTl.time() >= 1.299;
        const anTop = $('#features').getBoundingClientRect().top;
        dark = filled && anTop > 64;
    }
    hdr.classList.toggle('dark', dark);
}

/* ------------------------------------------------------------------ */
/* FAQ accordion                                                       */
/* ------------------------------------------------------------------ */
$$('.q button').forEach((btn) => {
    btn.addEventListener('click', () => {
        const open = btn.getAttribute('aria-expanded') === 'true';
        $$('.q button').forEach((b) => b.setAttribute('aria-expanded', 'false'));
        btn.setAttribute('aria-expanded', String(!open));
        setTimeout(() => ScrollTrigger.refresh(), 450);
    });
});

/* ------------------------------------------------------------------ */
/* Analytics-style preview: tabs + counters (shared state)             */
/* ------------------------------------------------------------------ */
const tabBtns = $$('.tabs button');
const thumb = $('.tab-thumb');
const cA = $('[data-count="a"]');
const cB = $('[data-count="b"]');
const cM1 = $('[data-count="m1"]');
const cM2 = $('[data-count="m2"]');
let tabShift = 1;
let lastP = 0;

function placeThumb() {
    const on = $('.tabs button.on');
    if (!on) return;
    thumb.style.width = on.offsetWidth + 'px';
    thumb.style.transform = `translateX(${on.offsetLeft}px)`;
}
function updateCounts(p) {
    lastP = p;
    const a = Math.round(24 + p * 46) + tabShift * 2;
    const b = Math.round(46 + p * 27) + tabShift;
    cA.textContent = '+' + a;
    cB.textContent = '+' + b;
    cM1.textContent = '+' + Math.round(a * 0.72);
    cM2.textContent = '+' + Math.round(b * 0.84);
}
tabBtns.forEach((btn, i) => {
    btn.addEventListener('click', () => {
        tabBtns.forEach((b) => { b.classList.remove('on'); b.setAttribute('aria-selected', 'false'); });
        btn.classList.add('on');
        btn.setAttribute('aria-selected', 'true');
        tabShift = i;
        placeThumb();
        updateCounts(lastP);
    });
});
placeThumb();
addEventListener('resize', placeThumb);

/* ------------------------------------------------------------------ */
/* Scroll choreography                                                 */
/* ------------------------------------------------------------------ */
if (!reduce) {
    /* ---- Hero intro (on load) ---- */
    gsap.from('.hl', { y: 28, opacity: 0, duration: 0.9, stagger: 0.09, ease: 'power3.out', delay: 0.1 });
    gsap.from('.hero-cta', { y: 20, opacity: 0, duration: 0.8, ease: 'power3.out', delay: 0.35 });
    gsap.from('.stack', { y: 90, opacity: 0, duration: 1.2, ease: 'power3.out', delay: 0.2 });

    /* ---- Statement: masked line-by-line reveal ---- */
    $$('.ln > span').forEach((s) => {
        gsap.fromTo(s, { yPercent: 115 }, {
            yPercent: 0, ease: 'none',
            scrollTrigger: { trigger: s.parentElement, start: 'top 94%', end: 'top 64%', scrub: 0.4 },
        });
    });

    /* ---- Stat rows: count-up, tile grows, tag drops in ---- */
    $$('.row').forEach((row) => {
        const num = $('.num', row);
        const parts = num.dataset.to.split(',').map(Number);
        const sep = num.dataset.sep || '';
        const write = (k) => { num.textContent = parts.map((t) => Math.round(t * k)).join(sep); };
        const o = { k: 0 };
        const trig = { trigger: row, start: 'top 94%', end: 'top 40%', scrub: 0.4 };
        write(0);
        gsap.to(o, { k: 1, ease: 'none', scrollTrigger: trig, onUpdate: () => write(o.k) });
        gsap.fromTo($('.tileimg', row), { scale: 0.55 }, { scale: 1, ease: 'none', scrollTrigger: trig });
        gsap.fromTo($('.tag', row), { y: 16, opacity: 0 }, { y: 0, opacity: 1, ease: 'none', scrollTrigger: trig });
    });

    /* ---- Gemini scan: beam sweeps the document, details appear ---- */
    const demo = $('.scan-demo');
    if (demo) {
        demo.classList.add('anim');
        const doc = $('.doc', demo);
        const scanTl = gsap.timeline({
            defaults: { ease: 'none' },
            scrollTrigger: { trigger: '.scan-card', start: 'top 78%', end: 'bottom 52%', scrub: 0.5, invalidateOnRefresh: true },
        });
        scanTl
            .fromTo('.beam', { y: 0 }, { y: () => doc.offsetHeight + $('.beam').offsetHeight, duration: 1 }, 0)
            .fromTo('.dl', { backgroundColor: '#ececf1' }, { backgroundColor: '#c9bdfb', stagger: 0.06, duration: 0.25 }, 0.05)
            .fromTo('.fd', { opacity: 0, y: 18 }, { opacity: 1, y: 0, stagger: 0.22, duration: 0.3 }, 0.15);
    }

    /* ---- Section titles ---- */
    $$('.how .title, .faq-s .title').forEach((t) => {
        gsap.from(t, { y: 36, opacity: 0, duration: 0.9, ease: 'power3.out', scrollTrigger: { trigger: t, start: 'top 88%' } });
    });

    /* ---- Parallax columns ---- */
    $$('.col').forEach((col) => {
        const sp = Number(col.dataset.speed) * (narrowMq.matches ? 0.35 : 1);
        gsap.fromTo(col, { y: sp }, {
            y: -sp * 0.5, ease: 'none',
            scrollTrigger: { trigger: '.cols', start: 'top bottom', end: 'bottom top', scrub: 0.6 },
        });
    });

    /* ---- FAQ reveal ---- */
    gsap.from('.q', {
        opacity: 0.12, y: 16, duration: 0.7, stagger: 0.1, ease: 'power2.out',
        scrollTrigger: { trigger: '.faq', start: 'top 86%', toggleActions: 'play none none reverse' },
    });

    /* ---- CTA banner ---- */
    gsap.fromTo('.cta', { scale: 0.9 }, {
        scale: 1, ease: 'none',
        scrollTrigger: { trigger: '.cta', start: 'top 100%', end: 'top 45%', scrub: 0.5 },
    });
    gsap.fromTo('.cta-art', { yPercent: 28 }, {
        yPercent: 0, ease: 'none',
        scrollTrigger: { trigger: '.cta', start: 'top 90%', end: 'top 25%', scrub: 0.5 },
    });
}

if (!noPin) {
    /* ---- 1. Hero: copy collapses, stack straightens, card goes full-bleed ---- */
    const copy = $('.hero-copy');
    const stack = $('.stack');
    const card = $('.card');
    const sy = $('.sheet-y');
    const sp = $('.sheet-p');
    const art = $('.art');

    const hero = gsap.timeline({
        defaults: { ease: 'none' },
        onUpdate: updateNav,
        scrollTrigger: { trigger: '#hero', start: 'top top', end: 'bottom bottom', scrub: 0.5, invalidateOnRefresh: true },
    });
    hero
        .fromTo(copy, { height: () => { copy.style.height = ''; return copy.scrollHeight; } }, { height: 0, duration: 0.7 }, 0)
        .to(stack, { marginTop: 0, duration: 0.7 }, 0)
        .to(card, { rotation: 0, duration: 0.7 }, 0)
        .to(sp, { rotation: 0, x: 0, y: 0, duration: 0.7 }, 0)
        .to(sy, { rotation: 0, x: 0, y: 0, duration: 0.7 }, 0)
        .to(stack, { marginLeft: 0, marginRight: 0, duration: 0.6 }, 0.7)
        .to([card, sy, sp], { borderTopLeftRadius: 0, borderTopRightRadius: 0, duration: 0.6 }, 0.7)
        .to([sy, sp], { opacity: 0, duration: 0.2 }, 0.9)
        .to(art, { y: () => -(window.innerWidth * 2 - window.innerHeight), duration: 2.4 }, 0.6)
        .to({}, { duration: 1 }, 3);
    heroTl = hero;

    /* ---- 2. Pinned block: three crossfading states + scroll-driven counters ---- */
    const views = $$('.lc-view');
    const segs = $$('.seg b');
    const chips = $$('.chip');
    const panelH = () => ($('.chips')?.offsetHeight || 260);
    let state = 0;

    gsap.set(chips, { opacity: 0 });

    function dropChips() {
        gsap.killTweensOf(chips);
        gsap.fromTo(chips, {
            y: () => -(panelH() + 80),
            opacity: 0,
            rotation: (i, el) => parseFloat(el.style.getPropertyValue('--r')) + gsap.utils.random(-28, 28),
        }, {
            y: 0, opacity: 1,
            rotation: (i, el) => parseFloat(el.style.getPropertyValue('--r')),
            duration: 1.05, ease: 'bounce.out',
            stagger: { each: 0.07, from: 'random' },
        });
    }
    function drawCurve() {
        gsap.fromTo('.curve svg', { clipPath: 'inset(0 100% 0 0)' }, { clipPath: 'inset(0 0% 0 0)', duration: 1.3, ease: 'power2.out' });
        gsap.fromTo(['.curve .dot', '.curve .tip'], { opacity: 0 }, { opacity: 1, duration: 0.4, delay: 0.7 });
    }
    function setState(i) {
        if (i === state) return;
        state = i;
        views.forEach((v, k) => v.classList.toggle('is-on', k === i));
        if (i === 0) dropChips();
        if (i === 2) drawCurve();
    }

    ScrollTrigger.create({ trigger: '.an', start: 'top 78%', once: true, onEnter: dropChips });
    ScrollTrigger.create({
        trigger: '.an-run', start: 'top top', end: 'bottom bottom',
        onUpdate: (self) => {
            const p = self.progress;
            setState(Math.min(2, Math.floor(p * 3)));
            segs.forEach((b, i) => { b.style.width = Math.max(0, Math.min(1, p * 3 - i)) * 100 + '%'; });
            updateCounts(p);
        },
    });

    /* ---- 3. Pathways: title + ticker pill, then staggered cards rise ---- */
    const cards = $$('.pc');
    const pw = gsap.timeline({
        defaults: { ease: 'none' },
        scrollTrigger: { trigger: '.pw-run', start: 'top top', end: 'bottom bottom', scrub: 0.5, invalidateOnRefresh: true },
    });
    pw
        .fromTo('.tick', { width: '0.3em' }, { width: '2.6em', duration: 0.25 }, 0.02)
        .fromTo('.tick-in', { xPercent: 0 }, { xPercent: -62, duration: 0.9 }, 0)
        .fromTo('.float-ic', { scale: 0.25, x: () => -window.innerWidth * 0.3, y: () => -window.innerHeight * 0.22, rotation: -40, opacity: 0 },
            { scale: 1, x: 0, y: 0, rotation: 0, opacity: 1, duration: 0.3 }, 0.05)
        .to('.pw-title', { y: () => -window.innerHeight * 0.62, duration: 0.5 }, 0.35);
    cards.forEach((c, i) => {
        pw.fromTo(c, { y: () => window.innerHeight * (1.0 + i * 0.07) }, { y: 0, duration: 0.5 + i * 0.05, ease: 'power1.out' }, 0.3);
    });
    pw.to({}, { duration: 0.15 }, 0.85);

    /* ---- Nav theme follows scroll ---- */
    ScrollTrigger.create({ start: 0, end: 'max', onUpdate: updateNav });
    updateNav();
    updateCounts(0);
}

/* ------------------------------------------------------------------ */
/* Tech stack: duplicate the list once so the loop has no gap          */
/* ------------------------------------------------------------------ */
if (!reduce) {
    $$('.marq-track').forEach((track) => {
        const clone = track.firstElementChild.cloneNode(true);
        clone.setAttribute('aria-hidden', 'true');
        track.appendChild(clone);
    });
}

/* Fonts change line breaks, so measure again once they are in. */
if (document.fonts && document.fonts.ready) {
    document.fonts.ready.then(() => { placeThumb(); ScrollTrigger.refresh(); });
}
addEventListener('load', () => ScrollTrigger.refresh());
