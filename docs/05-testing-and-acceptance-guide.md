# Testing & Acceptance Guide

**Project:** General Sir John Kotelawala Defence University (KDU) Alumni Network  
**Target Environment:** PHP 8+, MySQL / MariaDB, Apache (XAMPP), Vanilla JS, Semantic HTML5, CSS3  
**Document Identifier:** `docs/05-testing-and-acceptance-guide.md`  
**Revision:** 1.0.0 (Manual Quality Verification Guide)  

---

## 1. Environment Setup & Prerequisites

Before initiating manual testing, verify that the local environment meets the operational baseline:
1. **Launch XAMPP Control Panel:** Start both **Apache** (Port `80`, `443`) and **MySQL** (Port `3306`).
2. **Verify Project Placement:** Ensure repository is located in `c:\xampp\htdocs\alumni-network`.
3. **Database Initialization:**
   * Option A: Navigate in browser to `http://localhost/alumni-network/config/setup.php` and click "Initialize / Reset Database Now".
   * Option B: Open phpMyAdmin (`http://localhost/phpmyadmin`), import `database/schema.sql`.
4. **Seed Accounts Provided:**
   * **System Administrator:** Username/Email `admin@alumni.edu`, Password `Admin@123`
   * **Verified Alumnus (John Doe):** Username/Email `john.doe@alumni.edu`, Password `Alumni@123`
   * **Additional Seed Alumni:** `sarah.jenkins@alumni.edu`, `michael.chen@alumni.edu` (Password: `Alumni@123`)

---

## 2. Test Execution Suite

---

### Module 1: System Bootstrap & Database Initialization

#### Test ID: `TC-SYS-01` — Database Auto-Creation & Schema Provisioning
* **Preconditions:** MySQL service running in XAMPP.
* **Test Steps:**
  1. Open browser and navigate to `http://localhost/alumni-network/config/setup.php`.
  2. If already logged in as administrator, click "Initialize / Reset Database Now".
  3. Inspect the on-screen execution log.
* **Expected Result:** Confirmation message displays verifying that database `alumni_network` and all 9 tables were created, and seed records were inserted.
* **Actual Result:** Verified; all tables (`users`, `alumni_profiles`, `events`, `event_registrations`, `jobs`, `job_applications`, `conversations`, `messages`, `reports`) created successfully.
* **Status:** **PASS**

---

### Module 2: Authentication & Session Management

#### Test ID: `TC-AUTH-01` — Member Self-Service Registration
* **Preconditions:** Unauthenticated visitor on registration page.
* **Test Steps:**
  1. Navigate to `http://localhost/alumni-network/auth/register.php`.
  2. Fill in First Name (`Kavinda`), Last Name (`Silva`), Username (`kavindas`), Email (`kavinda.s@alumni.edu`), Degree (`B.Sc. in Software Engineering`), Department (`Computer Engineering`), Cohort (`2023`), Password (`Kavinda@1234`), Confirm Password (`Kavinda@1234`).
  3. Submit the form.
* **Expected Result:** Validation succeeds, records are inserted into `users` and `alumni_profiles`, user is automatically logged in, and redirected to `profile.php?welcome=1`.
* **Actual Result:** Successfully registered and redirected to `profile.php`; password stored as BCRYPT hash in database.
* **Status:** **PASS**

#### Test ID: `TC-AUTH-02` — Duplicate Registration Rejection
* **Preconditions:** Existing registered email (`john.doe@alumni.edu`).
* **Test Steps:**
  1. Navigate to `http://localhost/alumni-network/auth/register.php`.
  2. Submit the form using Email `john.doe@alumni.edu`.
* **Expected Result:** Form submission halted with error: "An account with this email address or username already exists."
* **Actual Result:** Duplicate caught; error alert displayed; no database insertion.
* **Status:** **PASS**

#### Test ID: `TC-AUTH-03` — Credential Authentication & Role Routing
* **Preconditions:** Registered admin and alumni accounts.
* **Test Steps:**
  1. Navigate to `http://localhost/alumni-network/auth/login.php`.
  2. Log in with `admin@alumni.edu` and `Admin@123`.
  3. Verify landing destination.
  4. Log out, then log in with `john.doe@alumni.edu` and `Alumni@123`.
  5. Verify landing destination.
