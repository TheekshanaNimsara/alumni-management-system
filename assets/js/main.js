/* ============================================================
   UNIVERSITY ALUMNI NETWORK - VANILLA JAVASCRIPT
   Cinematic Dark Theme, Smooth Loader, Hero & UI Animations
   Vanilla JS • No External Frameworks • Pure CSS3 & DOM APIs
   ============================================================ */

// ------------------------------------------------------------
// 1. FEATURE ONE: GLOBAL CINEMATIC LOADING SCREEN CONTROLLER
// ------------------------------------------------------------
window.AlumniLoader = {
    show: function(statusText) {
        const loader = document.getElementById('cinematicLoader');
        if (!loader) return;
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

        const statusEl = loader.querySelector('.loader-status');
        if (statusEl && statusText) {
            statusEl.textContent = statusText;
        }

        const bar = loader.querySelector('.loader-progress-bar');
        if (bar) {
            bar.style.animation = 'none';
            void bar.offsetWidth; // trigger reflow
            bar.style.animation = 'loaderProgressExpand 1.5s cubic-bezier(0.25, 1, 0.5, 1) 0.15s forwards';
        }

        loader.classList.remove('loader-hidden');
        loader.setAttribute('aria-hidden', 'false');
        loader.style.pointerEvents = 'auto';

        // Guaranteed auto-dismissal failsafe (3.8s max)
        clearTimeout(window._alumniLoaderTimer);
        window._alumniLoaderTimer = setTimeout(() => {
            window.AlumniLoader.hide();
        }, 3800);
    },
    hide: function() {
        const loader = document.getElementById('cinematicLoader');
        if (!loader) return;
        loader.classList.add('loader-hidden');
        loader.setAttribute('aria-hidden', 'true');
        loader.style.pointerEvents = 'none';
        clearTimeout(window._alumniLoaderTimer);
    }
};

document.addEventListener('DOMContentLoaded', () => {

    const loader = document.getElementById('cinematicLoader');

    if (loader) {
        // If loader is initially visible on page load, dismiss it smoothly
        if (!loader.classList.contains('loader-hidden')) {
            setTimeout(() => {
                window.AlumniLoader.hide();
            }, 800);
        }

        // Safety fallback: guaranteed dismissal within 2200ms
        setTimeout(() => {
            window.AlumniLoader.hide();
        }, 2200);

        // Handle browser back/forward cache (bfcache) restoration
        window.addEventListener('pageshow', (event) => {
            window.AlumniLoader.hide();
        });
    }

    // Intercept Major Section Navigation Links
    document.addEventListener('click', (e) => {
        const link = e.target.closest('a');
        if (!link) return;

        // Skip modified or non-primary clicks
        if (e.ctrlKey || e.metaKey || e.shiftKey || e.altKey || e.button !== 0) return;
        if (link.target && link.target !== '_self') return;

        const href = link.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) return;

        // Exclude micro-interactions (theme switchers, modal buttons, tabs, conversation items)
        if (link.closest('.theme-switcher') || link.closest('.modal-overlay') || link.closest('.modal-box') || link.hasAttribute('data-no-loader') || link.closest('[data-no-loader]') || link.closest('.conv-item')) return;

        // Logout Action
        if (href.includes('logout.php')) {
            window.AlumniLoader.show('Signing out securely...');
            return;
        }

        // Major Section Navigation (Navbar links, logo, user profile, return home)
        if (link.classList.contains('nav-link') || link.classList.contains('nav-user') || link.classList.contains('logo') || link.classList.contains('btn-return-home')) {
            try {
                const dest = new URL(link.href, window.location.href);
                if (dest.origin === window.location.origin && dest.pathname === window.location.pathname && dest.search === window.location.search) {
                    return; // Same page, do nothing
                }
            } catch (err) {}

            let label = link.textContent.trim();
            if (link.classList.contains('logo')) label = 'Alumni Network';
            if (link.classList.contains('nav-user')) label = 'Your Profile';
            if (link.classList.contains('btn-return-home')) label = 'Homepage';

            window.AlumniLoader.show('Connecting Alumni • ' + label);
        }
    });

    // Auth pages (login & register) handle their own dedicated Liquid Glass success animations
    // and inline validation states; do not trigger global full-screen cinematic loader here.

    // ------------------------------------------------------------
    // 2. FEATURE THREE (D): SCROLL REVEAL (INTERSECTION OBSERVER)
    // ------------------------------------------------------------
    const revealElements = document.querySelectorAll('.reveal, .reveal-stagger');
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (prefersReducedMotion) {
        // Reduced motion: reveal all sections immediately without animation
        revealElements.forEach(el => el.classList.add('show'));
    } else if ('IntersectionObserver' in window && revealElements.length > 0) {
        const revealObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('show');
                    // Stop observing once animated to save GPU/CPU cycles
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.12,
            rootMargin: '0px 0px -40px 0px'
        });

        revealElements.forEach(el => revealObserver.observe(el));
    } else {
        // Fallback for older browsers
        revealElements.forEach(el => el.classList.add('show'));
    }

    // ------------------------------------------------------------
    // 3. FEATURE THREE (A): NAVBAR SCROLL TRANSPARENCY & BLUR
    // ------------------------------------------------------------
    const navbar = document.querySelector('.navbar');
    if (navbar) {
        const handleScroll = () => {
            if (window.scrollY > 30) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        };

        window.addEventListener('scroll', handleScroll, { passive: true });
        handleScroll(); // Initial check
    }

    // ------------------------------------------------------------
    // 4. MOBILE NAVIGATION DRAWER TOGGLE
    // ------------------------------------------------------------
    const navToggle = document.querySelector('.nav-toggle');
    const navMenu = document.querySelector('.nav-menu');

    if (navToggle && navMenu) {
        navToggle.addEventListener('click', () => {
            const isOpen = navMenu.classList.toggle('open');
            navToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        // Close mobile drawer when clicking outside
        document.addEventListener('click', (e) => {
            if (navMenu.classList.contains('open') && !navMenu.contains(e.target) && !navToggle.contains(e.target)) {
                navMenu.classList.remove('open');
                navToggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // ------------------------------------------------------------
    // 5. FEATURE THREE (F): MODAL KEYBOARD ACCESSIBILITY (ESC KEY)
    // ------------------------------------------------------------
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' || event.key === 'Esc') {
            const openModals = document.querySelectorAll('.modal-overlay[style*="display: flex"], .modal-overlay[style*="display: block"]');
            openModals.forEach(m => {
                m.style.display = 'none';
            });
            if (typeof window.closeModals === 'function') {
                window.closeModals();
            }
        }
    });

    // Note: Auth forms (login & register) encapsulate their own validation,
    // inline spinners, error alerts, and Liquid Glass success animations.
    // main.js avoids attaching duplicate listeners to prevent handler collisions.

});
