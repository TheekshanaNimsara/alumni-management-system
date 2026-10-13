# 02 — Master Manual Testing Checklist & Role Matrix

**Project:** University Alumni Network  
**Target Environments:** Local Dynamic Full-Stack (XAMPP) & Static Host (GitHub Pages)  
**Execution Standard:** Pure HTML5, CSS3, Vanilla JS, and Native PHP/PDO Architecture  
**Test Status Legend:**
* `Not Tested` — Test case has not yet been executed by the QA engineer.
* `Pass` — All expected results met completely without regression.
* `Fail` — Observed behavior deviates from expected result (requires filing a bug report in `07-bug-report-template.md`).
* `Blocked` — Test cannot proceed due to environmental failure or prerequisite bug.

---

## 1. Master Test Suite Index

| Suite ID | Document Reference | Feature Domain | Test Count | Requires PHP/MySQL? |
| :---: | :--- | :--- | :---: | :---: |
| **SUITE-AUTH** | `03-authentication-and-permissions-tests.md` | Authentication & Password Security | 8 | Yes (Full-Stack) |
| **SUITE-PERM** | `03-authentication-and-permissions-tests.md` | Role-Based Access Control (RBAC) & Route Protection | 7 | Yes (Full-Stack) |
| **SUITE-JOB** | `04-jobs-and-events-tests.md` | Job Board, Applications, Creation & Moderation | 10 | Yes (Full-Stack) |
| **SUITE-EVT** | `04-jobs-and-events-tests.md` | Events Portal, RSVP, Creation & Moderation | 9 | Yes (Full-Stack) |
| **SUITE-UI** | `05-ui-and-responsive-tests.md` | Theme Switcher, Liquid Glass, Responsive & A11y | 10 | No (Static + Full-Stack) |
| **SUITE-DATA** | `06-deployment-and-database-tests.md` | Database Integrity, Transactions & Security | 6 | Yes (Full-Stack) |
| **SUITE-DEP** | `06-deployment-and-database-tests.md` | Deployment, Static Fallbacks & Asset Paths | 5 | Both Environments |
| **TOTAL** | — | **Full System Verification** | **55 Tests** | — |

---

## 2. Role-Based Coverage Matrix

Every major section of the system must be verified across three distinct security roles:

| Feature / Action | Anonymous Visitor (Not Logged In) | Normal Alumni (`johndoe`) | Administrator (`admin`) |
| :--- | :---: | :---: | :---: |
| **Browse Jobs Board** | Permitted (Approved only) | Permitted (Approved + My Postings) | Permitted (All Listings) |
| **View Job Details Page** | Permitted | Permitted | Permitted |
| **Apply for Job ("View & Apply" / Modal)** | Redirects to Login (`auth_required`) | Permitted (Submits Application) | Permitted (Submits Application) |
| **Post a Job ("+ Post a Job")** | Redirects to Login (`auth_required`) | Permitted (Saves as `pending`) | Permitted (Saves as `approved`) |
| **Job Moderation (Approve / Reject / Delete)** | Forbidden (Hidden UI & 403 API) | Forbidden (Hidden UI & 403 API) | Permitted (`jobadmin.html` / `admin/jobs.php`) |
| **Browse Events Portal** | Permitted (Approved only) | Permitted (Approved only) | Permitted (All Events) |
| **RSVP for Event ("Register" / "RSVP")** | Redirects to Login (`auth_required`) | Permitted (Submits RSVP) | Permitted (Submits RSVP) |
| **Create Event ("+ Create Event")** | Redirects to Login (`auth_required`) | Permitted (Saves as `pending`) | Permitted (Saves as `approved`) |
| **Event Moderation (Approve / Reject / Delete)** | Forbidden (Hidden UI & 403 API) | Forbidden (Hidden UI & 403 API) | Permitted (`eventadmin.html` / `admin/events.php`) |
| **Admin Navigation Links (`#adminNavLink`)** | Hidden (`display: none`) | Hidden (`display: none`) | Visible & Accessible |
| **Admin Portal Direct Access (`admin/*.php`)** | Redirects to Login (`admin_required=1`) | Forbidden (Redirects with 403) | Permitted (Dashboard Loaded) |
| **Theme Switcher (Dark / Light / System)** | Functional & Persisted | Functional & Persisted | Functional & Persisted |
| **Apple/macOS Liquid Glass Buttons** | Interactive & Animated | Interactive & Animated | Interactive & Animated |

---

## 3. Master Test Execution Tracker

Use this checklist table to record progress during test runs.