* **Expected Result:** Admin credentials redirect to `admin/index.php`. Alumnus credentials redirect to `index.php`.
* **Actual Result:** Correct role routing verified for both accounts.
* **Status:** **PASS**

#### Test ID: `TC-AUTH-04` — Invalid Password & Brute-Force Shielding
* **Preconditions:** User on login page.
* **Test Steps:**
  1. Enter `john.doe@alumni.edu` with incorrect password `WrongPassword999`.
  2. Submit form.
* **Expected Result:** Login denied with error "Invalid email/username or password." No sensitive stack traces leaked.
* **Actual Result:** Generic error displayed; login denied.
* **Status:** **PASS**

#### Test ID: `TC-AUTH-05` — Session Fixation & Cookie Hardening Verification
* **Preconditions:** Browser DevTools open (Application -> Cookies).
* **Test Steps:**
  1. Inspect `PHPSESSID` cookie value prior to login.
  2. Complete login.
  3. Inspect `PHPSESSID` cookie value after login.
  4. Check cookie flags: `HttpOnly`, `SameSite`.
* **Expected Result:** `PHPSESSID` value changes upon authentication (`session_regenerate_id(true)`). Cookie flags show `HttpOnly = true` and `SameSite = Lax`.
* **Actual Result:** Session ID regenerated; `HttpOnly` and `SameSite=Lax` flags verified.
* **Status:** **PASS**

#### Test ID: `TC-AUTH-06` — Secure Logout
* **Preconditions:** User logged in.
* **Test Steps:**
  1. Click `Logout` button in top navigation bar.
  2. Attempt to navigate back via browser back button to `profile.php`.
* **Expected Result:** Session destroyed; browser lands on `index.php`; navigation reverts to guest state; accessing protected pages redirects to login.
* **Actual Result:** Session terminated; cookie invalidated; protected routes redirect to `login.php`.
* **Status:** **PASS**

---

### Module 3: Access Control & Route Guarding

#### Test ID: `TC-SEC-01` — Unauthenticated Access to Member Portals
* **Preconditions:** Guest visitor (logged out).
* **Test Steps:**
  1. Attempt to directly access `http://localhost/alumni-network/profile.php`.
  2. Attempt to directly access `http://localhost/alumni-network/messages.php`.
* **Expected Result:** Both requests intercepted by `require_login()` and redirected to `auth/login.php`.
* **Actual Result:** Immediate 302 redirect to login page for both routes.
* **Status:** **PASS**

#### Test ID: `TC-SEC-02` — Non-Admin Access to Administrative Suite
* **Preconditions:** Logged in as regular alumnus (`john.doe@alumni.edu`).
* **Test Steps:**
  1. Attempt to directly open `http://localhost/alumni-network/admin/index.php`.
  2. Attempt to directly open `http://localhost/alumni-network/admin/users.php`.
* **Expected Result:** Both requests intercepted by `require_admin()` and redirected to `auth/login.php?admin_required=1`.
* **Actual Result:** Alumnus blocked from admin suite; redirected with admin required flag.
* **Status:** **PASS**

---

### Module 4: Alumni Profile & Avatar Uploads

#### Test ID: `TC-PROF-01` — Profile Details Modification
* **Preconditions:** Logged in as alumnus John Doe.
* **Test Steps:**
  1. Open `http://localhost/alumni-network/profile.php`.
  2. Switch to `Edit Information` tab.
  3. Update Current Job Title to "Lead Cloud Systems Architect" and Employer to "Google Cloud".
  4. Save changes.
* **Expected Result:** Success banner displays; database records in `alumni_profiles` updated; profile header reflects new job title.
* **Actual Result:** Data persisted across `users` and `alumni_profiles` tables via transaction.
* **Status:** **PASS**

#### Test ID: `TC-PROF-02` — Avatar Image Upload & File System Verification
* **Preconditions:** Logged in as alumnus on `profile.php`.
* **Test Steps:**
  1. Switch to `Profile Photo` tab.
  2. Upload a valid PNG image (<2MB).
  3. Submit the upload form.
  4. Inspect `uploads/profiles/` directory on server disk.
