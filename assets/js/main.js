/* ============================================================
   UNIVERSITY ALUMNI NETWORK - VANILLA JAVASCRIPT
   Clean, Readable & Beginner-Friendly Code
   ============================================================ */

document.addEventListener('DOMContentLoaded', () => {

    // ------------------------------------------------------------
    // 1. SIMPLE INTERSECTION OBSERVER SCROLL REVEAL
    // Animates elements with class .reveal when they enter view
    // ------------------------------------------------------------
    const revealElements = document.querySelectorAll('.reveal');

    if ('IntersectionObserver' in window && revealElements.length > 0) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('show');
                    // Stop observing once animated
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.15,
            rootMargin: '0px 0px -40px 0px'
        });

        revealElements.forEach(el => observer.observe(el));
    } else {
        // Fallback for older browsers
        revealElements.forEach(el => el.classList.add('show'));
    }

    // ------------------------------------------------------------
    // 2. NAVBAR SCROLL EFFECT
    // Adds .scrolled class on scroll for subtle transparency & shadow
    // ------------------------------------------------------------
    const navbar = document.querySelector('.navbar');
    if (navbar) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 40) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    }

    // ------------------------------------------------------------
    // 3. MOBILE NAVIGATION MENU TOGGLE
    // ------------------------------------------------------------
    const navToggle = document.querySelector('.nav-toggle');
    const navMenu = document.querySelector('.nav-menu');

    if (navToggle && navMenu) {
        navToggle.addEventListener('click', () => {
            navMenu.classList.toggle('open');
            const isExpanded = navMenu.classList.contains('open');
            navToggle.setAttribute('aria-expanded', isExpanded);
        });
    }

    // ------------------------------------------------------------
    // 4. CLIENT-SIDE REGISTRATION FORM VALIDATION
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
