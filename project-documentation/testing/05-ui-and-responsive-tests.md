# 05 — UI, Theme System, and Responsive Test Cases

**Project:** University Alumni Network  
**Test Suite:** SUITE-UI  
**Target Architecture:** Apple/macOS-Inspired Liquid Glass UI, CSS Custom Properties Theme System (`theme.css`, `theme.js`), Responsive Flexbox/Grid  
**Execution Environment:** Both Local XAMPP and Static GitHub Pages (Cross-Browser)  

---

## 1. Theme Switcher System Test Cases

### UI-001: Dark Theme Visual Presentation & Color Tokens

| Field | Details |
| :--- | :--- |
| **Test ID** | UI-001 |
| **Feature** | Default Dark-Purple Theme appearance and token validation |
| **Prerequisites** | Website open in browser (`http://localhost/alumni-network/` or GitHub Pages) |
| **Steps** | 1. In the theme switcher pill (header), click the **Dark** option.<br>2. Inspect `<html>` element in DevTools Elements tab.<br>3. Inspect computed CSS styles on `body`, `.job-card`, and buttons. |
| **Expected result** | 1. `<html>` tag possesses `data-theme="dark"` and `data-theme-preference="dark"`.<br>2. Dark theme active: background displays deep purple gradient (`#0A0612` / `#160B29`).<br>3. Text displays crisp high-contrast white / lavender (`#F3EEFA` / `#C4B5FD`).<br>4. Glass surfaces display dark translucent purple with blur (`rgba(22, 12, 38, 0.72)`).<br>5. Buttons display rich purple ambient glow (`rgba(124, 58, 237, 0.35)`). |
| **Actual result** | |
| **Status** | Not Tested |
| **Notes** | |

---

### UI-002: Light Theme Visual Presentation & Contrast Verification

| Field | Details |
| :--- | :--- |
| **Test ID** | UI-002 |
| **Feature** | Coordinated Purple Light Theme appearance and readability |
| **Prerequisites** | Website open in browser |
| **Steps** | 1. In the theme switcher pill, click the **Light** option.<br>2. Inspect `<html>` attributes.<br>3. Inspect page background, cards, tables, input fields, and text readability.<br>4. Check contrast ratios using DevTools Accessibility color picker. |
| **Expected result** | 1. `<html>` tag updates to `data-theme="light"` and `data-theme-preference="light"`.<br>2. Page background shifts to clean light lavender/purple hue (`#F7F5FC`).<br>3. Cards render clean frosted white/lavender glass (`rgba(255, 255, 255, 0.85)`).<br>4. Text is dark and easily legible (`#211632` and `#625773`), meeting WCAG AA contrast (>= 4.5:1).<br>5. Borders, buttons, and purple badges maintain brand identity without harsh contrast breaks. |
| **Actual result** | |
| **Status** | Not Tested |
| **Notes** | |

---

### UI-003: System Theme Auto-Sync with OS Appearance

| Field | Details |
| :--- | :--- |
| **Test ID** | UI-003 |
| **Feature** | Real-time OS color scheme synchronization (`prefers-color-scheme`) |
| **Prerequisites** | Windows / macOS operating system settings accessible |
| **Steps** | 1. In the theme switcher pill, click **Auto** / **System**.<br>2. Observe `<html>` attribute `data-theme-preference="system"`.<br>3. Open Windows Settings -> Personalization -> Colors -> change "Choose your mode" from Dark to Light.<br>4. Switch back to Dark.<br>5. Observe browser window without refreshing the page. |
| **Expected result** | 1. When System is selected, `matchMedia('(prefers-color-scheme: dark)')` listener is active.<br>2. Changing OS mode from Dark to Light instantly switches the website to Light mode.<br>3. Changing OS mode from Light to Dark instantly switches the website to Dark mode.<br>4. Transitions animate smoothly without screen tearing or visual glitches. |
| **Actual result** | |
| **Status** | Not Tested |
| **Notes** | |

---

### UI-004: Theme Preference Persistence & Zero-FOUC Reload

| Field | Details |
| :--- | :--- |
| **Test ID** | UI-004 |
| **Feature** | Theme preference storage in `localStorage` and Flash of Unstyled Content prevention |
| **Prerequisites** | Website open in browser |
| **Steps** | 1. Select **Light** theme.<br>2. Open DevTools Application -> Local Storage -> verify key `alumni_theme_preference` equals `'light'`.<br>3. Navigate between `index.html`, `jobs.html`, `event.html`, and `login.html`.<br>4. Perform hard refresh (`Ctrl + F5`) on each page.<br>5. Observe whether any dark-theme flash occurs before the page paints. |
| **Expected result** | 1. Theme persists across all internal pages automatically.<br>2. Zero-FOUC script in `<head>` executes synchronously prior to DOM rendering, preventing white/dark flickering on reload.<br>3. The active theme option in the switcher pill correctly highlights the active choice. |
| **Actual result** | |
| **Status** | Not Tested |
| **Notes** | |

---

## 2. Apple/macOS Liquid Glass UI & Visual Enhancements

### UI-005: Liquid Glass Button Hover, Sheen & Ambient Glow

| Field | Details |
| :--- | :--- |
| **Test ID** | UI-005 |
| **Feature** | Apple/macOS liquid glass button styling and micro-interactions |
| **Prerequisites** | Website loaded in modern browser (Chrome, Edge, Firefox, Safari) |
| **Steps** | 1. Navigate to `jobs.html` or `index.html`.<br>2. Locate primary buttons (e.g., "+ Post a Job", "View & Apply", "Return to Homepage").<br>3. Move mouse cursor over the button.<br>4. Press and hold mouse down (active state), then release.<br>5. Inspect CSS pseudo-elements `::before` and `::after` in DevTools. |
| **Expected result** | 1. Buttons display frosted translucent glass texture with subtle specular rim highlight.<br>2. Hovering smoothly increases purple ambient glow (`box-shadow`) and triggers subtle highlight sheen animation.<br>3. Active press slightly scales button down (`scale(0.98)` or `translateY(1px)`) providing haptic visual feedback.<br>4. Backdrop blur (`backdrop-filter: blur(12px)`) renders smoothly behind translucent surfaces. |
| **Actual result** | |
| **Status** | Not Tested |
| **Notes** | |