* **Expected Result:** Success banner displays; avatar updates on page; unique file `avatar_[id]_[time]_[hash].png` created in `uploads/profiles/`.
* **Actual Result:** Image uploaded, verified via `getimagesize()`, renamed securely, and rendered on profile.
* **Status:** **PASS**

#### Test ID: `TC-PROF-03` — Malicious File Upload Rejection
* **Preconditions:** Logged in on `profile.php`.
* **Test Steps:**
  1. Attempt to upload a text or PHP script file disguised as an image (`shell.php`).
* **Expected Result:** Upload rejected with error "Invalid image file format." No file saved to disk.
* **Actual Result:** `getimagesize()` returned false; upload halted; file rejected.
* **Status:** **PASS**

#### Test ID: `TC-PROF-04` — Upload Directory Script Execution Denial
* **Preconditions:** Server access to `uploads/`.
* **Test Steps:**
  1. Place a test file `test_exec.php` containing `<?php echo "executed"; ?>` in `uploads/`.
  2. Attempt to open `http://localhost/alumni-network/uploads/test_exec.php` in browser.
* **Expected Result:** Apache returns `403 Forbidden` due to `.htaccess` execution denial rules. Script does not execute.
* **Actual Result:** HTTP 403 Forbidden returned; script execution blocked. (Test file removed after verification).
* **Status:** **PASS**

---

### Module 5: Alumni Directory & Search

#### Test ID: `TC-DIR-01` — Multi-Attribute Search & Cohort Filtering
* **Preconditions:** Populated alumni database.
* **Test Steps:**
  1. Navigate to `http://localhost/alumni-network/directory.php`.
  2. In search box, type "Google" and submit -> verify only Google employees appear.
  3. Clear search; select Graduation Year "2022" -> verify only 2022 alumni display.
  4. Filter by Degree "Computer Engineering" -> verify matching degree cards display.
* **Expected Result:** Dynamic SQL queries filter results accurately without returning unapproved or private profiles.
* **Actual Result:** Search and dropdown filters function accurately.
* **Status:** **PASS**

#### Test ID: `TC-DIR-02` — Server-Side Pagination
* **Preconditions:** More than 6 active alumni profiles in database.
* **Test Steps:**
  1. Navigate to `directory.php`.
  2. Observe that exactly 6 alumni cards display on page 1.
  3. Click `Page 2` pagination button.
  4. Observe URL (`directory.php?page=2`) and card results.
* **Expected Result:** Page 2 displays the next batch of profiles; active search and filter parameters are preserved in the query string.
* **Actual Result:** Pagination operates seamlessly; query parameters preserved.
* **Status:** **PASS**

#### Test ID: `TC-DIR-03` — Client-Side Instant Filter
* **Preconditions:** On `directory.php` with multiple cards rendered.
* **Test Steps:**
  1. Type a name in the instant filter input.
* **Expected Result:** Cards matching the query remain visible; non-matching cards are hidden instantly without full page reload.
* **Actual Result:** DOM cards dynamically filtered via JavaScript in `main.js`.
* **Status:** **PASS**

---

### Module 6: Events, Capacity & RSVP

#### Test ID: `TC-EVT-01` — Interactive Event RSVP & Cancellation
* **Preconditions:** Logged in as alumnus; approved event exists.
* **Test Steps:**
  1. Navigate to `http://localhost/alumni-network/events.php`.
  2. Locate an approved event with available capacity.
  3. Click `RSVP Now`.
  4. Verify confirmation banner and attendee count increment.
  5. Click `Cancel RSVP`.
  6. Verify cancellation banner and attendee count decrement.
* **Expected Result:** RSVP creates record in `event_registrations` with `status = 'attending'`. Cancellation updates status to `cancelled`. Attendee counts update accurately.
* **Actual Result:** Dynamic state toggle and count tracking verified.
* **Status:** **PASS**

#### Test ID: `TC-EVT-02` — Event Capacity Enforcement
* **Preconditions:** An event with capacity set to 1 and 1 existing attendee.
* **Test Steps:**
  1. Log in with a different alumnus account.
  2. Attempt to RSVP for the full event.
* **Expected Result:** System blocks RSVP and displays alert: "This event has reached its maximum capacity."
* **Actual Result:** Capacity check triggered; registration blocked.
* **Status:** **PASS**

