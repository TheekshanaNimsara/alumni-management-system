# Complete File-by-File Technical Description

**Project:** General Sir John Kotelawala Defence University (KDU) Alumni Network  
**Target Environment:** PHP 8+, MySQL / MariaDB, Apache (XAMPP), Vanilla JS, Semantic HTML5, CSS3  
**Document Identifier:** `docs/03-complete-file-by-file-technical-description.md`  
**Revision:** 1.0.0 (Comprehensive Source Code Specification)  

---

## Overview

This document provides an exhaustive, source-verified technical specification of every relevant file in the KDU Alumni Network codebase. Each entry details the file's architectural purpose, execution flow, inputs/outputs, database operations, security mechanisms, known issues, manual testing steps, implementation status, and associated development phase.

---

## 1. Core User-Facing Pages & Page Controllers

### File: `index.php`
* **1. File purpose:** The primary public landing page of the KDU Alumni Network. Welcomes visitors, highlights key university statistics, showcases featured alumni, displays upcoming events, and previews recent job vacancies.
* **2. Main responsibilities:**
  * Render the hero section with KDU maroon and gold branding and primary CTAs.
  * Query and display up to 3 featured public alumni profiles.
  * Query and display up to 3 upcoming approved events.
  * Query and display up to 3 approved career opportunities.
  * Provide fallback to sample static datasets if the database is offline.
* **3. Execution flow:**
  1. Sets `$pageTitle = 'Home'` and `$currentPage = 'home'`.
  2. Requires `includes/header.php`.
  3. Checks `$db_connected` and executes queries to retrieve featured alumni, approved events, and approved jobs.
  4. Falls back to `get_sample_alumni()`, `get_sample_events()`, `get_sample_jobs()` if queries return empty.
  5. Renders the HTML hero, statistics ribbon, alumni cards, event cards, and job preview cards.
  6. Requires `includes/footer.php`.
* **4. Important functions and logic:**
  * Dynamic conditional checking for database connectivity.
  * Array slicing for fallbacks.
  * Output escaping via `htmlspecialchars()`.
* **5. Inputs and outputs:**
  * Inputs: Database query results; session variables (via header).
  * Outputs: HTML document with dynamic card components.
* **6. Database interactions:**
  * Reads from `users` and `alumni_profiles`: `SELECT ... FROM users u JOIN alumni_profiles p ON u.id = p.user_id WHERE u.status = 'active' AND p.is_public = 1 ORDER BY u.id ASC LIMIT 3`.
  * Reads from `events`: `SELECT * FROM events WHERE status = 'approved' ORDER BY event_date ASC LIMIT 3`.
  * Reads from `jobs`: `SELECT * FROM jobs WHERE status = 'approved' ORDER BY posted_at DESC LIMIT 3`.
* **7. Authentication requirements:** `Public` (No login required; adapts navigation if logged in).
* **8. Authorization requirements:** Unrestricted.
* **9. Dependencies:** `includes/header.php`, `includes/footer.php`, `config/db.php`.
* **10. Dependants:** Linked by global logo brand and `Home` navigation item.
* **11. Security considerations:** All dynamic database content is escaped via `htmlspecialchars()` to prevent XSS.
* **12. Current implementation status:** `VERIFIED WORKING`.
* **13. Known issues:** None.
* **14. Manual testing instructions:**
  1. Open `http://localhost/alumni-network/index.php`.
  2. Verify hero banner, statistics, 3 featured alumni, 3 events, and 3 jobs render properly.
  3. Click `Find Alumni` -> verify navigation to `directory.php`.
* **15. Related development phase:** Phase 1 (Foundation & Layout) and Phase 4 (Integration).

---

### File: `about.php`
* **1. File purpose:** Provides background on the KDU Alumni Network, university vision, mission, and leadership.
* **2. Main responsibilities:**
  * Display institutional history and mission statement.
  * Present key leadership messages and organizational pillars.
  * Display campus contact details.
* **3. Execution flow:**
  1. Sets `$pageTitle = 'About Us'` and `$currentPage = 'about'`.
  2. Requires `includes/header.php`.
  3. Renders structured two-column layout and milestone cards.
  4. Requires `includes/footer.php`.
* **4. Important functions and logic:** Static semantic HTML presentation.
* **5. Inputs and outputs:** Outputs static HTML document.
* **6. Database interactions:** None.
* **7. Authentication requirements:** `Public`.
* **8. Authorization requirements:** Unrestricted.
* **9. Dependencies:** `includes/header.php`, `includes/footer.php`.
* **10. Dependants:** Navigation item `About` in header and footer.
* **11. Security considerations:** Pure HTML rendering; immune to external input tampering.
* **12. Current implementation status:** `VERIFIED WORKING`.
* **13. Known issues:** None.
* **14. Manual testing instructions:**
  1. Navigate to `http://localhost/alumni-network/about.php`.
  2. Verify typography, responsive columns, and active navigation indicator.
* **15. Related development phase:** Phase 1 (Foundation & Shared Layout).

---

### File: `directory.php`
* **1. File purpose:** The central alumni discovery portal. Enables members and guests to search and filter alumni across cohorts, degrees, departments, and employers with server-side pagination.
* **2. Main responsibilities:**
  * Process search queries and dropdown filters (`year`, `degree`, `department`).
  * Enforce privacy (`p.is_public = 1` and `u.status = 'active'`).
  * Calculate total matches and enforce pagination (`LIMIT 6 OFFSET ?`).
  * Render alumni profile cards with links to full profiles and direct messaging.
  * Provide dynamic dropdown options populated from distinct database records.
* **3. Execution flow:**
  1. Sets page metadata and includes header and helper functions.
  2. Extracts sanitized `$_GET` parameters: `search`, `year`, `degree`, `department`, `page`.
  3. Constructs dynamic SQL `WHERE` clause with parameterized bindings.
  4. Executes count query to derive `$totalPages = ceil($totalCount / 6)`.
  5. Executes paginated query and fetches matching profiles.
  6. Fetches distinct graduation years and degrees for filter dropdowns.
  7. Renders filter form, card grid, and pagination buttons.
  8. Includes footer.
* **4. Important functions and logic:**
  * Dynamic SQL builder with positional and named PDO parameters.
  * Server-side pagination calculation preserving active filter query parameters.
  * Safe avatar URL resolution via `get_user_avatar_url()`.
* **5. Inputs and outputs:**
  * Inputs: `$_GET['search']`, `$_GET['year']`, `$_GET['degree']`, `$_GET['department']`, `$_GET['page']`.
  * Outputs: Paginated HTML directory grid.
* **6. Database interactions:**
  * `SELECT COUNT(*) FROM users u JOIN alumni_profiles p ON u.id = p.user_id WHERE u.status = 'active' AND p.is_public = 1 [filters]`
  * `SELECT u.id, u.username, u.first_name, u.last_name, u.email, p.* FROM users u JOIN alumni_profiles p ON u.id = p.user_id ... LIMIT 6 OFFSET ?`
  * `SELECT DISTINCT graduation_year FROM alumni_profiles ORDER BY graduation_year DESC`
  * `SELECT DISTINCT degree_programme FROM alumni_profiles ORDER BY degree_programme ASC`
* **7. Authentication requirements:** `Public` (Guests can browse public profiles; message buttons require login).
* **8. Authorization requirements:** Unrestricted for public profiles.
* **9. Dependencies:** `includes/header.php`, `includes/footer.php`, `includes/functions.php`, `config/db.php`.
* **10. Dependants:** Navigation item `Alumni`, homepage CTA buttons.
* **11. Security considerations:** Parameterized queries eliminate SQL injection; page input is strictly sanitized with `max(1, intval($_GET['page']))`; card outputs escaped with `htmlspecialchars()`.
* **12. Current implementation status:** `VERIFIED WORKING`.
* **13. Known issues:** None.
* **14. Manual testing instructions:**
  1. Open `http://localhost/alumni-network/directory.php`.
  2. Search for a name or company -> verify matching cards display.
  3. Filter by graduation year -> verify only matching cohort displays.
  4. Click `Page 2` -> verify next 6 profiles display while keeping active filters.