| Test ID | Test Summary | Category | Environment | Status | Executed By | Date | Notes / Findings |
| :--- | :--- | :--- | :--- | :---: | :--- | :--- | :--- |
| **AUTH-001** | User login with valid credentials | Authentication | Local XAMPP | Not Tested | | | |
| **AUTH-002** | User login with invalid password | Authentication | Local XAMPP | Not Tested | | | |
| **AUTH-003** | User login with non-existent user | Authentication | Local XAMPP | Not Tested | | | |
| **AUTH-004** | User registration with valid inputs | Authentication | Local XAMPP | Not Tested | | | |
| **AUTH-005** | User registration duplicate email check | Authentication | Local XAMPP | Not Tested | | | |
| **AUTH-006** | Session regeneration upon login | Security | Local XAMPP | Not Tested | | | |
| **AUTH-007** | User logout and session destruction | Authentication | Local XAMPP | Not Tested | | | |
| **AUTH-008** | Suspended account login rejection | Security | Local XAMPP | Not Tested | | | |
| **PERM-001** | Anonymous visitor navigation bar links | RBAC Navigation | Both | Not Tested | | | |
| **PERM-002** | Normal user navigation bar links | RBAC Navigation | Both | Not Tested | | | |
| **PERM-003** | Administrator navigation bar links | RBAC Navigation | Both | Not Tested | | | |
| **PERM-004** | Direct URL protection for admin PHP files | Route Protection | Local XAMPP | Not Tested | | | |
| **PERM-005** | Normal user blocked from admin pages | Route Protection | Local XAMPP | Not Tested | | | |
| **PERM-006** | Unauthenticated API rejection on job action | API Security | Local XAMPP | Not Tested | | | |
| **PERM-007** | Unauthenticated API rejection on event action | API Security | Local XAMPP | Not Tested | | | |
| **JOB-001** | Public job listings rendering and filters | Jobs Board | Both | Not Tested | | | |
| **JOB-002** | Job search by keyword and location | Jobs Board | Both | Not Tested | | | |
| **JOB-003** | Anonymous click on "View & Apply" redirect | Protected Action | Both | Not Tested | | | |
| **JOB-004** | Anonymous click on "+ Post a Job" redirect | Protected Action | Both | Not Tested | | | |
| **JOB-005** | Authenticated user opens job details page | Job Details | Both | Not Tested | | | |
| **JOB-006** | Authenticated job application submission | Job Application | Local XAMPP | Not Tested | | | |
| **JOB-007** | Duplicate job application prevention | Job Application | Local XAMPP | Not Tested | | | |
| **JOB-008** | Normal user job creation (pending status) | Job Posting | Local XAMPP | Not Tested | | | |
| **JOB-009** | Admin approves pending job posting | Moderation | Local XAMPP | Not Tested | | | |
| **JOB-010** | Admin rejects and deletes job posting | Moderation | Local XAMPP | Not Tested | | | |
| **EVT-001** | Public events rendering and past/upcoming filter | Events Portal | Both | Not Tested | | | |
| **EVT-002** | Anonymous click on "Register" / "RSVP" redirect | Protected Action | Both | Not Tested | | | |
| **EVT-003** | Anonymous click on "+ Create Event" redirect | Protected Action | Both | Not Tested | | | |
| **EVT-004** | Authenticated event RSVP submission | Event RSVP | Local XAMPP | Not Tested | | | |
| **EVT-005** | Duplicate event RSVP prevention | Event RSVP | Local XAMPP | Not Tested | | | |
| **EVT-006** | Normal user event creation (pending status) | Event Posting | Local XAMPP | Not Tested | | | |
| **EVT-007** | Admin approves pending event | Moderation | Local XAMPP | Not Tested | | | |
| **EVT-008** | Admin rejects and deletes event | Moderation | Local XAMPP | Not Tested | | | |
| **EVT-009** | Event capacity limit enforcement | Event RSVP | Local XAMPP | Not Tested | | | |
| **UI-001** | Dark theme visual verification & tokens | UI / Theme | Both | Not Tested | | | |
| **UI-002** | Light theme visual verification & contrast | UI / Theme | Both | Not Tested | | | |
| **UI-003** | System theme auto-sync with OS appearance | UI / Theme | Both | Not Tested | | | |
| **UI-004** | Theme preference persistence across reloads | UI / Storage | Both | Not Tested | | | |
| **UI-005** | Liquid glass button hover, sheen & glow | UI / Apple Style | Both | Not Tested | | | |
| **UI-006** | Liquid glass heading effect ("All Opportunities") | UI / Apple Style | Both | Not Tested | | | |
| **UI-007** | "Return to Homepage" button navigation | UI / Navigation | Both | Not Tested | | | |
| **UI-008** | Mobile viewport responsiveness (375px/414px) | UI / Responsive | Both | Not Tested | | | |
| **UI-009** | Tablet viewport responsiveness (768px/1024px) | UI / Responsive | Both | Not Tested | | | |
| **UI-010** | Keyboard tab accessibility & focus rings | Accessibility | Both | Not Tested | | | |
| **DATA-001** | Prepared statement SQL injection resistance | DB Security | Local XAMPP | Not Tested | | | |
| **DATA-002** | Foreign key cascade integrity on user delete | DB Integrity | Local XAMPP | Not Tested | | | |
| **DATA-003** | Graceful database offline fallback | Resilience | Local XAMPP | Not Tested | | | |
| **DATA-004** | XSS sanitization on job and event input fields | Security | Both | Not Tested | | | |
| **DATA-005** | Open redirect validation on `redirect` param | Security | Both | Not Tested | | | |
| **DATA-006** | Session cookie flags (`HttpOnly`, `SameSite`) | Security | Local XAMPP | Not Tested | | | |
| **DEP-001** | Static mode mock data fallback on GitHub Pages | Static Fallback | GitHub Pages | Not Tested | | | |
| **DEP-002** | Demo login flow in static GitHub Pages mode | Static Fallback | GitHub Pages | Not Tested | | | |
| **DEP-003** | Relative path resolution across subdirectories | Deployment | Both | Not Tested | | | |
| **DEP-004** | Sensitive config files direct exposure check | Security | Local XAMPP | Not Tested | | | |
| **DEP-005** | Favicon, SVG icons and asset 404 audit | Static Assets | Both | Not Tested | | | |