#### Test ID: `TC-EVT-03` — Alumni Event Proposal & Administrative Moderation
* **Preconditions:** Alumnus logged in; admin account available.
* **Test Steps:**
  1. As alumnus, open `events.php` and submit an event via "Propose an Alumni Event" modal.
  2. Verify confirmation message that event is pending review.
  3. Verify event is NOT visible on the public events board.
  4. Log in as admin and navigate to `admin/events.php`.
  5. Locate the proposed event with status `Pending` and click `Approve`.
  6. Refresh `events.php` as a public visitor.
* **Expected Result:** Event enters database with `status = 'pending'`, is approved by admin, and subsequently appears on the public events board.
* **Actual Result:** End-to-end moderation lifecycle verified.
* **Status:** **PASS**

---

### Module 7: Career Board & Job Applications

#### Test ID: `TC-JOB-01` — Internal Job Application & Duplicate Guard
* **Preconditions:** Logged in as alumnus; approved job vacancy exists.
* **Test Steps:**
  1. Navigate to `http://localhost/alumni-network/jobs.php`.
  2. Click `Apply for Position` on an active job card.
  3. Enter cover message: "Interested in the senior engineering vacancy."
  4. Submit application -> verify success message and button updates to "Applied".
  5. Attempt to re-submit application for the same job.
* **Expected Result:** First application creates record in `job_applications`. Second attempt is blocked with warning "You have already applied for this position."
* **Actual Result:** Duplicate application guard verified; application persisted.
* **Status:** **PASS**

#### Test ID: `TC-JOB-02` — Vacancy Submission & Administrative Moderation
* **Preconditions:** Alumnus logged in; admin account available.
* **Test Steps:**
  1. Submit a vacancy via "Post New Opportunity" on `jobs.php`.
  2. Verify confirmation that vacancy is queued for admin approval.
  3. Log in as admin, navigate to `admin/jobs.php`, and click `Approve`.
  4. Return to `jobs.php` as guest visitor.
* **Expected Result:** Vacancy starts as `pending`, is approved by admin, and renders on public career board.
* **Actual Result:** Job moderation workflow verified end-to-end.
* **Status:** **PASS**

---

### Module 8: Private Direct Messaging

#### Test ID: `TC-MSG-01` — 1-to-1 Conversation Initialization & Message Delivery
* **Preconditions:** Two distinct alumni accounts (Alumnus A and Alumnus B).
* **Test Steps:**
  1. Log in as Alumnus A; navigate to `messages.php?user_id=[Alumnus B ID]`.
  2. Type "Hi, are you attending the KDU Tech Summit?" and click Send.
  3. Log out and log in as Alumnus B.
  4. Inspect navigation bar.
  5. Open `messages.php`.
* **Expected Result:**
  * Conversation created with ordered IDs (`user_one_id = min`, `user_two_id = max`).
  * Message inserted with `is_read = 0`.
  * Alumnus B sees unread notification badge `1` on `Messages` navigation link.
  * Opening the conversation displays the message bubble and clears unread status.
* **Actual Result:** Real-time messaging, unread notification counter, and transcript viewing verified.
* **Status:** **PASS**

#### Test ID: `TC-MSG-02` — Conversation Access Authorization Check
* **Preconditions:** Conversation exists between User 2 and User 3 (Conversation ID 1).
* **Test Steps:**
  1. Log in as User 4 (unrelated user).
  2. Attempt to open `http://localhost/alumni-network/messages.php?conversation_id=1`.
* **Expected Result:** Access denied with error "Unauthorized conversation access." Transcript is not exposed.
* **Actual Result:** Authorization check intercepted unauthorized user; messages shielded.
* **Status:** **PASS**

---

### Module 9: Trust, Safety & Community Moderation

#### Test ID: `TC-REP-01` — Content Misconduct Reporting & Admin Resolution
* **Preconditions:** Alumnus logged in; admin account available.
* **Test Steps:**
  1. On any event or profile card, click `Report`.
  2. Select reason "Inappropriate Content" and enter description "Spam solicitation."
  3. Submit report.
  4. Verify redirect back with `?reported=1`.
  5. Log in as admin and navigate to `admin/reports.php`.
  6. Locate the report, change status to `Resolved`, and submit.
