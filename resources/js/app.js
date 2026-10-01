import './theme.js';
import './document-scanner.js';
import './career-chat.js';

const authCarousel = document.querySelector('[data-auth-carousel]');

if (authCarousel) {
    const slides = Array.from(authCarousel.querySelectorAll('[data-feature-slide]'));
    const indicators = Array.from(document.querySelectorAll('[data-feature-go]'));
    const pauseButton = document.querySelector('[data-feature-toggle]');
    const pauseIcon = pauseButton?.querySelector('[data-toggle-icon]');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    let activeSlide = 0;
    let userPaused = reducedMotion.matches;
    let pointerInside = false;
    let focusInside = false;
    let rotationTimer;

    const updatePauseButton = () => {
        if (!pauseButton) {
            return;
        }

        if (reducedMotion.matches) {
            pauseButton.hidden = true;
            return;
        }

        pauseButton.hidden = false;

        const isPaused = userPaused || reducedMotion.matches;

        pauseButton.setAttribute('aria-pressed', String(isPaused));
        pauseButton.setAttribute('aria-label', isPaused ? 'Resume feature rotation' : 'Pause feature rotation');

        if (pauseIcon) {
            pauseIcon.textContent = isPaused ? '▶' : 'Ⅱ';
        }
    };

    const showSlide = (nextSlide) => {
        activeSlide = (nextSlide + slides.length) % slides.length;

        slides.forEach((slide, index) => {
            const isActive = index === activeSlide;

            slide.classList.toggle('is-active', isActive);
            slide.setAttribute('aria-hidden', String(!isActive));
        });

        indicators.forEach((indicator, index) => {
            indicator.setAttribute('aria-pressed', String(index === activeSlide));
        });
    };

    const scheduleRotation = () => {
        window.clearInterval(rotationTimer);

        if (userPaused || reducedMotion.matches || pointerInside || focusInside || document.hidden || slides.length < 2) {
            return;
        }

        rotationTimer = window.setInterval(() => showSlide(activeSlide + 1), 6200);
    };

    indicators.forEach((indicator) => {
        indicator.addEventListener('click', () => {
            showSlide(Number(indicator.dataset.featureGo));
            scheduleRotation();
        });
    });

    pauseButton?.addEventListener('click', () => {
        userPaused = !userPaused;
        updatePauseButton();
        scheduleRotation();
    });

    authCarousel.addEventListener('pointerenter', () => {
        pointerInside = true;
        scheduleRotation();
    });

    authCarousel.addEventListener('pointerleave', () => {
        pointerInside = false;
        scheduleRotation();
    });

    authCarousel.addEventListener('focusin', () => {
        focusInside = true;
        scheduleRotation();
    });

    authCarousel.addEventListener('focusout', (event) => {
        if (!authCarousel.contains(event.relatedTarget)) {
            focusInside = false;
            scheduleRotation();
        }
    });

    document.addEventListener('visibilitychange', scheduleRotation);
    reducedMotion.addEventListener('change', () => {
        updatePauseButton();
        scheduleRotation();
    });

    updatePauseButton();
    scheduleRotation();
}
