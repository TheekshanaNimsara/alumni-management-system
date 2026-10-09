# Four-Phase Project Roadmap & Implementation Matrix

**Project:** General Sir John Kotelawala Defence University (KDU) Alumni Network  
**Target Environment:** PHP 8+, MySQL / MariaDB, Apache (XAMPP), Vanilla JS, Semantic HTML5, CSS3  
**Document Identifier:** `docs/04-four-phase-project-roadmap.md`  
**Revision:** 1.0.0 (Project Delivery Plan)  

---

## Executive Summary

The **KDU Alumni Network** project is structured across **four sequential engineering phases**. This roadmap delineates the scope, technical objectives, architectural dependencies, security controls, acceptance criteria, and completion status of each phase.

```
┌─────────────────────────────────────────────────────────────────────────────┐
│ PHASE 1: Foundation, Architecture & Authentication                          │
│ Project skeleton, database schema, session hardening, BCRYPT auth, shared UI│
├─────────────────────────────────────────────────────────────────────────────┤
│ PHASE 2: Alumni Profiles & Directory                                        │
│ Biographical profiles, secure photo uploads, multi-filter search, pagination│
├─────────────────────────────────────────────────────────────────────────────┤
│ PHASE 3: Events, RSVP, Job Board & Applications                             │
│ Campus events, RSVP state toggles, capacity meters, jobs, cover-letter apps │
├─────────────────────────────────────────────────────────────────────────────┤
│ PHASE 4: Messaging, Administration, Reporting & Verification               │
│ 1-to-1 messaging, admin dashboard, user lifecycle, reports, full audit     │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## Phase 1 — Foundation, Architecture & Authentication

### 1. Phase Objective
Establish the foundational infrastructure of the KDU Alumni Network: normalized relational database schema, hardened session configuration, BCRYPT-secured authentication (registration, login, logout), role-based access control guards, and the universal shared frontend layout with responsive styling.

### 2. Business Purpose
Ensures that all subsequent modules build upon a secure, reliable foundation. Guarantees that only authorized members access private university features, eliminates session fixation risks, and presents a consistent, professional institutional identity.

### 3. Features Included
* Relational database schema with 9 normalized tables and cascading foreign keys.
* Centralized PDO connection singleton with auto-creation fallback.
* Session security hardening (`httponly`, `samesite=Lax`, `use_only_cookies`, `use_strict_mode`).
* Registration with academic validation (cohort, degree, department) and password hashing (BCRYPT).
* Login with credential verification, session ID regeneration, and role-based routing.
* Logout with complete cookie invalidation and session destruction.
* Shared layout components (`includes/header.php`, `includes/footer.php`, `includes/functions.php`).
* Custom KDU Maroon & Gold responsive CSS design system.
* Public landing page (`index.php`) and institutional background page (`about.php`).

### 4. Existing Files Involved
* `config/db.php`
* `config/setup.php`
* `database/schema.sql` (and root `schema.sql`)
* `auth/register.php`
* `auth/login.php`
* `auth/logout.php`
* `includes/header.php`
* `includes/footer.php`
* `includes/functions.php`
* `includes/auth.php`
* `assets/css/style.css`
* `assets/js/main.js`
* `assets/images/campus-hero.svg`
* `assets/images/default-avatar.svg`
* `index.php`
* `about.php`

### 5. Files to Create (if necessary)
None. All foundational files are implemented and verified in the repository.

### 6. Database Tables Involved
* `users`
* `alumni_profiles`

### 7. Dependencies on Previous Phases
None (Foundational phase).

### 8. Required Implementation Tasks
* [x] Design normalized schema with primary keys, unique indexes, and foreign keys.
* [x] Configure PDO connection with exception handling and utf8mb4 encoding.
* [x] Implement session security parameters before session initialization.
* [x] Build registration controller with duplicate email/username detection and transaction support.
* [x] Implement login with `password_verify()` and `session_regenerate_id(true)`.
* [x] Implement logout with cookie expiration and session destruction.
* [x] Build responsive navigation bar with active route highlighting and dynamic auth states.
* [x] Implement homepage with featured alumni, events, and jobs previews.

### 9. Security Requirements
* Passwords hashed exclusively with `password_hash($pwd, PASSWORD_BCRYPT)`.
* Session cookies must enforce `HttpOnly` and `SameSite=Lax`.
* Emulated prepares disabled in PDO to enforce native prepared statements.
* Generic authentication error messages to prevent user enumeration.

### 10. Testing Requirements
* Test registration with valid, duplicate, and malformed inputs.
* Test login with correct, incorrect, and suspended credentials.
* Verify session cookie parameters in browser developer tools.
* Verify responsive layout on mobile (<768px) and desktop viewports.

### 11. Acceptance Criteria
* Users can successfully register, and records are created in both `users` and `alumni_profiles`.
* Passwords stored in the database are valid BCRYPT hashes (starting with `$2y$`).
* Valid credentials authenticate successfully and redirect according to role.
* Invalid credentials display a user-friendly error message without system exceptions.
* Session ID changes immediately upon successful authentication.
* Logging out completely invalidates the session and redirects to the homepage.

### 12. Definition of Done
All Phase 1 code passes syntax checks, database tables exist with referential integrity, and authentication workflows pass manual and automated verification.

### 13. Current Completion Status
**`COMPLETED AND VERIFIED`** (100% of Phase 1 tasks are implemented and verified).

### 14. Remaining Blockers
None.

---

## Phase 2 — Alumni Profiles & Directory

### 1. Phase Objective
Deliver the alumni biographical portfolio and community discovery engine. Enable members to manage their professional details, upload profile photos securely, toggle visibility settings, and discover alumni through a multi-filter, paginated directory.

### 2. Business Purpose
Enables graduates to showcase their career accomplishments, locate batchmates, connect across faculties, and provide mentorship to junior students.

### 3. Features Included
* Alumni profile viewing with academic background, career details, bio, skills, and LinkedIn links.
* Profile owner editing interface with input validation and transactional data updates.
* Secure multipart avatar photo upload with MIME inspection and size caps.
* Web server execution denial in upload folders via `.htaccess`.
* Multi-attribute directory search (keyword, graduation year, degree programme, department).
* Server-side pagination (6 cards per page) preserving active filter parameters.
* Client-side live card search filtering.
* Privacy-preserving visibility toggle (`is_public = 1`).

### 4. Existing Files Involved
* `profile.php`
* `directory.php`
* `includes/functions.php`
* `uploads/.htaccess`
* `uploads/profiles/.htaccess`
* `assets/js/main.js`

### 5. Files to Create (if necessary)
None.

### 6. Database Tables Involved
* `users`
* `alumni_profiles`

### 7. Dependencies on Previous Phases
Requires Phase 1 authentication, session management, and database connection.

### 8. Required Implementation Tasks
* [x] Implement `profile.php` controller handling both profile viewing and editing tabs.
* [x] Build file upload handler with MIME verification via `getimagesize()`.
* [x] Deploy `.htaccess` security files in `uploads/` to deny script execution.
* [x] Build `directory.php` with dynamic SQL `WHERE` builder and parameterized queries.
* [x] Implement pagination logic computing `$totalPages` and offset math.
* [x] Integrate client-side instant filtering in `assets/js/main.js`.

### 9. Security Requirements
* Uploaded files must be validated using `getimagesize()` (MIME types: `image/jpeg`, `image/png`, `image/webp`).
* Maximum file size capped at 2MB.
* Uploaded files renamed with cryptographically random strings (`avatar_{uid}_{time}_{hash}.{ext}`).
* Upload directory must disable PHP execution engine via `php_flag engine off`.
* Directory queries must strictly enforce `u.status = 'active' AND p.is_public = 1`.

### 10. Testing Requirements
* Test profile updates with valid data and verify database reflection.
* Test photo uploads with valid JPG/PNG files and verify storage in `uploads/profiles/`.
* Test photo uploads with non-image files (.php, .txt) and verify rejection.
* Test directory search with keywords, graduation year, and degree filters.
* Test pagination across multiple pages with active filters.

### 11. Acceptance Criteria
* Alumni can edit their job title, employer, location, bio, and skills.
* Uploaded profile photos display immediately on profile and directory cards.
* Executable files uploaded to `uploads/` cannot be executed by the web server.
* Directory correctly paginates results at 6 profiles per page.
* Non-public profiles do not appear in public directory listings.

### 12. Definition of Done
Profile editing and photo uploads function without errors, directory search and pagination operate reliably, and upload folder security is confirmed.

### 13. Current Completion Status
**`COMPLETED AND VERIFIED`** (100% of Phase 2 tasks are implemented and verified).

### 14. Remaining Blockers
None.

---

## Phase 3 — Events, RSVP, Job Board & Applications

### 1. Phase Objective
Implement the campus gatherings and career development hubs. Provide event discovery with seat reservations and capacity tracking, an alumni event proposal mechanism, career vacancy listings, direct job applications with cover messages, duplicate application guards, and administrative moderation endpoints.

### 2. Business Purpose
Fosters community engagement through university gatherings, networking dinners, and seminars. Facilitates career advancement and student placement by connecting graduates with alumni recruiters.

### 3. Features Included
* Events board displaying upcoming gatherings, dates, venues, organizers, and attendee counts.
* Interactive RSVP toggle (`Attend` <-> `Cancel RSVP`) with seat capacity enforcement.
* Alumni event proposal form submitting events with `status = 'pending'`.
* Administrative event moderation interface (`admin/events.php`) to approve, reject, or delete events.
* Backward-compatible event router (`event.php`) with JSON endpoint.
* Career board displaying vacancies with job type filters (Full-Time, Internship, Remote, etc.).
* Member job application modal with cover message and duplicate application prevention.
* Alumni vacancy submission form creating jobs with `status = 'pending'`.
* Administrative job moderation interface (`admin/jobs.php`) to approve, reject, or delete listings.
* Backward-compatible job router (`job.php`) with schema migration and JSON endpoint.

### 4. Existing Files Involved
* `events.php`
* `event.php`
* `jobs.php`
* `job.php`
* `admin/events.php`
* `admin/jobs.php`
* `assets/css/event.css`
* `assets/css/jobs.css`

### 5. Files to Create (if necessary)
None.

### 6. Database Tables Involved
* `events`
* `event_registrations`
* `jobs`
* `job_applications`
* `users`

### 7. Dependencies on Previous Phases
Requires Phase 1 authentication and Phase 2 profile data.

### 8. Required Implementation Tasks
* [x] Build `events.php` controller handling RSVP toggles and event proposal submissions.
* [x] Implement capacity checking preventing RSVP on full or concluded events.
* [x] Build `jobs.php` controller handling job applications and vacancy submissions.
* [x] Implement duplicate application guard in `job_applications`.
* [x] Build `admin/events.php` and `admin/jobs.php` moderation controllers.
* [x] Harmonize legacy routing adapters (`event.php`, `job.php`) to redirect to primary pages.

### 9. Security Requirements
* RSVP and job application actions require authenticated member sessions.
* Moderation actions strictly guarded by `require_admin()`.
* All database interactions parameterized with PDO prepared statements.
* Cover messages and event descriptions sanitized and escaped against XSS.

### 10. Testing Requirements
* Test event RSVP toggle: verify attendance count increments/decrements.
* Test RSVP on full event: verify rejection message.
* Test event proposal submission and subsequent admin approval.
* Test job application submission and verify duplicate prevention on re-submission.
* Test vacancy proposal submission and subsequent admin approval.

### 11. Acceptance Criteria
* Members can RSVP and cancel RSVP for active events.
* Seat capacity is accurately enforced.
* Proposed events and jobs enter `pending` status and become visible on public boards only after admin approval.
* Members cannot submit duplicate applications for the same job vacancy.

### 12. Definition of Done
Event RSVP, capacity checking, event proposals, job applications, duplicate guards, and admin moderation workflows are fully functional and verified.

### 13. Current Completion Status
**`COMPLETED AND VERIFIED`** (100% of Phase 3 tasks are implemented and verified).

### 14. Remaining Blockers
None.

---

## Phase 4 — Messaging, Administration, Reporting & Final Verification

### 1. Phase Objective
Complete the communication, governance, trust & safety, and verification systems. Implement 1-to-1 private messaging, conversation authorization, unread indicators, the administrative dashboard with analytics, user lifecycle controls, community misconduct reporting, client-side progressive enhancement, comprehensive security auditing, and deployment packaging.

### 2. Business Purpose
Enables direct peer networking, equips university administrators with platform governance tools, ensures community safety through content moderation, and validates the entire platform for reliable production deployment.

### 3. Features Included
* Private 1-to-1 messaging system with auto-initializing conversation threads.
* Participant-level access authorization guarding message privacy.
* Unread message indicator badges in top navigation bar and conversation list.
* Real-time conversation view with chronological message bubbles and autoscroll.
* Standalone message action bridge (`send_message.php`) and alias (`conversation.php`).
* Contextual misconduct reporting (`actions/report.php`) on profiles, events, jobs, and messages.
* Administrative reports center (`admin/reports.php`) with resolution controls and user suspension.
* Administrative dashboard (`admin/index.php`) with platform metrics and moderation queues.
* Full user lifecycle manager (`admin/users.php`) with activation, suspension, role change, and deletion.
* Safety guards protecting Root Admin (ID 1) from deletion and preventing admin self-lockout.
* Platform diagnostics and settings view (`admin/settings.php`).
* Comprehensive manual test suite and documentation package.

### 4. Existing Files Involved
* `messages.php`
* `conversation.php`
* `send_message.php`
* `actions/report.php`
* `admin/index.php`
* `admin/users.php`
* `admin/reports.php`
* `admin/settings.php`
* `admin/includes/sidebar.php`
* `assets/js/main.js`
* `docs/*`

### 5. Files to Create (if necessary)
None.

### 6. Database Tables Involved
* `conversations`
* `messages`
* `reports`
* `users`
* `alumni_profiles`
* `events`
* `jobs`

### 7. Dependencies on Previous Phases
Requires all previous phases (Phase 1 authentication, Phase 2 profiles, Phase 3 events/jobs).

### 8. Required Implementation Tasks
* [x] Build `messages.php` controller with canonical conversation ID ordering (`min`/`max`).
* [x] Implement participant authorization check preventing unauthorized message eavesdropping.
* [x] Build unread message counter and auto-read update upon viewing.
* [x] Implement message autoscroll script in `assets/js/main.js`.
* [x] Build `actions/report.php` action handler and modal integration across views.
* [x] Build `admin/reports.php` moderation interface with user suspension capability.
* [x] Build `admin/index.php` dashboard computing aggregate counts across all tables.
* [x] Build `admin/users.php` with root admin protection and self-action safeguards.
* [x] Perform end-to-end security verification and produce documentation suite.

### 9. Security Requirements
* Message conversations must be accessible only to their two verified participants.
* Reports and user management actions require `require_admin()`.
* Root admin (ID 1) must be protected against modification, suspension, or deletion.
* Administrators must be prevented from suspending or deleting their own active account.
* All message and report text must be escaped via `htmlspecialchars()` to prevent XSS.

### 10. Testing Requirements
* Test message exchange between two alumni accounts; verify unread indicator and chronological ordering.
* Test unauthorized conversation access by attempting to view a third-party conversation ID.
* Test report submission from an event and verify appearance in admin reports queue.
* Test user suspension via admin panel; verify suspended user cannot authenticate.
* Test attempted deletion of Root Admin (ID 1); verify system rejection.
* Verify mobile navigation toggle and alert auto-dismissal in browser.

### 11. Acceptance Criteria
* Messages are delivered accurately and privately between participants.
* Unread badges update dynamically in the navigation bar.
* Reports can be submitted by members and reviewed/resolved by administrators.
* Suspended users are immediately barred from logging in.
* Root administrator cannot be deleted or suspended.
* Administrative metrics reflect actual database records.

### 12. Definition of Done
Messaging operates securely, administration workflows are fully functional with verified safety guards, community reporting is active, and complete technical documentation is prepared.

### 13. Current Completion Status
**`COMPLETED AND VERIFIED`** (100% of Phase 4 tasks are implemented and verified).

### 14. Remaining Blockers
None.

---

## Project Task Matrix

The following matrix documents the implementation and verification status of all platform tasks:

| Phase | Module | Task Description | Related Files | Dependencies | Status | Acceptance Criteria |
| :---: | :--- | :--- | :--- | :--- | :---: | :--- |
| **1** | Architecture | Database Schema Definition | `database/schema.sql` | None | `Completed and verified` | 9 normalized tables created with foreign keys and cascade rules. |
| **1** | Database | PDO Connection Singleton | `config/db.php` | MySQL Server | `Completed and verified` | Connects with exception mode, utf8mb4, emulated prepares off. |
| **1** | Database | Automated Setup & Seed Tool | `config/setup.php` | `schema.sql` | `Completed and verified` | Executes multi-query schema; guarded when populated. |
| **1** | Security | Session Security Hardening | `config/db.php` | None | `Completed and verified` | Sets `HttpOnly`, `SameSite=Lax`, strict mode before session start. |
| **1** | Auth | Member Registration Controller | `auth/register.php` | `config/db.php` | `Completed and verified` | BCRYPT password hash, validates cohort/degree, PDO transaction. |
| **1** | Auth | Credential Login Controller | `auth/login.php` | `config/db.php` | `Completed and verified` | `password_verify()`, `session_regenerate_id(true)`, role routing. |
| **1** | Auth | Secure Logout Controller | `auth/logout.php` | Session | `Completed and verified` | Flushes session, expires cookie, redirects to home. |
| **1** | Layout | Shared Header & Navbar | `includes/header.php` | `config/db.php` | `Completed and verified` | Dynamic navigation, unread badge, active route highlight. |
| **1** | Layout | Shared Footer & Script Loader | `includes/footer.php` | `main.js` | `Completed and verified` | Institutional links, copyright, loads progressive scripts. |
| **1** | Layout | Master CSS Design System | `assets/css/style.css` | None | `Completed and verified` | KDU Maroon & Gold palette, responsive grid, card components. |
| **1** | Public | Homepage & Highlights | `index.php` | `header.php` | `Completed and verified` | Featured alumni, upcoming events, jobs preview, fallback data. |
| **1** | Public | Institutional About Page | `about.php` | `header.php` | `Completed and verified` | University mission, leadership pillars, contact info. |
| **2** | Profile | Profile Viewing Interface | `profile.php` | `header.php` | `Completed and verified` | Displays academic history, employer, bio, skills, LinkedIn. |
| **2** | Profile | Profile Editing Form | `profile.php` | `db.php` | `Completed and verified` | Owner-only edit form, email uniqueness check, PDO transaction. |
| **2** | Profile | Avatar Photo Upload Handler | `profile.php` | `uploads/` | `Completed and verified` | MIME validation via `getimagesize()`, 2MB cap, random name. |
| **2** | Security | Upload Folder Protection | `uploads/.htaccess` | Apache | `Completed and verified` | `php_flag engine off`, denies script execution. |
| **2** | Directory | Multi-Filter Search Engine | `directory.php` | `db.php` | `Completed and verified` | Dynamic WHERE builder, cohort/degree/department filters. |
| **2** | Directory | Server-Side Pagination | `directory.php` | `db.php` | `Completed and verified` | 6 items/page, offset calculation, query string preservation. |
| **2** | Frontend | Instant Client Search | `assets/js/main.js` | DOM | `Completed and verified` | Live keyword filtering on card elements without reload. |
| **3** | Events | Campus Events Board | `events.php` | `header.php` | `Completed and verified` | Chronological event listings, venue, organizer details. |
| **3** | Events | Dynamic RSVP Toggle | `events.php` | `db.php` | `Completed and verified` | Attending/Cancel toggle, seat capacity enforcement. |
| **3** | Events | Member Event Proposal | `events.php` | `db.php` | `Completed and verified` | Validates input, inserts event with `status = 'pending'`. |
| **3** | Events | Admin Event Moderation | `admin/events.php` | `db.php` | `Completed and verified` | Approve, reject, delete actions; admin check enforced. |
| **3** | Events | Event Router & JSON Adapter | `event.php` | `db.php` | `Completed and verified` | Redirects web visits to `events.php`; serves JSON on `?action=get`. |
| **3** | Jobs | Career Board & Filters | `jobs.php` | `header.php` | `Completed and verified` | Vacancies indexed by type (Full-Time, Internship, Remote). |
| **3** | Jobs | Job Application Workflow | `jobs.php` | `db.php` | `Completed and verified` | Cover message modal, duplicate application prevention. |
| **3** | Jobs | Member Vacancy Submission | `jobs.php` | `db.php` | `Completed and verified` | Validates fields, creates listing with `status = 'pending'`. |
| **3** | Jobs | Admin Job Moderation | `admin/jobs.php` | `db.php` | `Completed and verified` | Approve, reject, delete actions; admin check enforced. |
| **3** | Jobs | Job Router & JSON Adapter | `job.php` | `db.php` | `Completed and verified` | Schema migration check, web redirect, JSON endpoint. |
| **4** | Messaging | 1-to-1 Private Messaging UI | `messages.php` | `db.php` | `Completed and verified` | Canonical conversation ordering, chronological bubble transcript. |
| **4** | Messaging | Conversation Authorization | `messages.php` | `db.php` | `Completed and verified` | Enforces participant-only access check on message threads. |
| **4** | Messaging | Unread Status Tracking | `messages.php` | `db.php` | `Completed and verified` | Dynamic counter badge in navbar, auto-marks read on view. |
| **4** | Messaging | Direct Message Bridge | `send_message.php` | `db.php` | `Completed and verified` | Standalone POST endpoint for quick modal messaging. |
| **4** | Messaging | Conversation Route Alias | `conversation.php` | `db.php` | `Completed and verified` | Resolves `?user_id=X` and redirects to `messages.php`. |
| **4** | Moderation | Misconduct Reporting Endpoint | `actions/report.php` | `db.php` | `Completed and verified` | Handles POST reports from profile, event, job, message modals. |
| **4** | Moderation | Admin Reports Management | `admin/reports.php` | `db.php` | `Completed and verified` | Status updates (reviewed, resolved, dismissed), user suspension. |
| **4** | Admin | Central Admin Dashboard | `admin/index.php` | `db.php` | `Completed and verified` | Metrics tiles for users, events, jobs, reports; pending queues. |
| **4** | Admin | User Lifecycle Management | `admin/users.php` | `db.php` | `Completed and verified` | Activate, suspend, role changes, cascade delete. |
| **4** | Admin | Root Admin Protection Guard | `admin/users.php` | `db.php` | `Completed and verified` | Blocks deletion/modification of User ID 1 and self-lockout. |
| **4** | Admin | Platform Diagnostics View | `admin/settings.php` | `db.php` | `Completed and verified` | Displays PHP version, database state, cookie flags, re-seed link. |
| **4** | Frontend | Mobile Navigation Toggle | `assets/js/main.js` | DOM | `Completed and verified` | Hamburger toggle adds `.active` class to mobile nav menu. |
| **4** | Frontend | Alert Auto-Dismissal | `assets/js/main.js` | DOM | `Completed and verified` | Smooth fade-out of alert banners after 5 seconds. |
| **4** | Verification | Documentation Suite | `docs/*` | All Files | `Completed and verified` | Comprehensive 5-document architecture and technical package. |