* **Expected Result:** Record inserted into `reports` with `status = 'pending'`; visible in admin console; admin can update status to `Resolved`.
* **Actual Result:** Reporting pipeline and resolution workflow verified.
* **Status:** **PASS**

---

### Module 10: Administration & User Governance

#### Test ID: `TC-ADM-01` — User Suspension & Authentication Lockout
* **Preconditions:** Admin logged in.
* **Test Steps:**
  1. Navigate to `admin/users.php`.
  2. Locate an active alumnus account and click `Suspend`.
  3. Verify status updates to `suspended`.
  4. Log out and attempt to log in using the suspended account credentials.
* **Expected Result:** Login rejected with message: "This account has been suspended. Please contact the administrator."
* **Actual Result:** Suspended status enforced during authentication; access blocked.
* **Status:** **PASS**

#### Test ID: `TC-ADM-02` — Root Administrator Protection Guard
* **Preconditions:** Admin logged in on `admin/users.php`.
* **Test Steps:**
  1. Attempt to suspend, change role, or delete User ID 1 (Root Admin).
* **Expected Result:** Action blocked with alert: "Primary system administrator cannot be modified or deleted."
* **Actual Result:** Protection guard triggered; root admin preserved.
* **Status:** **PASS**

---

### Module 11: Cross-Cutting Security & Responsive Verification

#### Test ID: `TC-SEC-03` — SQL Injection Defense Verification
* **Preconditions:** Directory search input and login identity input.
* **Test Steps:**
  1. In directory search, input: `' OR 1=1 --`.
  2. In login identity, input: `admin' OR '1'='1`.
* **Expected Result:** Input treated strictly as literal strings; no syntax errors, data leakage, or authentication bypass.
* **Actual Result:** Prepared statements treated inputs safely; zero SQL injection vulnerability.
* **Status:** **PASS**

#### Test ID: `TC-SEC-04` — Stored Cross-Site Scripting (XSS) Neutralization
* **Preconditions:** Profile bio input and message input.
* **Test Steps:**
  1. In profile bio, save: `<script>alert('xss');</script>`.
  2. View profile page.
* **Expected Result:** Script rendered as literal escaped text; no JavaScript popup executes.
* **Actual Result:** `htmlspecialchars()` escaped `<` and `>` into `&lt;` and `&gt;`; script neutralized.
* **Status:** **PASS**

#### Test ID: `TC-RES-01` — Viewport Responsiveness & Mobile Menu
* **Preconditions:** Browser DevTools responsive emulation active.
* **Test Steps:**
  1. Resize viewport to 375px (mobile width).
  2. Verify layout reflows into single column.
  3. Click mobile hamburger navigation button.
  4. Verify mobile menu expands smoothly.
* **Expected Result:** Navigation menu toggles `.active` class; cards and tables stack cleanly without horizontal scroll.
* **Actual Result:** Responsive breakpoints and hamburger toggle operate smoothly.
* **Status:** **PASS**

---

## 3. Test Summary Matrix

| Module | Total Tests | Passed | Failed | Not Tested | Compliance Rate |
| :--- | :---: | :---: | :---: | :---: | :---: |
| 1. System Bootstrap | 1 | 1 | 0 | 0 | 100% |
| 2. Authentication & Sessions | 6 | 6 | 0 | 0 | 100% |
| 3. Access Control & Route Guarding | 2 | 2 | 0 | 0 | 100% |
| 4. Profiles & Uploads | 4 | 4 | 0 | 0 | 100% |
| 5. Alumni Directory & Search | 3 | 3 | 0 | 0 | 100% |
| 6. Events & RSVP | 3 | 3 | 0 | 0 | 100% |
| 7. Careers & Applications | 2 | 2 | 0 | 0 | 100% |
| 8. Private Messaging | 2 | 2 | 0 | 0 | 100% |
| 9. Trust & Safety Reports | 1 | 1 | 0 | 0 | 100% |
| 10. Administration & Governance | 2 | 2 | 0 | 0 | 100% |
| 11. Security & Responsiveness | 3 | 3 | 0 | 0 | 100% |
| **Overall Platform Total** | **29** | **29** | **0** | **0** | **100%** |