* **15. Related development phase:** Phase 2 (Alumni Profiles & Directory).

---

### File: `events.php`
* **1. File purpose:** Campus and alumni gatherings hub. Displays approved events, manages member RSVP registrations and cancellations, enforces seat capacity, and accepts new event proposals.
* **2. Main responsibilities:**
  * Display approved events sorted chronologically.
  * Handle member RSVP toggle (`toggle_rsvp` action) with capacity checking.
  * Handle event proposal submission (`create_event` action) with `status = 'pending'`.
  * Display real-time attendance counts and capacity indicators.
  * Provide report modal link for each event card.
* **3. Execution flow:**
  1. Requires header and functions.
  2. Handles POST actions:
     * `toggle_rsvp`: Verifies login, checks event status and capacity, toggles attendance in `event_registrations`.
     * `create_event`: Verifies login, validates title/date/location, inserts record into `events` as `pending`.
  3. Queries approved events with correlated subqueries for attendee counts and current user RSVP status.
  4. Renders event cards, RSVP buttons, proposal form modal, and report modal.
  5. Requires footer.
* **4. Important functions and logic:**
  * State management: checks if event is past (`strtotime($date) < strtotime(today)`), full (`attendee_count >= capacity`), or closed.
  * Dynamic toggle: if user is attending, RSVP button switches to "Cancel RSVP".
* **5. Inputs and outputs:**
  * Inputs: `$_POST['action']`, `$_POST['event_id']`, proposal fields (`title`, `event_date`, `event_time`, `location`, `capacity`, `description`).
  * Outputs: HTML event board with flash messages.
* **6. Database interactions:**
  * `SELECT e.*, (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id AND status = 'attending') AS attendee_count, (SELECT status FROM event_registrations WHERE event_id = e.id AND user_id = ?) AS user_reg_status FROM events e WHERE e.status = 'approved' ORDER BY e.event_date ASC`
  * `INSERT INTO event_registrations (event_id, user_id, status) VALUES (?, ?, 'attending')`
  * `UPDATE event_registrations SET status = 'cancelled' WHERE event_id = ? AND user_id = ?`
  * `INSERT INTO events (title, description, event_date, event_time, location, capacity, organizer_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')`
* **7. Authentication requirements:**
  * Viewing: `Public`.
  * RSVP & Event Creation: Authenticated (`require_login()`).
* **8. Authorization requirements:** Open to logged-in members.
* **9. Dependencies:** `includes/header.php`, `includes/footer.php`, `includes/functions.php`, `config/db.php`.
* **10. Dependants:** Navigation item `Events`, homepage events preview.
* **11. Security considerations:** All inputs sanitized; SQL operations use prepared statements; HTML outputs escaped.
* **12. Current implementation status:** `VERIFIED WORKING`.
* **13. Known issues:** None.
* **14. Manual testing instructions:**
  1. Open `http://localhost/alumni-network/events.php` while logged in.
  2. Click `RSVP Now` -> verify attendee count increments and button changes to `Cancel RSVP`.
  3. Click `Cancel RSVP` -> verify attendee count decrements.
  4. Submit an event via the proposal modal -> verify pending confirmation message.
* **15. Related development phase:** Phase 3 (Events, RSVP, Job Board).

---

### File: `event.php`
* **1. File purpose:** Backward-compatible router and REST JSON adapter for events. Directs browser visitors to `events.php` while serving JSON arrays for AJAX queries.
* **2. Main responsibilities:**
  * Route web visitors to `events.php`: `header("Location: events.php")`.
  * Respond to `?action=get` with JSON array of events (`Content-Type: application/json`).
  * Handle `?action=save` from prototype forms and redirect back.
* **3. Execution flow:**
  1. Checks if request is standard browser navigation without action; if so, redirects to `events.php`.
  2. Requires `config/db.php`.
  3. Evaluates `$action` (`get` or `save`).
  4. Returns JSON or handles insert and redirects.
* **4. Important functions and logic:** JSON encoding via `json_encode()`.
* **5. Inputs and outputs:** `$_GET['action']`, `$_POST['action']`; outputs JSON or 302 redirect.
* **6. Database interactions:**
  * `SELECT id, title, event_date AS date, start_time AS start, end_time AS end, location, reg_date AS regDate, status FROM events ORDER BY event_date ASC`
  * `INSERT INTO events (title, event_date, start_time, end_time, location, reg_date, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')`
* **7. Authentication requirements:** Mixed.
* **8. Authorization requirements:** Open for reads; member for inserts.
* **9. Dependencies:** `config/db.php`.
* **10. Dependants:** Prototype scripts (`event.js`).
* **11. Security considerations:** Prepared statements used for inserts.
* **12. Current implementation status:** `VERIFIED WORKING`.
* **13. Known issues:** None.
* **14. Manual testing instructions:**
  1. Open `http://localhost/alumni-network/event.php` -> verify immediate redirection to `events.php`.
  2. Request `http://localhost/alumni-network/event.php?action=get` -> verify valid JSON array.
* **15. Related development phase:** Phase 3 (Events Hub).

---

### File: `jobs.php`
* **1. File purpose:** The career board of the KDU Alumni Network. Catalogs job vacancies and internships, enables direct cover-letter applications, prevents duplicate applications, and allows members to submit new postings.
* **2. Main responsibilities:**
  * Display approved career postings sorted by submission date.
  * Filter jobs by job type (`Full-Time`, `Part-Time`, `Remote`, `Internship`).
  * Process internal job applications (`apply_job` action) with duplicate prevention.
  * Process new vacancy submissions (`post_job` action) with `status = 'pending'`.
  * Display application status badges (`Applied`) for current user.
* **3. Execution flow:**
  1. Requires header and functions.
  2. Handles POST actions:
     * `apply_job`: Verifies login, checks for existing application in `job_applications`, inserts application.
     * `post_job`: Verifies login, validates fields, inserts job as `pending`.
  3. Queries approved jobs joined with poster details and application status for current user.
  4. Renders job cards, application modal, vacancy posting modal, and report modal.
  5. Requires footer.
* **4. Important functions and logic:**
  * Duplicate application guard via database check before insert.
  * Dynamic button states: "Apply for Position" vs "Applied" badge.
* **5. Inputs and outputs:**
  * Inputs: `$_GET['type']`, `$_POST['action']`, `$_POST['job_id']`, `$_POST['application_message']`, vacancy fields.
  * Outputs: HTML career board with confirmation banners.
* **6. Database interactions:**
  * `SELECT j.*, CONCAT(u.first_name, ' ', u.last_name) AS poster_name, (SELECT status FROM job_applications WHERE job_id = j.id AND user_id = ?) AS user_app_status FROM jobs j LEFT JOIN users u ON j.posted_by = u.id WHERE j.status = 'approved' [filter] ORDER BY j.posted_at DESC`
  * `INSERT INTO job_applications (job_id, user_id, message, status, applied_at) VALUES (?, ?, ?, 'Applied', NOW())`
  * `INSERT INTO jobs (title, company, location, job_type, description, requirements, deadline, application_link, posted_by, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')`
* **7. Authentication requirements:**
  * Browsing: `Public`.
  * Applying & Posting: Authenticated (`require_login()`).
* **8. Authorization requirements:** Open to logged-in members.
* **9. Dependencies:** `includes/header.php`, `includes/footer.php`, `includes/functions.php`, `config/db.php`.
* **10. Dependants:** Navigation item `Jobs`, homepage jobs preview.
* **11. Security considerations:** Server-side input validation, prepared statements, and output escaping.
* **12. Current implementation status:** `VERIFIED WORKING`.
* **13. Known issues:** None.
* **14. Manual testing instructions:**
  1. Open `http://localhost/alumni-network/jobs.php` while logged in.
  2. Click `Apply for Position`, enter cover note, and submit -> verify status updates to `Applied`.
  3. Propose a job posting -> verify success confirmation that vacancy is queued for admin review.
