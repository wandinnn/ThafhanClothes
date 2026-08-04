let revealObserver;
let countdownObservers = [];
let badgeObservers = [];
let removeScrollListener = () => {};

function replayClass(element, className) {
    element.classList.remove(className);
    void element.offsetWidth;
    element.classList.add(className);
}

function initializeStorefrontMotion() {
    revealObserver?.disconnect();
    countdownObservers.forEach((observer) => observer.disconnect());
    badgeObservers.forEach((observer) => observer.disconnect());
    removeScrollListener();

    countdownObservers = [];
    badgeObservers = [];

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const revealElements = document.querySelectorAll('[data-reveal]');

    document.documentElement.classList.toggle('motion-ready', !reducedMotion);

    if (reducedMotion || !('IntersectionObserver' in window)) {
        revealElements.forEach((element) => element.classList.add('is-visible'));
    } else {
        revealObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return;
                }

                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            });
        }, {
            rootMargin: '0px 0px -8% 0px',
            threshold: 0.12,
        });

        revealElements.forEach((element) => revealObserver.observe(element));
    }

    document.querySelectorAll('.countdown-value').forEach((element) => {
        const observer = new MutationObserver(() => {
            if (!reducedMotion) {
                replayClass(element, 'is-flipping');
            }
        });

        observer.observe(element, { childList: true, characterData: true, subtree: true });
        countdownObservers.push(observer);
    });

    document.querySelectorAll('[data-badge-wrapper]').forEach((wrapper) => {
        const observer = new MutationObserver(() => {
            const badge = wrapper.querySelector('.badge-count');

            if (badge && !reducedMotion) {
                replayClass(badge, 'is-popping');
                replayClass(wrapper, 'is-celebrating');
            }
        });

        observer.observe(wrapper, { childList: true, characterData: true, subtree: true });
        badgeObservers.push(observer);
    });

    const header = document.querySelector('[data-site-header]');

    if (header) {
        const updateHeader = () => header.classList.toggle('is-scrolled', window.scrollY > 24);

        updateHeader();
        window.addEventListener('scroll', updateHeader, { passive: true });
        removeScrollListener = () => window.removeEventListener('scroll', updateHeader);
    } else {
        removeScrollListener = () => {};
    }
}

document.addEventListener('livewire:navigated', initializeStorefrontMotion);