---

### UI-006: Liquid Glass Heading Effect on "All Opportunities"

| Field | Details |
| :--- | :--- |
| **Test ID** | UI-006 |
| **Feature** | Liquid glass styling applied to main section heading |
| **Prerequisites** | Open `jobs.html` |
| **Steps** | 1. Inspect the "All Opportunities" section header on `jobs.html`.<br>2. Observe glass card backing, borders, gradient text, and responsive wrapping. |
| **Expected result** | 1. Heading is styled with liquid glass aesthetic (translucent frosted background, rounded pill border, subtle violet reflection).<br>2. Text maintains high contrast and readability against both Dark and Light backgrounds.<br>3. Heading scales appropriately on smaller viewports. |
| **Actual result** | |
| **Status** | Not Tested |
| **Notes** | |

---

### UI-007: "Return to Homepage" Button Navigation Integrity

| Field | Details |
| :--- | :--- |
| **Test ID** | UI-007 |
| **Feature** | Consistent return navigation on all secondary and authentication views |
| **Prerequisites** | Website running |
| **Steps** | 1. Navigate to `auth/login.php` (or `login.html`).<br>2. Click "Return to Homepage" button in header.<br>3. Navigate to `auth/register.php` (or `register.html`) -> click "Return to Homepage".<br>4. Navigate to `job-details.html?id=1` -> click "Back to Opportunities".<br>5. Navigate to `jobcreate.html` -> click "Return to Homepage" / "Cancel". |
| **Expected result** | 1. Every secondary and auth view contains a visible, accessible return link.<br>2. Clicking each link smoothly navigates back to `index.html` (or `index.php`) without 404 errors.<br>3. Return button incorporates the liquid glass arrow icon and button hover styling. |
| **Actual result** | |
| **Status** | Not Tested |
| **Notes** | |

---

## 3. Responsive Design & Accessibility Test Cases

### UI-008: Mobile Viewport Responsiveness (375px & 414px)

| Field | Details |
| :--- | :--- |
| **Test ID** | UI-008 |
| **Feature** | Mobile layout integrity on small screens (iPhone SE / iPhone 14/15) |
| **Prerequisites** | DevTools Device Mode enabled (`Ctrl + Shift + M`) |
| **Steps** | 1. Set viewport to **375 x 667** (iPhone SE) and **414 x 896**.<br>2. Test `index.html`, `jobs.html`, `event.html`, `job-details.html`, and `login.html`.<br>3. Verify navigation toggle / hamburger menu behavior.<br>4. Check horizontal overflow (scroll horizontally to check for side spillage).<br>5. Check job cards and event cards stacking. |
| **Expected result** | 1. No horizontal scrollbar (`overflow-x: hidden` / clean layout containment).<br>2. Mobile navigation toggles open and closed cleanly with backdrop blur.<br>3. Job and event cards stack vertically in a single full-width column.<br>4. Action buttons ("View & Apply", "Register", "Share") are easily tappable (min 44px height).<br>5. Theme switcher remains accessible on mobile viewports. |
| **Actual result** | |
| **Status** | Not Tested |
| **Notes** | |

---

### UI-009: Tablet Viewport Responsiveness (768px & 1024px)

| Field | Details |
| :--- | :--- |
| **Test ID** | UI-009 |
| **Feature** | Tablet layout integrity (iPad / iPad Air / Android tablets) |
| **Prerequisites** | DevTools Device Mode set to 768px and 1024px width |
| **Steps** | 1. Navigate across all pages at 768px and 1024px widths.<br>2. Inspect grid transitions for jobs, events, and alumni directory.<br>3. Verify modal dialogs (`applyModal`) scale without overflowing viewport. |
| **Expected result** | 1. Multi-column grids adapt cleanly to 2 columns on tablet.<br>2. Modals center accurately with padding and scrollable interior if content exceeds height.<br>3. Header elements maintain comfortable spacing without collision. |
| **Actual result** | |
| **Status** | Not Tested |
| **Notes** | |

---

### UI-010: Keyboard Tab Navigation & Accessibility (WCAG 2.1)

| Field | Details |
| :--- | :--- |
| **Test ID** | UI-010 |
| **Feature** | Keyboard accessibility, visible focus indicators, and reduced motion |
| **Prerequisites** | Browser open, use only keyboard (`Tab`, `Shift + Tab`, `Enter`, `Space`) |
| **Steps** | 1. Navigate to `jobs.html`.<br>2. Press `Tab` repeatedly to cycle through interactive elements.<br>3. Verify focus outlines on theme switcher options, search inputs, tabs, and action buttons.<br>4. Press `Enter` on a focused button to trigger action.<br>5. In DevTools Rendering panel, emulate CSS media feature `prefers-reduced-motion: reduce`. |
| **Expected result** | 1. All interactive elements receive logical tab focus order.<br>2. Visible focus indicator (e.g. purple or cyan outline ring) is clearly discernible.<br>3. Keyboard `Enter` triggers button actions.<br>4. When `prefers-reduced-motion: reduce` is enabled, smooth scroll, transitions, and pulsing glow animations are minimized or disabled. |
| **Actual result** | |
| **Status** | Not Tested |
| **Notes** | |