* **15. Related development phase:** Phase 3 (Job Board & Applications).

---

### File: `job.php`
* **1. File purpose:** Backward-compatible router and REST JSON adapter for job listings. Routes web visits to `jobs.php` while fulfilling JSON requests from prototype scripts.
* **2. Main responsibilities:**
  * Automatically applies schema migrations for older tables if needed.
  * Redirects browser visits to `jobs.php`.
  * Serves JSON array of jobs on `?action=get`.
  * Handles job creation on `?action=create` returning JSON status.
* **3. Execution flow:**
  1. Checks for direct browser visits and redirects to `jobs.php`.
  2. Requires `config/db.php`.
  3. Executes schema migration checks (`contact_email`, `status`).
  4. Evaluates `$action` (`get` or `create`).
* **4. Important functions and logic:** `isAjax()` helper detecting asynchronous XMLHttpRequests.
* **5. Inputs and outputs:** `$_GET['action']`, `$_POST['action']`; outputs JSON or redirect.
* **6. Database interactions:**
  * `SELECT * FROM jobs WHERE status = :status ORDER BY id DESC`
  * `INSERT INTO jobs (title, company, location, job_type, description, status, contact_email) VALUES (?, ?, ?, ?, ?, 'pending', ?)`
* **7. Authentication requirements:** Mixed.
* **8. Authorization requirements:** Open reads; member inserts.
* **9. Dependencies:** `config/db.php`.
* **10. Dependants:** Legacy prototype scripts (`jobs.js`, `jobadmin.js`).
* **11. Security considerations:** Parameterized queries; validates mandatory fields.
* **12. Current implementation status:** `VERIFIED WORKING`.
* **13. Known issues:** None.
* **14. Manual testing instructions:**
  1. Open `http://localhost/alumni-network/job.php` -> verify redirect to `jobs.php`.
  2. Request `http://localhost/alumni-network/job.php?action=get` -> verify JSON payload.
* **15. Related development phase:** Phase 3 (Job Board).

---

### File: `profile.php`
* **1. File purpose:** The alumni profile management and viewing engine. Displays comprehensive graduate records and provides profile editing, avatar photo upload, and privacy controls for account owners.
* **2. Main responsibilities:**
  * Display academic degree, cohort, employer, skills, bio, and LinkedIn link.
  * Enable profile owners to update details with email uniqueness validation.
  * Enable profile owners to upload avatar images with MIME validation.
  * Show `Send Message` and `Report Profile` actions when viewing other members.
* **3. Execution flow:**
  1. Requires header and functions.
  2. Resolves target profile ID (`$_GET['id']` or `current_user_id()`).
  3. Verifies ownership: `$isOwner = ($currentUserId && $viewUserId == $currentUserId)`.
  4. Handles POST actions (owner only):
     * `upload_photo`: Checks image size (<2MB), inspects MIME with `getimagesize()`, validates format (JPG, PNG, WEBP), generates random filename, saves to `uploads/profiles/`, updates database.
     * `update_profile`: Validates inputs, checks email collision, executes multi-table transaction on `users` and `alumni_profiles`, updates active session.
  5. Queries full profile record joined across `users` and `alumni_profiles`.
  6. Renders profile card, view tab, edit tab, photo upload tab, and report modal.
  7. Requires footer.
* **4. Important functions and logic:**
  * Multi-table PDO transaction handling (`beginTransaction`, `commit`, `rollBack`).
  * Image inspection using native `getimagesize()`.
  * Cryptographically secure filename generation: `avatar_{id}_{time}_{random}.{ext}`.
* **5. Inputs and outputs:**
  * Inputs: `$_GET['id']`, `$_POST['action']`, `$_FILES['profile_photo']`, profile fields.
  * Outputs: HTML profile interface with flash alerts.
* **6. Database interactions:**
  * `SELECT u.id, u.username, u.first_name, u.last_name, u.email, u.role, u.status, p.* FROM users u LEFT JOIN alumni_profiles p ON u.id = p.user_id WHERE u.id = ? LIMIT 1`
  * `UPDATE alumni_profiles SET profile_picture = ? WHERE user_id = ?`
  * `UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ?`
  * `UPDATE alumni_profiles SET graduation_year = ?, degree_programme = ?, department = ?, current_job_title = ?, current_company = ?, location = ?, phone = ?, linkedin_url = ?, bio = ?, skills = ? WHERE user_id = ?`
* **7. Authentication requirements:**
  * Viewing: `Public` (for public profiles); requires login if viewed without query ID.
  * Editing: Strictly authenticated owner only.
* **8. Authorization requirements:** Ownership guard (`$isOwner`).
* **9. Dependencies:** `includes/header.php`, `includes/footer.php`, `includes/functions.php`, `config/db.php`.
* **10. Dependants:** Directory profile links, user navigation menu.
* **11. Security considerations:** File uploads validated by MIME check and size cap; random filenames prevent directory traversal; upload folder execution denied by `.htaccess`; database updates executed in transaction.
* **12. Current implementation status:** `VERIFIED WORKING`.
* **13. Known issues:** None.
* **14. Manual testing instructions:**
  1. Log in and open `http://localhost/alumni-network/profile.php`.
  2. Edit bio and job title -> save and verify changes reflect immediately.
  3. Upload an avatar image (<2MB) -> verify photo updates and file appears in `uploads/profiles/`.
  4. View another user's profile (`profile.php?id=1`) -> verify edit tabs are absent.
* **15. Related development phase:** Phase 2 (Alumni Profiles & Directory).

---

### File: `messages.php`
* **1. File purpose:** The private 1-to-1 direct messaging and alumni networking communication hub. Provides conversation indexing, unread notification tracking, automatic chronological transcripts, and real-time message sending.
* **2. Main responsibilities:**
  * Require login and enforce participant-level access authorization.
  * Auto-initialize conversation threads when visited with `?user_id=X`.
  * Render two-pane interface: inbox list on left, active chat transcript on right.
  * Handle message sending (`send_message` action) and update conversation timestamps.
  * Mark unread messages as read upon viewing.
  * Provide autoscroll script to focus on the latest message.
* **3. Execution flow:**
  1. Requires header and functions; calls `require_login()`.
  2. If `user_id` is supplied, checks for existing conversation or creates one with ordered IDs (`user_one_id = min(u1, u2)`).
  3. If POST `send_message`: verifies user is participant, inserts message into `messages`, updates `conversations.updated_at`.
  4. Queries user's conversations with preview of last message and unread counts.
  5. If conversation is active: verifies authorization, updates unread messages to `is_read = 1`, queries full message transcript.
  6. Renders conversation list and chat bubbles.
  7. Requires footer.
* **4. Important functions and logic:**
  * Conversation canonical ordering: `min($u1, $u2)` and `max($u1, $u2)` prevents duplicate reverse threads.
  * Access authorization: blocks users from reading conversations they do not belong to.
  * Client-side autoscroll to bottom.
* **5. Inputs and outputs:**
  * Inputs: `$_GET['conversation_id']`, `$_GET['user_id']`, `$_POST['action']`, `$_POST['message']`.
  * Outputs: HTML messaging interface.
