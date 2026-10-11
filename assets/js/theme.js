/**
 * ============================================================
 * Alumni Network — macOS-Inspired Theme Switcher (Dark / Light / System)
 * Accessible • Smooth Transitions • Storage Persisted • Zero Dependencies
 * ============================================================
 */
(function () {
  'use strict';

  const STORAGE_KEY = 'alumni_theme_preference';
  const VALID_PREFERENCES = ['dark', 'light', 'system'];

  // Native SVG Icons for Segmented Control
  const ICONS = {
    dark: `<svg class="theme-icon" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>`,
    light: `<svg class="theme-icon" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"></circle><line x1="12" y1="2" x2="12" y2="4"></line><line x1="12" y1="20" x2="12" y2="22"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>`,
    system: `<svg class="theme-icon" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>`
  };

  /**
   * Safe storage access (handles private browsing / blocked storage)
   */
  function getSavedPreference() {
    try {
      const saved = localStorage.getItem(STORAGE_KEY);
      if (saved && VALID_PREFERENCES.includes(saved)) {
        return saved;
      }
    } catch (e) {
      // Storage unavailable, fallback to system
    }
    return 'system';
  }

  function savePreference(pref) {
    try {
      localStorage.setItem(STORAGE_KEY, pref);
    } catch (e) {
      // Fail safely if quota exceeded or disabled
    }
  }

  /**
   * Determine OS-level preference
   */
  function getSystemScheme() {
    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches) {
      return 'light';
    }
    return 'dark';
  }

  /**
   * Resolve effective appearance (dark or light)
   */
  function resolveTheme(pref) {
    if (pref === 'system') {
      return getSystemScheme();
    }
    return pref;
  }

  let transitionTimer = null;

  /**
   * Apply theme to DOM and sync controls
   */
  function applyTheme(preference, enableTransition = true) {
    const effective = resolveTheme(preference);
    const root = document.documentElement;

    if (enableTransition) {
      root.classList.add('theme-transitioning');
      clearTimeout(transitionTimer);
      transitionTimer = setTimeout(() => {
        root.classList.remove('theme-transitioning');
      }, 350);
    }

    root.setAttribute('data-theme', effective);
    root.setAttribute('data-theme-preference', preference);

    // Update all theme switchers in DOM
    document.querySelectorAll('.theme-switcher').forEach((switcher) => {
      switcher.querySelectorAll('.theme-option').forEach((btn) => {
        const val = btn.getAttribute('data-theme-value');
        const isSelected = (val === preference);
        btn.setAttribute('aria-checked', isSelected ? 'true' : 'false');
        btn.classList.toggle('active', isSelected);
      });
    });

    // Announce to assist screen readers and external listeners
    window.dispatchEvent(
      new CustomEvent('alumni-theme-change', {
        detail: { preference, resolved: effective }
      })
    );
  }

  /**
   * Set user preference and persist
   */
  function setPreference(pref) {
    if (!VALID_PREFERENCES.includes(pref)) return;
    savePreference(pref);
    applyTheme(pref, true);
  }

  /**
   * Create standard HTML string for segmented control
   */
  function buildSwitcherHtml() {
    return `
      <div class="theme-switcher" role="radiogroup" aria-label="Select appearance theme">
        <button type="button" class="theme-option" data-theme-value="dark" role="radio" aria-checked="false" title="Dark theme (always dark)">
          ${ICONS.dark}
          <span class="theme-label">Dark</span>
        </button>
        <button type="button" class="theme-option" data-theme-value="light" role="radio" aria-checked="false" title="Light theme (always light)">
          ${ICONS.light}
          <span class="theme-label">Light</span>
        </button>
        <button type="button" class="theme-option" data-theme-value="system" role="radio" aria-checked="false" title="System theme (syncs with OS)">
          ${ICONS.system}
          <span class="theme-label">Auto</span>
        </button>
      </div>
    `.trim();
  }

  /**
   * Attach interaction events to a theme switcher element
   */
  function bindSwitcherEvents(switcher) {
    if (switcher.dataset.bound === 'true') return;
    switcher.dataset.bound = 'true';

    const options = Array.from(switcher.querySelectorAll('.theme-option'));

    options.forEach((btn, index) => {
      // Click handler
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        const val = btn.getAttribute('data-theme-value');
        if (val) setPreference(val);
      });

      // Keyboard navigation (Arrow keys, Home, End)
      btn.addEventListener('keydown', (e) => {
        let targetIndex = -1;
        if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
          targetIndex = (index + 1) % options.length;
        } else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
          targetIndex = (index - 1 + options.length) % options.length;
        } else if (e.key === 'Home') {
          targetIndex = 0;
        } else if (e.key === 'End') {
          targetIndex = options.length - 1;
        }

        if (targetIndex !== -1) {
          e.preventDefault();
          const targetBtn = options[targetIndex];
          targetBtn.focus();
          const val = targetBtn.getAttribute('data-theme-value');
          if (val) setPreference(val);
        }
      });
    });
  }

  /**
   * Ensure theme switcher exists in header navigation
   */
  function ensureSwitcherExists() {
    // If already in DOM, bind all instances
    const existing = document.querySelectorAll('.theme-switcher');
    if (existing.length > 0) {
      existing.forEach(bindSwitcherEvents);
      return;
    }

    // Auto-mount container target search
    const targets = [
      document.querySelector('.navbar .nav-actions'),
      document.querySelector('.navbar .nav-auth'),
      document.querySelector('.navbar nav'),
      document.querySelector('.site-header .header-inner nav'),
      document.querySelector('.site-header .site-nav'),
      document.querySelector('.auth-nav'),
      document.querySelector('.admin-header'),
      document.querySelector('.navbar-container')
    ];

    for (const target of targets) {
      if (target) {
        const temp = document.createElement('div');
        temp.innerHTML = buildSwitcherHtml();
        const switcher = temp.firstElementChild;
        target.prepend(switcher);
        bindSwitcherEvents(switcher);
        break;
      }
    }
  }

  /**
   * Listen for live operating system theme changes
   */
  if (window.matchMedia) {
    const mql = window.matchMedia('(prefers-color-scheme: light)');
    const handleSystemChange = () => {
      const currentPref = getSavedPreference();
      if (currentPref === 'system') {
        // Only react if system mode is currently selected
        applyTheme('system', true);
      }
    };

    if (mql.addEventListener) {
      mql.addEventListener('change', handleSystemChange);
    } else if (mql.addListener) {
      mql.addListener(handleSystemChange);
    }
  }

  // Initial setup immediately upon script load
  const initialPref = getSavedPreference();
  applyTheme(initialPref, false);

  // Setup DOM elements when ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
      ensureSwitcherExists();
      applyTheme(getSavedPreference(), false);
    });
  } else {
    ensureSwitcherExists();
    applyTheme(initialPref, false);
  }

  // Public API for programmatic integration and testing
  window.AlumniTheme = {
    getPreference: getSavedPreference,
    getTheme: () => document.documentElement.getAttribute('data-theme'),
    setPreference: setPreference,
    renderSwitcher: buildSwitcherHtml
  };
})();
