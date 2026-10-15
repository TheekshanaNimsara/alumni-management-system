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
            bar.style.animation = 'loaderProgressExpand 0.45s cubic-bezier(0.25, 1, 0.5, 1) 0.05s forwards';
        }

        loader.classList.remove('loader-hidden');
        loader.setAttribute('aria-hidden', 'false');
        loader.style.pointerEvents = 'auto';

        // Guaranteed auto-dismissal failsafe (1.2s max)
        clearTimeout(window._alumniLoaderTimer);
        window._alumniLoaderTimer = setTimeout(() => {
            window.AlumniLoader.hide();
        }, 1200);
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
        // If loaded with a specific section hash, announce the section and smooth-scroll
        if (window.location.hash && window.location.hash.length > 1) {
            const targetEl = document.querySelector(window.location.hash);
            if (targetEl) {
                let sectionName = window.location.hash.replace('#', '');
                sectionName = sectionName.charAt(0).toUpperCase() + sectionName.slice(1);
                const statusEl = loader.querySelector('.loader-status');
                if (statusEl) statusEl.textContent = 'Connecting Alumni • ' + sectionName;
                setTimeout(() => {
                    targetEl.scrollIntoView({ behavior: 'smooth' });
                }, 120);
            }
        }

        // If loader is initially visible on page load, dismiss it swiftly & smoothly (300ms)
        if (!loader.classList.contains('loader-hidden')) {
            setTimeout(() => {
                window.AlumniLoader.hide();
            }, 300);
        }

        // Safety fallback: guaranteed dismissal within 600ms
        setTimeout(() => {
            window.AlumniLoader.hide();
        }, 600);

        // Handle browser back/forward cache (bfcache) restoration
        window.addEventListener('pageshow', (event) => {
            window.AlumniLoader.hide();
        });
    }

    // Double-submit prevention & inline button spinner for data forms (Jobs, Events, RSVPs, Reports)
    document.querySelectorAll('form').forEach(form => {
        if (form.classList.contains('auth-form') || (form.method && form.method.toUpperCase() === 'GET')) return;
        form.addEventListener('submit', function(e) {
            if (this.dataset.submitting === 'true') {
                e.preventDefault();
                return;
            }
            if (this.checkValidity && !this.checkValidity()) return;

            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.disabled) {
                this.dataset.submitting = 'true';
                const originalHtml = submitBtn.innerHTML;
                submitBtn.dataset.originalHtml = originalHtml;
                submitBtn.innerHTML = '<span class="btn-spinner"></span> Submitting...';
                submitBtn.style.opacity = '0.85';
                submitBtn.style.pointerEvents = 'none';

                setTimeout(() => {
                    if (this.dataset.submitting === 'true') {
                        this.dataset.submitting = 'false';
                        submitBtn.innerHTML = submitBtn.dataset.originalHtml || originalHtml;
                        submitBtn.style.opacity = '1';
                        submitBtn.style.pointerEvents = 'auto';
                    }
                }, 8000);
            }
        });
    });

    // ------------------------------------------------------------
    // INTERCEPT MAIN HEADER SECTIONS & MAJOR NAVIGATION LINKS
    // ------------------------------------------------------------
    document.addEventListener('click', (e) => {
        const link = e.target.closest('a');
        if (!link) return;

        // Skip modified or non-primary clicks (middle-click, ctrl+click, new tabs)
        if (e.ctrlKey || e.metaKey || e.shiftKey || e.altKey || e.button !== 0) return;
        if (link.target && link.target !== '_self') return;

        const href = link.getAttribute('href');
        if (!href || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) return;

        // Exclude micro-interactions (theme switchers, modal buttons, tabs, conversation items)
        if (link.closest('.theme-switcher') || link.closest('.modal-overlay') || link.closest('.modal-box') || link.hasAttribute('data-no-loader') || link.closest('[data-no-loader]') || link.closest('.conv-item') || link.classList.contains('tab')) return;

        // Logout Action
        if (href.includes('logout.php')) {
            window.AlumniLoader.show('Signing out securely...');
            return;
        }

        // Determine if this is a header/navbar link or a major section link
        const isHeaderLink = !!link.closest('.navbar, .site-header, header, .nav-menu, .site-nav, .nav-links');
        const isMajorLink = link.classList.contains('nav-link') || link.classList.contains('active-nav') || link.classList.contains('logo') || link.classList.contains('brand') || link.classList.contains('brand-link') || link.classList.contains('nav-user') || link.classList.contains('btn-return-home') || link.closest('.footer-links') || link.classList.contains('gold-glow');

        if (!isHeaderLink && !isMajorLink) return;

        // Derive user-friendly section/action label
        let label = link.textContent.trim().replace(/\s+/g, ' ');
        if (link.classList.contains('logo') || link.classList.contains('brand') || link.classList.contains('brand-link')) {
            label = 'Home';
        } else if (link.classList.contains('nav-user')) {
            label = 'Your Profile';
        } else if (link.classList.contains('btn-return-home')) {
            label = 'Homepage';
        }
        if (!label) label = 'Portal';

        // Check for in-page anchor navigation (e.g. #alumni, #events, #jobs, #about, #home)
        let isAnchor = false;
        let targetId = '';

        if (href.startsWith('#')) {
            isAnchor = true;
            targetId = href;
        } else {
            try {
                const dest = new URL(link.href, window.location.href);
                if (dest.origin === window.location.origin && dest.pathname === window.location.pathname) {
                    if (dest.hash) {
                        isAnchor = true;
                        targetId = dest.hash;
                    } else if (href.includes('index.html') || href === './' || href === '/') {
                        isAnchor = true;
                        targetId = '#home';
                    }
                }
            } catch (err) {}
        }

        if (isAnchor) {
            e.preventDefault();

            // Auto-close mobile drawer if open
            const navMenu = document.querySelector('.nav-menu');
            const navToggle = document.querySelector('.nav-toggle');
            if (navMenu && navMenu.classList.contains('open')) {
                navMenu.classList.remove('open');
                if (navToggle) navToggle.setAttribute('aria-expanded', 'false');
            }

            // Trigger full cinematic loader with section label
            window.AlumniLoader.show('Connecting Alumni • ' + label);

            // Snappy transition: scroll to target after initial progress expansion
            setTimeout(() => {
                if (targetId === '#home' || targetId === '' || targetId === '#') {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                } else {
                    const targetEl = document.querySelector(targetId);
                    if (targetEl) {
                        targetEl.scrollIntoView({ behavior: 'smooth' });
                    }
                }

                // Update active navigation state
                document.querySelectorAll('.nav-links .nav-link, .site-nav a, nav a').forEach(l => {
                    l.classList.remove('active', 'active-nav');
                });
                if (link.classList.contains('nav-link') || link.closest('.nav-links')) {
                    link.classList.add('active');
                }

                // Update hash in history without triggering browser jump
                if (history.pushState && targetId) {
                    history.pushState(null, '', targetId);
                }

                // Smoothly dismiss loader
                setTimeout(() => {
                    window.AlumniLoader.hide();
                }, 160);
            }, 240);
            return;
        }

        // Cross-page navigation: display loader immediately then proceed
        e.preventDefault();
        window.AlumniLoader.show('Connecting Alumni • ' + label);
        setTimeout(() => {
            window.location.href = link.href;
        }, 180);
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