* **6. Database interactions:**
  * `SELECT id FROM conversations WHERE user_one_id = ? AND user_two_id = ? LIMIT 1`
  * `INSERT INTO conversations (user_one_id, user_two_id) VALUES (?, ?)`
  * `SELECT c.*, u.id AS other_user_id, ... FROM conversations c ... WHERE c.user_one_id = ? OR c.user_two_id = ? ORDER BY c.updated_at DESC`
  * `SELECT m.*, CONCAT(u.first_name, ' ', u.last_name) AS sender_name FROM messages m JOIN users u ON m.sender_id = u.id WHERE m.conversation_id = ? ORDER BY m.sent_at ASC`
  * `UPDATE messages SET is_read = 1 WHERE conversation_id = ? AND receiver_id = ? AND is_read = 0`
  * `INSERT INTO messages (conversation_id, sender_id, receiver_id, message, is_read, sent_at) VALUES (?, ?, ?, ?, 0, NOW())`
* **7. Authentication requirements:** Authenticated Members Only (`require_login()`).
* **8. Authorization requirements:** Participant-level ownership check.
* **9. Dependencies:** `includes/header.php`, `includes/footer.php`, `includes/functions.php`, `config/db.php`.
* **10. Dependants:** Navigation item `Messages` (with unread badge), profile and directory `Message` buttons.
* **11. Security considerations:** Strict authorization check prevents unauthorized message reading; all text escaped with `htmlspecialchars()` to prevent stored XSS; prepared statements prevent SQL injection.
* **12. Current implementation status:** `VERIFIED WORKING`.
* **13. Known issues:** None.
* **14. Manual testing instructions:**
  1. Log in as Alumnus A and navigate to `messages.php?user_id=[Alumnus B ID]`.
  2. Send message "Testing alumni message delivery".
  3. Verify message appears in sent bubble.
  4. Log in as Alumnus B -> verify unread badge in navbar and read message in chat.
* **15. Related development phase:** Phase 4 (Messaging & Administration).

---

### File: `conversation.php`
* **1. File purpose:** Route alias for conversation links. Extracts conversation or user ID parameters and forwards to `messages.php`.
* **2. Main responsibilities:** Forward requests to `messages.php` with appropriate query string.
* **3. Execution flow:**
  1. Requires `config/db.php` and calls `require_login()`.
  2. Inspects `$_GET['id']`, `$_GET['conversation_id']`, or `$_GET['user_id']`.
  3. Emits 302 redirect header: `Location: messages.php?...`.
* **4. Important functions and logic:** Query extraction and header redirection.
* **5. Inputs and outputs:** `$_GET` parameters; outputs HTTP 302 redirect.
* **6. Database interactions:** None.
* **7. Authentication requirements:** Authenticated (`require_login()`).
* **8. Authorization requirements:** Enforced upon landing on `messages.php`.
* **9. Dependencies:** `config/db.php`.
* **10. Dependants:** External email links or bookmarks.
* **11. Security considerations:** Clean parameter casting via `intval()`.
* **12. Current implementation status:** `VERIFIED WORKING`.
* **13. Known issues:** None.
* **14. Manual testing instructions:** Visit `http://localhost/alumni-network/conversation.php?user_id=2` -> verify redirect to `messages.php?user_id=2`.
* **15. Related development phase:** Phase 4 (Messaging).

---

### File: `send_message.php`
* **1. File purpose:** Direct standalone POST action endpoint for sending messages from external modal dialogs or directory cards.
* **2. Main responsibilities:**
  * Validate sender and receiver IDs.
  * Find or create conversation thread.
  * Insert message into `messages` table.
  * Redirect user directly into the active conversation thread.
* **3. Execution flow:**
  1. Requires `config/db.php` and calls `require_login()`.
  2. Verifies POST method.
  3. Finds or creates conversation with `min()` and `max()` ID ordering.
  4. Inserts message and updates conversation timestamp.
  5. Redirects to `messages.php?conversation_id=X`.
* **4. Important functions and logic:** Atomic conversation creation and message insertion.
* **5. Inputs and outputs:** `$_POST['receiver_id']`, `$_POST['message']`; outputs HTTP 302 redirect.
* **6. Database interactions:**
  * Reads/Writes to `conversations` and `messages`.
* **7. Authentication requirements:** Authenticated (`require_login()`).
* **8. Authorization requirements:** Verified participant.
* **9. Dependencies:** `config/db.php`.
* **10. Dependants:** Quick message modals on `directory.php` and `profile.php`.
* **11. Security considerations:** Prepared statements; input trimming.
* **12. Current implementation status:** `VERIFIED WORKING`.
* **13. Known issues:** None.
* **14. Manual testing instructions:** Submit message POST request -> verify message persists in DB and user lands on `messages.php`.
* **15. Related development phase:** Phase 4 (Messaging).

---

## 2. Authentication Suite

### File: `auth/login.php`
* **1. File purpose:** The authentication gateway. Verifies user credentials, validates account status, regenerates session IDs, and redirects users according to role.
* **2. Main responsibilities:**
  * Render cinematic split layout (KDU branding on left, login form on right).
  * Validate identity (email or username) and password.
  * Verify password hash using `password_verify()`.
  * Check account suspension status.
  * Regenerate session ID and populate session variables.
  * Route administrators to `admin/index.php` and alumni to `index.php`.
* **3. Execution flow:**
  1. Requires `config/db.php`.
  2. On POST: extracts `login_identity` and `password`.
  3. Queries `users` table by email or username.
  4. Validates hash via `password_verify()`.
  5. If valid and active: calls `session_regenerate_id(true)`, sets session vars, and redirects.
  6. If invalid: displays error alert without leaking account existence.
* **4. Important functions and logic:**
  * Constant-time password verification via native `password_verify()`.
  * Session fixation defense via `session_regenerate_id(true)`.
* **5. Inputs and outputs:** `$_POST['login_identity']`, `$_POST['password']`; outputs HTML form or 302 redirect.
* **6. Database interactions:** `SELECT * FROM users WHERE email = ? OR username = ? LIMIT 1`.
* **7. Authentication requirements:** `Public`.
* **8. Authorization requirements:** Unrestricted.
* **9. Dependencies:** `config/db.php`.
* **10. Dependants:** Top navigation `Login` link, protected route redirects.
* **11. Security considerations:** Generic error messages prevent user enumeration; BCRYPT verification; session ID regeneration.
* **12. Current implementation status:** `VERIFIED WORKING`.
* **13. Known issues:** None.
* **14. Manual testing instructions:**
  1. Open `http://localhost/alumni-network/auth/login.php`.
  2. Submit invalid password -> verify "Invalid email/username or password" error.
  3. Submit `admin@alumni.edu` / `Admin@123` -> verify redirect to `admin/index.php`.
* **15. Related development phase:** Phase 1 (Architecture & Authentication).

---

### File: `auth/register.php`
* **1. File purpose:** New member registration and academic onboarding portal.
* **2. Main responsibilities:**
  * Render registration form collecting name, username, email, degree, department, cohort, and password.
  * Perform comprehensive server-side input validation.
  * Verify email and username uniqueness.
  * Hash password with BCRYPT.
  * Atomically create records in `users` and `alumni_profiles` via PDO transaction.
  * Automatically log user in and redirect to `profile.php`.
* **3. Execution flow:**
  1. Requires `config/db.php`.
  2. On POST: validates required fields, email format, password length (>=8), password confirmation match, and graduation year range (1960–2030).
  3. Checks for duplicate email or username.
  4. Hashes password with `password_hash($password, PASSWORD_BCRYPT)`.
  5. Executes transaction: inserts into `users`, retrieves ID, inserts into `alumni_profiles`, commits.
  6. Regenerates session, initializes login, and redirects.
* **4. Important functions and logic:** Multi-table transaction (`beginTransaction`, `commit`).
* **5. Inputs and outputs:** POST registration fields; outputs HTML form or 302 redirect.
* **6. Database interactions:**
  * `SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1`
  * `INSERT INTO users (username, first_name, last_name, email, password, role, status) VALUES (?, ?, ?, ?, ?, 'alumni', 'active')`
  * `INSERT INTO alumni_profiles (user_id, graduation_year, degree_programme, department, profile_picture, is_public) VALUES (?, ?, ?, ?, 'default-avatar.svg', 1)`
