/* ============================================================
   UNIVERSITY ALUMNI NETWORK - VANILLA JAVASCRIPT
   Cinematic Dark Theme, Smooth Loader, Hero & UI Animations
   Vanilla JS • No External Frameworks • Pure CSS3 & DOM APIs
   ============================================================ */

document.addEventListener('DOMContentLoaded', () => {

    // ------------------------------------------------------------
    // 1. FEATURE ONE: CINEMATIC LOADING SCREEN CONTROLLER
    // ------------------------------------------------------------
    const loader = document.getElementById('cinematicLoader');

    if (loader) {
        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const hasLoadedBefore = sessionStorage.getItem('kdu_cinematic_loader_shown');

        const dismissLoader = (delay = 0) => {
            setTimeout(() => {
                loader.classList.add('loader-hidden');
                loader.setAttribute('aria-hidden', 'true');
                // Allow page interactions immediately
                loader.style.pointerEvents = 'none';
            }, delay);
        };

        if (prefersReducedMotion) {
            // Bypass animation immediately for reduced motion preference
            dismissLoader(0);
        } else {
            // Crisp, cinematic loader runs quickly (~900ms + smooth 0.35s fade)
            dismissLoader(900);
        }

        // Safety fallback: guaranteed dismissal within 1800ms regardless of assets
        setTimeout(() => {
            if (!loader.classList.contains('loader-hidden')) {
                loader.classList.add('loader-hidden');
                loader.style.pointerEvents = 'none';
            }
        }, 1800);

        // Handle browser back/forward cache (bfcache) restoration
        window.addEventListener('pageshow', (event) => {
            if (event.persisted) {
                loader.classList.add('loader-hidden');
                loader.style.pointerEvents = 'none';
            }
        });
    }

    // ------------------------------------------------------------
    // 2. FEATURE THREE (D): SCROLL REVEAL (INTERSECTION OBSERVER)
    // ------------------------------------------------------------
    const revealElements = document.querySelectorAll('.reveal');
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

    // ------------------------------------------------------------
    // 6. CLIENT-SIDE REGISTRATION FORM VALIDATION
    // ------------------------------------------------------------
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        const passwordInput = document.getElementById('reg_password');
        const confirmInput = document.getElementById('reg_confirm_password');
        const errorAlert = document.getElementById('registerErrorAlert');

        registerForm.addEventListener('submit', (event) => {
            const password = passwordInput ? passwordInput.value : '';
            const confirm = confirmInput ? confirmInput.value : '';

            // Password length check
            if (password.length < 8) {
                event.preventDefault();
                showError('Password must be at least 8 characters long.');
                if (passwordInput) passwordInput.focus();
                return;
            }

            // Matching passwords check
            if (password !== confirm) {
                event.preventDefault();
                showError('Passwords do not match. Please re-enter carefully.');
                if (confirmInput) confirmInput.focus();
                return;
            }
        });

        function showError(message) {
            if (errorAlert) {
                errorAlert.textContent = message;
                errorAlert.style.display = 'block';
            } else {
                alert(message);
            }
        }
    }

});