* **7. Authentication requirements:** `Public`.
* **8. Authorization requirements:** Unrestricted.
* **9. Dependencies:** `config/db.php`.
* **10. Dependants:** Top navigation `Join Network` link, login page link.
* **11. Security considerations:** Strong BCRYPT hashing; server-side validation; transactional consistency; prepared statements.
* **12. Current implementation status:** `VERIFIED WORKING`.
* **13. Known issues:** None.
* **14. Manual testing instructions:**
  1. Open `http://localhost/alumni-network/auth/register.php`.
  2. Submit form with existing email -> verify collision error.
  3. Submit valid new alumnus record -> verify auto-login and landing on `profile.php`.
* **15. Related development phase:** Phase 1 (Authentication).

---

### File: `auth/logout.php`
* **1. File purpose:** Secure session termination handler.
* **2. Main responsibilities:** Clear session array, invalidate session cookie, destroy session data, redirect to homepage.
* **3. Execution flow:**
  1. Starts session if none exists.
  2. Flushes `$_SESSION = []`.
  3. Deletes session cookie by setting past timestamp.
  4. Calls `session_destroy()`.
  5. Emits `Location: ../index.php`.
* **4. Important functions and logic:** Standard secure session destruction.
* **5. Inputs and outputs:** Active session; outputs 302 redirect.
* **6. Database interactions:** None.
* **7. Authentication requirements:** `Public` / Authenticated.
* **8. Authorization requirements:** Unrestricted.
* **9. Dependencies:** None.
* **10. Dependants:** Top navigation `Logout` button, admin sidebar logout.
* **11. Security considerations:** Complete invalidation of server-side state and client cookie.
* **12. Current implementation status:** `VERIFIED WORKING`.
* **13. Known issues:** None.
* **14. Manual testing instructions:** Log in, navigate to `auth/logout.php`, verify return to `index.php` as guest.
* **15. Related development phase:** Phase 1 (Authentication).

---

## 3. Administration Suite

### File: `admin/index.php`
* **1. File purpose:** Central management dashboard for university administrators. Aggregates system metrics and displays moderation queues.
* **2. Main responsibilities:**
  * Enforce administrator access control via `require_admin()`.
  * Compute statistics: total/active/suspended users, pending/approved events, pending/approved jobs, pending reports.
  * Display 5 most recent pending events and job opportunities with quick approval buttons.
* **3. Execution flow:**
  1. Requires `config/db.php`; calls `require_admin()`.
  2. Executes count queries for users, events, jobs, and reports.
  3. Fetches recent pending events and jobs.
  4. Renders metrics tiles and moderation queues.
* **4. Important functions and logic:** Statistical aggregation via PDO `fetchColumn()`.
* **5. Inputs and outputs:** Database counts; outputs HTML dashboard.
* **6. Database interactions:** Queries `users`, `events`, `jobs`, `reports`.
* **7. Authentication requirements:** Administrator (`role = 'admin'`).
* **8. Authorization requirements:** Administrator only.
* **9. Dependencies:** `config/db.php`, `admin/includes/sidebar.php`.
* **10. Dependants:** Header admin link, admin navigation.
* **11. Security considerations:** Protected by `require_admin()`; non-admins redirected to login.
* **12. Current implementation status:** `VERIFIED WORKING`.
* **13. Known issues:** None.
* **14. Manual testing instructions:** Log in as admin, open `admin/index.php`, verify metric cards reflect accurate counts.
* **15. Related development phase:** Phase 4 (Administration).

---

### File: `admin/users.php`
* **1. File purpose:** User lifecycle management interface. Allows administrators to search, filter, activate, suspend, change roles, and delete user accounts.
* **2. Main responsibilities:**
  * Enforce `require_admin()`.
  * Process actions: `change_status` (active/suspended), `change_role` (admin/alumni), `delete_user`.
  * Protect root admin (User ID 1) from deletion or suspension.
  * Prevent administrators from suspending or deleting their own session.
  * Provide search and filter by name, email, username, company, role, status.
* **3. Execution flow:**
  1. Requires `config/db.php` and functions; calls `require_admin()`.
  2. On POST: validates target user ID, checks root admin and self-action safety guards, executes update or delete query.
  3. Queries users matching search criteria with joined profile data.
  4. Renders user table and action controls.
* **4. Important functions and logic:** Safety guards preventing root admin deletion and admin self-lockout.
* **5. Inputs and outputs:** `$_POST['action']`, `$_POST['user_id']`, filter parameters; outputs HTML management table.
* **6. Database interactions:** `UPDATE users ...`, `DELETE FROM users ...`, `SELECT u.*, p.* FROM users u ...`.
* **7. Authentication requirements:** Administrator.
* **8. Authorization requirements:** Administrator only.
* **9. Dependencies:** `config/db.php`, `admin/includes/sidebar.php`, `includes/functions.php`.
* **10. Dependants:** Admin sidebar `Users` link.
* **11. Security considerations:** Root admin protection; parameter binding; output escaping.
* **12. Current implementation status:** `VERIFIED WORKING`.
* **13. Known issues:** None.
* **14. Manual testing instructions:** In admin panel, search for a user, toggle status to `suspended`, verify user cannot log in, toggle back to `active`.
* **15. Related development phase:** Phase 4 (Administration & Moderation).

---

### File: `admin/events.php`
* **1. File purpose:** Event moderation interface. Allows administrators to review, approve, reject, or delete submitted events.
* **2. Main responsibilities:**
  * Enforce `require_admin()`.
  * Process actions: `approve`, `reject`, `delete`.
  * Query all events ordered with `pending` items first.
  * Display event details, organizer info, capacity, and action buttons.
* **3. Execution flow:**
  1. Requires `config/db.php`; calls `require_admin()`.
  2. On POST: evaluates action and updates or deletes record in `events`.
  3. Queries all events with organizer details.
  4. Renders moderation table.
* **4. Important functions and logic:** Integer casting of `$_POST['event_id']`; prepared queries.
* **5. Inputs and outputs:** `$_POST['event_id']`, `$_POST['action']`; outputs HTML table.
* **6. Database interactions:** `UPDATE events SET status = ? WHERE id = ?`, `DELETE FROM events WHERE id = ?`, `SELECT e.*, ... FROM events e ...`.
* **7. Authentication requirements:** Administrator.
* **8. Authorization requirements:** Administrator only.
* **9. Dependencies:** `config/db.php`, `admin/includes/sidebar.php`.
* **10. Dependants:** Admin sidebar `Events` link.
* **11. Security considerations:** Prepared statements; admin authorization.
* **12. Current implementation status:** `VERIFIED WORKING`.
* **13. Known issues:** None.
* **14. Manual testing instructions:** Submit an event as an alumnus, navigate to `admin/events.php`, click `Approve`, verify status updates and event appears on public page.
* **15. Related development phase:** Phase 3 & Phase 4 (Moderation).

---

### File: `admin/jobs.php`
* **1. File purpose:** Career opportunity moderation interface. Allows administrators to review, approve, reject, or delete submitted vacancies.
* **2. Main responsibilities:**
  * Enforce `require_admin()`.
  * Process actions: `approve`, `reject`, `delete`.
  * Query all career listings with `pending` items prioritized.
  * Display job title, company, type, submitter, and action buttons.
* **3. Execution flow:**
  1. Requires `config/db.php`; calls `require_admin()`.
  2. On POST: updates or deletes record in `jobs`.
  3. Queries all vacancies.
  4. Renders moderation table.
* **4. Important functions and logic:** Prepared statement execution.
* **5. Inputs and outputs:** `$_POST['job_id']`, `$_POST['action']`; outputs HTML table.
* **6. Database interactions:** `UPDATE jobs SET status = ? WHERE id = ?`, `DELETE FROM jobs WHERE id = ?`, `SELECT j.*, ... FROM jobs j ...`.
* **7. Authentication requirements:** Administrator.
* **8. Authorization requirements:** Administrator only.
* **9. Dependencies:** `config/db.php`, `admin/includes/sidebar.php`.
* **10. Dependants:** Admin sidebar `Jobs` link.
* **11. Security considerations:** Prepared statements; admin check.
* **12. Current implementation status:** `VERIFIED WORKING`.
* **13. Known issues:** None.
* **14. Manual testing instructions:** Propose a job posting, open `admin/jobs.php`, approve the job, verify it appears on `jobs.php`.
* **15. Related development phase:** Phase 3 & Phase 4 (Moderation).

---

### File: `admin/reports.php`
* **1. File purpose:** Trust, safety, and community moderation centre. Collects and displays reports submitted by members regarding inappropriate messages, fraudulent jobs, spam events, or abusive profiles.
* **2. Main responsibilities:**
  * Enforce `require_admin()`.
  * Process actions: `set_status` (reviewed, resolved, dismissed) and `suspend_user`.
  * Protect root admin from suspension.
  * Query reports joined with reporter, accused user, event, and job details.
* **3. Execution flow:**
  1. Requires `config/db.php` and functions; calls `require_admin()`.
  2. On POST: evaluates action; updates report status or suspends accused user.
  3. Queries reports with left joins across target entities.
  4. Renders report cards with resolution controls.
* **4. Important functions and logic:** Multi-entity left join query; status update handling.
* **5. Inputs and outputs:** `$_POST['report_id']`, `$_POST['action']`, `$_POST['new_status']`, `$_POST['target_user_id']`; outputs HTML moderation table.
* **6. Database interactions:** `UPDATE reports SET status = ? WHERE id = ?`, `UPDATE users SET status = 'suspended' WHERE id = ?`, multi-table `SELECT` from `reports`.
* **7. Authentication requirements:** Administrator.
* **8. Authorization requirements:** Administrator only.
* **9. Dependencies:** `config/db.php`, `admin/includes/sidebar.php`, `includes/functions.php`.
* **10. Dependants:** Admin sidebar `Reports` link.
* **11. Security considerations:** Root admin protected from suspension; prepared statements.
* **12. Current implementation status:** `VERIFIED WORKING`.
* **13. Known issues:** None.
* **14. Manual testing instructions:** Submit a report from an event or profile, open `admin/reports.php`, change status to `Resolved`, verify update.
* **15. Related development phase:** Phase 4 (Trust & Safety Moderation).

---

### File: `admin/settings.php`
* **1. File purpose:** Platform diagnostic and configuration status viewer.
* **2. Main responsibilities:**
  * Display runtime PHP version, database connectivity, database name, and session cookie configuration.
  * Provide link to database re-seeder tool (`config/setup.php`).
* **3. Execution flow:**
  1. Requires `config/db.php`; calls `require_admin()`.
  2. Inspects PHP runtime parameters.
  3. Renders diagnostic summary cards.
* **4. Important functions and logic:** Native PHP diagnostic queries (`phpversion()`).
* **5. Inputs and outputs:** Environment state; outputs HTML diagnostic grid.
* **6. Database interactions:** None.
* **7. Authentication requirements:** Administrator.
* **8. Authorization requirements:** Administrator only.
* **9. Dependencies:** `config/db.php`, `admin/includes/sidebar.php`.
* **10. Dependants:** Admin sidebar `Settings` link.
* **11. Security considerations:** Does not expose raw database passwords or server keys.
* **12. Current implementation status:** `VERIFIED WORKING`.
* **13. Known issues:** None.
* **14. Manual testing instructions:** Open `admin/settings.php` as admin, verify diagnostic parameters display accurately.
* **15. Related development phase:** Phase 4 (Administration).

---

### File: `admin/includes/sidebar.php`
* **1. File purpose:** Persistent administrative navigation component.
* **2. Main responsibilities:**
  * Render vertical navigation links for Dashboard, Users, Events, Jobs, Reports, and Settings.
  * Display active indicator based on `$currentAdminPage`.
  * Display dynamic pending reports count badge via `get_pending_reports_count()`.
  * Provide links to view public site and log out.
* **3. Execution flow:** Included in admin layouts; evaluates current page and renders sidebar HTML.
* **4. Important functions and logic:** Dynamic pending report counter.
* **5. Inputs and outputs:** `$currentAdminPage`; outputs HTML sidebar.
* **6. Database interactions:** `SELECT COUNT(*) FROM reports WHERE status = 'pending'`.
* **7. Authentication requirements:** Administrator.
* **8. Authorization requirements:** Administrator only.
* **9. Dependencies:** `config/db.php`.
* **10. Dependants:** All views in `admin/`.
* **11. Security considerations:** Rendered within authenticated admin pages.
* **12. Current implementation status:** `VERIFIED WORKING`.
* **13. Known issues:** None.
* **14. Manual testing instructions:** Navigate across admin views -> verify active link changes and pending report badge reflects database state.
* **15. Related development phase:** Phase 4 (Administration).

---

## 4. Action Endpoints

### File: `actions/report.php`
* **1. File purpose:** Background HTTP POST action handler for filing content or user misconduct reports.
* **2. Main responsibilities:**
  * Require login via `require_login()`.
  * Extract reporter ID, target entity ID, reason category, and narrative description.
  * Insert report into `reports` table with status `pending`.
  * Redirect user back to the referring page with `?reported=1`.
* **3. Execution flow:**
  1. Requires `config/db.php`; calls `require_login()`.
  2. Verifies POST method.
  3. Extracts parameters and inserts into `reports`.
  4. Emits 302 redirect back to `$_SERVER['HTTP_REFERER']`.
* **4. Important functions and logic:** Safe referer redirect handling.
* **5. Inputs and outputs:** POST report parameters; outputs HTTP 302 redirect.
* **6. Database interactions:** `INSERT INTO reports (reporter_id, reported_user_id, event_id, job_id, message_id, reason, description, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')`.
* **7. Authentication requirements:** Authenticated Members Only.
* **8. Authorization requirements:** Logged-in member.
* **9. Dependencies:** `config/db.php`.
* **10. Dependants:** Report modals on `profile.php`, `events.php`, `jobs.php`, `messages.php`.
* **11. Security considerations:** Prepared statement execution; non-POST visits redirected.
* **12. Current implementation status:** `VERIFIED WORKING`.
* **13. Known issues:** None.
* **14. Manual testing instructions:** Submit report from an event modal -> verify redirect back to event page with `?reported=1` and record in `reports` table.
* **15. Related development phase:** Phase 4 (Trust & Safety).

---

## 5. Shared Includes & Global Helpers

### File: `includes/header.php`
* **1. File purpose:** Universal header template included across all public and member pages.
* **2. Main responsibilities:**
  * Require `config/db.php` first to guarantee session hardening before output.
  * Determine user avatar and retrieve unread message count.
  * Render HTML5 document head, stylesheets, brand logo, and responsive navigation bar.
  * Display dynamic navigation items based on authentication state (Guest vs Member vs Admin).
* **3. Execution flow:** Evaluates session variables, queries unread count, renders HTML header.
* **4. Important functions and logic:** Dynamic unread notification counter.
* **5. Inputs and outputs:** Session state; outputs top HTML markup and navigation bar.
* **6. Database interactions:**
  * `SELECT profile_picture FROM alumni_profiles WHERE user_id = ? LIMIT 1`
  * `SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0`
* **7. Authentication requirements:** Mixed (adapts dynamically).
* **8. Authorization requirements:** Unrestricted.
* **9. Dependencies:** `config/db.php`, `assets/css/style.css`.
* **10. Dependants:** Included by all top-level user pages.
* **11. Security considerations:** Escapes title and user name; enforces early session startup.
* **12. Current implementation status:** `VERIFIED WORKING`.
* **13. Known issues:** None.
* **14. Manual testing instructions:** Load any page -> verify navbar renders; log in -> verify member options and unread badge appear.
* **15. Related development phase:** Phase 1 (Foundation & Layout).

---

### File: `includes/footer.php`
* **1. File purpose:** Universal footer template closing the DOM on user-facing pages.
* **2. Main responsibilities:**
  * Render multi-column footer with quick links, university branding, and copyright.
  * Load client-side script `assets/js/main.js`.
* **3. Execution flow:** Closes open HTML tags and loads JavaScript.
* **4. Important functions and logic:** Standard template closing.
* **5. Inputs and outputs:** Outputs closing HTML markup.
* **6. Database interactions:** None.
* **7. Authentication requirements:** Public.
* **8. Authorization requirements:** Unrestricted.
* **9. Dependencies:** `assets/js/main.js`.
* **10. Dependants:** Included by all top-level user pages.
* **11. Security considerations:** None.
* **12. Current implementation status:** `VERIFIED WORKING`.
* **13. Known issues:** None.
* **14. Manual testing instructions:** Verify footer renders properly at the bottom of all pages.
* **15. Related development phase:** Phase 1 (Shared Layout).

---

### File: `includes/functions.php`
* **1. File purpose:** Central repository of general utility functions.
* **2. Main responsibilities:**
  * Provide XSS escaping helper: `sanitize_output($text)`.
  * Compute relative time strings: `time_ago($datetime)`.
  * Resolve avatar paths with fallback: `get_user_avatar_url($profile_picture)`.
* **3. Execution flow:** Defines helper functions when included.
* **4. Important functions and logic:**
  * `sanitize_output()`: wraps `htmlspecialchars($text, ENT_QUOTES, 'UTF-8')`.
  * `time_ago()`: computes relative duration (`Just now`, `5m ago`, `2h ago`, `3d ago`).
  * `get_user_avatar_url()`: verifies file presence on disk before returning path.
* **5. Inputs and outputs:** Helper function inputs and formatted return values.
* **6. Database interactions:** None.
* **7. Authentication requirements:** Universal helper.
* **8. Authorization requirements:** Unrestricted.
* **9. Dependencies:** `config/db.php`.
* **10. Dependants:** `directory.php`, `profile.php`, `messages.php`, `admin/users.php`.
* **11. Security considerations:** Implements consistent output escaping.
* **12. Current implementation status:** `VERIFIED WORKING`.
* **13. Known issues:** None.
* **14. Manual testing instructions:** Call `time_ago()` with a past timestamp -> verify formatted relative string.
* **15. Related development phase:** Phase 1 (Foundation).

---

### File: `includes/auth.php`
* **1. File purpose:** Authentication loader bridge.
* **2. Main responsibilities:** Includes `config/db.php` to ensure authentication functions (`is_logged_in()`, `current_user_id()`, `is_admin()`, `require_login()`, `require_admin()`) are available.
* **3. Execution flow:** Imports `config/db.php`.
* **4. Important functions and logic:** Exposes authentication guards.
* **5. Inputs and outputs:** None.
* **6. Database interactions:** None.
* **7. Authentication requirements:** Universal helper.
* **8. Authorization requirements:** Unrestricted.
* **9. Dependencies:** `config/db.php`.
* **10. Dependants:** Legacy scripts or files requiring auth helpers.
* **11. Security considerations:** Re-exports hardened auth guards.
* **12. Current implementation status:** `VERIFIED WORKING`.
* **13. Known issues:** None.
* **14. Manual testing instructions:** Require `includes/auth.php` -> verify `is_logged_in()` is callable.
* **15. Related development phase:** Phase 1 (Authentication).

---

## 6. Configuration & Database Engine

### File: `config/db.php`
* **1. File purpose:** The core configuration file of the entire platform. Sets session security, establishes the PDO connection singleton, provides automated database initialization fallbacks, defines auth helpers, and provides mock sample data.
* **2. Main responsibilities:**
  * Configure session cookie parameters (`httponly`, `samesite=Lax`, `use_only_cookies=1`, `use_strict_mode=1`) and start session.
  * Instantiate PDO connection to MySQL (`127.0.0.1:3306`, database `alumni_network`).
  * If database is missing, attempt auto-creation and schema provisioning via `database/schema.sql`.
  * Define auth guards: `is_logged_in()`, `current_user_id()`, `is_admin()`, `require_login()`, `require_admin()`.
  * Define query helpers: `get_unread_messages_count()`, `get_pending_reports_count()`, `get_base_url()`.
  * Provide sample fallback data arrays when MySQL is offline.
* **3. Execution flow:** Executed upon inclusion in every script; initializes session and PDO instance.
* **4. Important functions and logic:**
  * Automated recovery: auto-creates database and executes `database/schema.sql` if database does not exist.
  * Base URL detection: dynamically computes relative web root across subfolders.
* **5. Inputs and outputs:** Session state, PDO instance (`$pdo`), boolean `$db_connected`.
* **6. Database interactions:** Establishes PDO connection and performs schema auto-creation if needed.
* **7. Authentication requirements:** Universal bootstrap.
* **8. Authorization requirements:** Unrestricted.
* **9. Dependencies:** `database/schema.sql`.
* **10. Dependants:** Every PHP script in the repository.
* **11. Security considerations:** Session cookie hardening; PDO exception mode; emulated prepares disabled; CSRF protection features completely removed per project requirements.
* **12. Current implementation status:** `VERIFIED WORKING`.
* **13. Known issues:** Default configuration uses empty root password suited for local XAMPP; production deployments must set secure database credentials.
* **14. Manual testing instructions:** Check browser cookies -> verify `PHPSESSID` has `HttpOnly` and `SameSite=Lax`.
* **15. Related development phase:** Phase 1 (Foundation & Setup).

---

### File: `config/setup.php`
* **1. File purpose:** Database installer and re-seeding tool.
* **2. Main responsibilities:**
  * Connect to MySQL server, read `database/schema.sql`, and execute multi-query statements.
  * Protect against unauthorized database resets if users already exist by enforcing `require_admin()`.
  * Output confirmation log with default demo credentials.
* **3. Execution flow:**
  1. Requires `config/db.php`.
  2. Security check: if database has users and request is from browser, requires admin login.
  3. On POST (or CLI): connects to MySQL, executes `database/schema.sql`, outputs success log.
* **4. Important functions and logic:** Multi-query execution of schema SQL.
* **5. Inputs and outputs:** POST submit button; outputs installation status log.
* **6. Database interactions:** Creates database, drops/creates tables, inserts seed records.
* **7. Authentication requirements:** Public on first run; Administrator once populated.
* **8. Authorization requirements:** Administrator guard after initial install.
* **9. Dependencies:** `config/db.php`, `database/schema.sql`.
* **10. Dependants:** Linked from `admin/settings.php`.
* **11. Security considerations:** Protected against unauthorized resets once the database is populated.
* **12. Current implementation status:** `VERIFIED WORKING`.
* **13. Known issues:** None.
* **14. Manual testing instructions:** Navigate to `http://localhost/alumni-network/config/setup.php` as admin -> click Reset -> verify success log.
* **15. Related development phase:** Phase 1 (Database Setup).

---

### File: `database/schema.sql` (and root `schema.sql`)
* **1. File purpose:** Canonical relational SQL schema definition and seed data script.
* **2. Main responsibilities:**
  * Define `alumni_network` database with `utf8mb4` encoding.
  * Define 9 normalized relational tables: `users`, `alumni_profiles`, `events`, `event_registrations`, `jobs`, `job_applications`, `conversations`, `messages`, `reports`.
  * Define primary keys, unique constraints, foreign keys with `ON DELETE CASCADE`.
  * Insert seed data for root administrator, 6 sample alumni accounts, campus events, and jobs.
* **3. Execution flow:** Parsed and executed by MySQL / phpMyAdmin or `config/setup.php`.
* **4. Important functions and logic:** Normalized schema design with referential integrity.
* **5. Inputs and outputs:** SQL statements executed on MySQL server.
* **6. Database interactions:** DDL (`CREATE DATABASE`, `CREATE TABLE`) and DML (`INSERT INTO`).
* **7. Authentication requirements:** Database administrator.
* **8. Authorization requirements:** Database level.
* **9. Dependencies:** None.
* **10. Dependants:** `config/setup.php`, `config/db.php`.
* **11. Security considerations:** Seed passwords use pre-computed BCRYPT hashes.
* **12. Current implementation status:** `VERIFIED WORKING`.
* **13. Known issues:** Root `schema.sql` is a convenience copy of `database/schema.sql`.
* **14. Manual testing instructions:** Import file into phpMyAdmin -> verify all 9 tables are created with proper constraints.
* **15. Related development phase:** Phase 1 (Database Architecture).

---

## 7. Frontend Assets

### File: `assets/css/style.css`
* **1. File purpose:** Master stylesheet for the entire platform. Defines the design system and styling components.
* **2. Main responsibilities:**
  * Define CSS custom properties: KDU Cinematic Maroon & Gold palette (`--primary-color: #24070A`, `--primary-dark: #120305`, `--accent-color: #D4AF37`, etc.).
  * Provide responsive grid systems (`.grid`, `.grid-2`, `.grid-3`, `.grid-4`).
  * Style buttons (`.btn-gold`, `.btn-primary`, `.btn-danger`), cards (`.card`), badges, alert banners, tables, and forms.
  * Provide styling for admin layout (`.admin-layout`, `.admin-sidebar`, `.admin-main`) and messaging interface.
* **3. Execution flow:** Loaded by client browser via `<link>` tag in header.
* **4. Important functions and logic:** CSS3 variables, flexbox and grid layouts, media queries for mobile/tablet responsive breakpoints (`max-width: 992px`, `max-width: 768px`).
* **5. Inputs and outputs:** Parsed by browser CSS engine to render visual styling.
* **6. Database interactions:** None.
* **7. Authentication requirements:** Public static asset.
* **8. Dependencies:** None.
* **9. Dependants:** Included by all views.
* **10. Security considerations:** Static asset; no dynamic execution.
* **11. Current implementation status:** `VERIFIED WORKING`.
* **12. Known issues:** None.
* **13. Manual testing instructions:** Inspect page elements -> verify maroon and gold palette variables apply; test on mobile width -> verify responsive layout reflows cleanly.
* **14. Related development phase:** Phase 1 (Frontend Foundation).

---

### Files: `assets/css/event.css` & `assets/css/jobs.css`
* **1. File purpose:** Specialized stylesheets for event calendar components and job board layouts.
* **2. Main responsibilities:** Provide styling rules for date badges, capacity bars, company cards, and prototype views.
* **3. Execution flow:** Loaded when specialized components are rendered.
* **4. Current implementation status:** `VERIFIED WORKING`.
* **5. Related development phase:** Phase 3 (Events & Jobs).

---

### File: `assets/js/main.js`
* **1. File purpose:** Client-side progressive enhancement script.
* **2. Main responsibilities:**
  * Mobile Navigation: Toggles `.active` class on `.nav-menu` when hamburger button is clicked.
  * Instant Search Filter: Performs client-side text filtering across cards (`.alumni-card`, `.event-card`, `.job-card`).
  * Alert Auto-Dismissal: Fades out `.alert` banners after 5 seconds.
  * Message Autoscroll: Scrolls `.messages-thread` to bottom on chat open.
* **3. Execution flow:** Loaded at bottom of page via `includes/footer.php`; attaches event listeners to DOM elements.
* **4. Important functions and logic:** Vanilla DOM manipulation without external libraries.
* **5. Inputs and outputs:** DOM events; updates class names and element styles.
* **6. Database interactions:** None.
* **7. Authentication requirements:** Public static asset.
* **8. Dependencies:** None.
* **9. Dependants:** Loaded in `includes/footer.php`.
* **10. Security considerations:** No `eval()` or unescaped innerHTML manipulation.
* **11. Current implementation status:** `VERIFIED WORKING`.
* **12. Known issues:** None.
* **13. Manual testing instructions:** Click mobile menu button on small screen -> verify menu opens; type in directory live search -> verify instant card filtering.
* **14. Related development phase:** Phase 1 & Phase 4 (Frontend Integration).

---

### Files: `assets/images/*` (`campus-hero.svg`, `default-avatar.svg`, `event-placeholder.svg`)
* **1. File purpose:** Scalable vector graphic illustrations for hero sections, user avatars, and event cards.
* **2. Main responsibilities:** Provide lightweight, high-resolution visual branding.
* **3. Current implementation status:** `VERIFIED WORKING`.
* **4. Related development phase:** Phase 1 (Assets).

---

## 8. Upload Security Controls

### Files: `uploads/.htaccess` & `uploads/profiles/.htaccess`
* **1. File purpose:** Web server security configuration preventing remote code execution in upload folders.
* **2. Main responsibilities:**
  * Block execution of scripts (`.php`, `.phtml`, `.cgi`, `.pl`, `.py`, `.sh`).
  * Disable PHP engine: `php_flag engine off`.
  * Disable directory indexing: `Options -Indexes -ExecCGI`.
* **3. Execution flow:** Enforced at the Apache server level on every request to the `uploads/` directory.
* **4. Important functions and logic:** Apache directives: `<FilesMatch>`, `Require all denied`, `RemoveHandler`.
* **5. Inputs and outputs:** Evaluates incoming file requests; denies executable scripts with HTTP 403 Forbidden.
* **6. Database interactions:** None.
* **7. Authentication requirements:** Server-level rule.
* **8. Dependencies:** Apache `mod_authz_core`, `mod_mime`.
* **9. Dependants:** Protects `uploads/` and `uploads/profiles/`.
* **10. Security considerations:** Critical defense-in-depth protection against malicious file uploads.
* **11. Current implementation status:** `VERIFIED WORKING`.
* **12. Known issues:** Requires Apache `AllowOverride` to be enabled in server configuration.
* **13. Manual testing instructions:** Attempt to access a test `.php` file in `uploads/` -> verify Apache returns `403 Forbidden`.
* **14. Related development phase:** Phase 2 (Profile Photo Security).

---

## 9. Legacy Prototype Files

The following files represent early development prototypes created during initial phases. They have been superseded by the integrated PHP application controllers:

| File Path | Description | Integration Status |
| :--- | :--- | :--- |
| `event.html`, `event.js` | Early static prototype for browsing events via local fetch calls. | Superseded by `events.php` |
| `eventadmin.html`, `eventadmin.js` | Early prototype for event moderation. | Superseded by `admin/events.php` |
| `eventcreate.html`, `eventcreate.js` | Early prototype form for submitting events. | Superseded by modal forms in `events.php` |
| `jobs.html`, `jobs.js` | Early static prototype for career listings. | Superseded by `jobs.php` |
| `jobadmin.html`, `jobadmin.js` | Early prototype for job vacancy moderation. | Superseded by `admin/jobs.php` |
| `jobcreate.html` | Early prototype form for creating job postings. | Superseded by modal forms in `jobs.php` |

---

## 10. Repository Configuration & Meta Files

### File: `.gitignore`
* **1. File purpose:** Specifies intentionally untracked files that Git should ignore.
* **2. Main responsibilities:** Excludes temporary files, caches, logs (`*.log`), OS artifacts (`.DS_Store`, `Thumbs.db`), and IDE configuration folders (`.vscode/`, `.idea/`).
* **3. Current implementation status:** `VERIFIED WORKING`.

### File: `README.md`
* **1. File purpose:** The primary entry documentation file at the repository root.
* **2. Main responsibilities:** Provides project overview, architecture summary, installation guide, and links to the comprehensive documentation suite.
* **3. Current implementation status:** Updated with full technical documentation package links.
