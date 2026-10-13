# 10 — Step-by-Step Hands-on Manual Testing Guide

**Project:** University Alumni Network  
**Target Audience:** Beginner QA Tester / Developer performing hands-on manual verification  
**Architecture:** PHP 8.2 PDO, MySQL, Vanilla JS, HTML5, CSS3  
**Status Key:** `NOT TESTED` | `PASS` | `FAIL` | `BLOCKED`  

---

# Section A: Prepare the Testing Environment

Follow these steps before running any test cases.

### A.1 Starting Local Server Services (XAMPP)
1. Open the **XAMPP Control Panel** (`C:\xampp\xampp-control.exe`).
2. Click **Start** for **Apache** (Web Server). Verify port `80` (and `443`) turns green.
3. Click **Start** for **MySQL** (Database). Verify port `3306` turns green.
4. *Alternative CLI method:* Open PowerShell and run:
   ```powershell
   Get-Process -Name httpd, mysqld -ErrorAction SilentlyContinue
   ```

### A.2 Database Connection & Configuration
1. The active database configuration file is [`config/db.php`](file:///c:/xampp/htdocs/alumni-network/config/db.php).
2. It connects to MySQL at host `127.0.0.1`, database `alumni_network`, user `root`, password `""` (empty string).
3. If the database has not been initialized yet, run the CLI installer:
   ```powershell
   C:\xampp\php\php.exe config/setup.php
   ```

### A.3 Creating a Safe Database Backup Before Testing
Before executing destructive tests (user deletion, moderation rejection), create a local backup dump:
```powershell
# In PowerShell:
mysqldump -u root alumni_network > database/backup_pre_test.sql
```
*To restore if needed:*
```powershell
mysql -u root alumni_network < database/backup_pre_test.sql
```

### A.4 Opening the Application & Configuring Browser DevTools
1. Launch Google Chrome or Microsoft Edge.
2. Navigate to: `http://localhost/alumni-network/index.php`
3. Press `F12` to open Developer Tools:
   - **Console Tab:** Watch for red error messages (`Uncaught TypeError`, `404 Not Found`).
   - **Network Tab:** Check **Disable cache** and **Preserve log**. Filter by **Fetch/XHR** to inspect API responses.
   - **Application Tab -> Storage:** Inspect **Cookies** (`PHPSESSID`) and **Local Storage** (`alumni_theme_preference`).

### A.5 Monitoring Server Error Logs in Real Time
Keep a PowerShell window open to observe backend warnings or fatal errors:
```powershell
Get-Content -Path "C:\xampp\apache\logs\error.log" -Wait -Tail 20
```

---

# Section B: Test the Website as an Anonymous Visitor

### HOT-VIS-001: Public Portal Browsing & Navigation Links

| Field | Details |
| :--- | :--- |
| **Test ID** | HOT-VIS-001 |
| **Feature** | Public homepage and navigation links |
| **Purpose** | Ensure an unauthenticated visitor can explore public content without errors |
| **Required Setup** | Open browser in Incognito mode; clear cookies |
| **Exact Steps** | 1. Open `http://localhost/alumni-network/index.php`.<br>2. Observe the hero section, statistics counter, and navigation bar.<br>3. Click the navigation links one by one: "Home", "Alumni", "Events", "Jobs", "About".<br>4. Verify the active indicator updates to match the current page. |
| **Expected Result** | All public pages load with HTTP 200. No 404 broken link errors. Admin links (`Job Admin`, `Event Admin`) are completely hidden. |
| **How to Verify** | Check page titles, inspect top navigation HTML to confirm `#adminNavLink` has `display: none` or is absent. |
| **Actual Result** | |
| **Status** | NOT TESTED |
| **Notes** | |

---

### HOT-VIS-002: Public Jobs Board & Filtering Features

| Field | Details |
| :--- | :--- |
| **Test ID** | HOT-VIS-002 |
| **Feature** | Public jobs search, type filter, and card presentation |
| **Purpose** | Verify that anonymous visitors can browse approved vacancies and use live filters |
| **Required Setup** | Anonymous browser session |
| **Exact Steps** | 1. Navigate to `http://localhost/alumni-network/jobs.html`.<br>2. Type `Engineer` into the search box and observe cards.<br>3. Clear the search box.<br>4. Select "Internship" from the Job Type dropdown.<br>5. Select "All Types". |
| **Expected Result** | Job cards filter instantaneously via Vanilla JS. Only `approved` jobs are displayed. Orange "Pending Approval" cards are never visible to the visitor. |
| **How to Verify** | Confirm card titles match the typed query; check console for zero JavaScript errors. |
| **Actual Result** | |
| **Status** | NOT TESTED |
| **Notes** | |

---

### HOT-VIS-003: Public Events Portal & Date Filters

| Field | Details |
| :--- | :--- |
| **Test ID** | HOT-VIS-003 |
| **Feature** | Public events listing and upcoming/past category tabs |
| **Purpose** | Verify public event cards render correctly with attendee tally and location |
| **Required Setup** | Anonymous browser session |
| **Exact Steps** | 1. Navigate to `http://localhost/alumni-network/event.html`.<br>2. Inspect upcoming event cards for date, location, description, and attendee count.<br>3. Click "Upcoming Events" tab, then "Past Events" tab, then "All Events". |
| **Expected Result** | Cards render cleanly with liquid glass effects. Past events indicate concluded status. Upcoming events show registration details. |
| **How to Verify** | Check attendee counter displays e.g. "X people are going". Verify zero 404 errors for images. |
| **Actual Result** | |
| **Status** | NOT TESTED |
| **Notes** | |

---

### HOT-VIS-004: Cross-Device Responsive Layout Audit

| Field | Details |
| :--- | :--- |
| **Test ID** | HOT-VIS-004 |
| **Feature** | Mobile, tablet, and desktop responsive layout |
| **Purpose** | Confirm layout does not break, clip, or horizontally overflow on small screens |
| **Required Setup** | Browser DevTools with Device Toolbar enabled (`Ctrl + Shift + M`) |
| **Exact Steps** | 1. Set viewport to 375x667 (iPhone SE). Navigate `index.php`, `jobs.html`, `event.html`.<br>2. Try scrolling horizontally.<br>3. Tap the mobile hamburger menu icon in the header.<br>4. Set viewport to 768x1024 (iPad). Check 2-column card layouts.<br>5. Return to desktop 1440x900. |
| **Expected Result** | No horizontal scrollbar appears at any screen width. Mobile menu opens smoothly with blurred backdrop. Cards stack into a single column on mobile. |
| **How to Verify** | Run `document.body.scrollWidth > window.innerWidth` in Console (must return `false`). |
| **Actual Result** | |
| **Status** | NOT TESTED |
| **Notes** | |

---

### HOT-VIS-005: Theme Switcher (Dark, Light, System) as Visitor

| Field | Details |
| :--- | :--- |
| **Test ID** | HOT-VIS-005 |
| **Feature** | Theme switcher visual presentation and `localStorage` persistence |
| **Purpose** | Ensure theme toggling works for visitors without requiring an account |
| **Required Setup** | Anonymous browser session |
| **Exact Steps** | 1. Click "Light" in the theme switcher pill in the header.<br>2. Inspect background and cards.<br>3. Click "Dark" in the switcher pill.<br>4. Click "Auto" / "System". Change your Windows system appearance from Dark to Light.<br>5. Reload the page (`F5`). |
| **Expected Result** | Light theme applies crisp lavender background (`#F7F5FC`) and legible dark text. Dark theme restores purple identity. System mode syncs with OS instantly. Selection persists after refresh without white flash. |
| **How to Verify** | Inspect `document.documentElement.getAttribute('data-theme')` in Console. Check `localStorage.getItem('alumni_theme_preference')`. |
| **Actual Result** | |
| **Status** | NOT TESTED |
| **Notes** | |

---

# Section C: Test Registration, Login, Sessions, and Logout

### HOT-AUTH-001: Register a New Normal Alumni Account

| Field | Details |
| :--- | :--- |
| **Test ID** | HOT-AUTH-001 |
| **Feature** | New member registration form |
| **Purpose** | Verify standard user onboarding and Bcrypt password hashing |
| **Required Setup** | Database running |
| **Exact Steps** | 1. Navigate to `http://localhost/alumni-network/auth/register.php`.<br>2. Fill in: First Name: `Test`, Last Name: `Member`, Username: `testmember99`, Email: `test.member99@alumni.edu`, Degree: `BSc Computing`, Department: `Computing`, Graduation Year: `2024`, Password: `Member@Pass2026!`, Confirm Password: `Member@Pass2026!`.<br>3. Click "Create Account". |
| **Expected Result** | Account is created. User is automatically logged in and redirected to `index.php?registered=1` with authenticated header. |
| **How to Verify** | In MySQL: `SELECT id, username, role, LEFT(password, 15) FROM users WHERE username = 'testmember99';` -> role must be `alumni`, password must start with `$2y$10$`. |
| **Actual Result** | |
| **Status** | NOT TESTED |
| **Notes** | |

---

### HOT-AUTH-002: Registration Form Validation & Error Handling

| Field | Details |
| :--- | :--- |
| **Test ID** | HOT-AUTH-002 |
| **Feature** | Form field validation and mismatch detection |
| **Purpose** | Ensure invalid registration attempts are blocked with clear feedback |
| **Required Setup** | On `auth/register.php` |
| **Exact Steps** | 1. Leave "Email" empty and submit form.<br>2. Enter an invalid email `bademailaddress` and submit.<br>3. Enter mismatched passwords (`Secret1` vs `Secret2`) and submit.<br>4. Enter a password shorter than 8 characters (`short`) and submit. |
| **Expected Result** | Form submission is halted in each case. Red error banner displays specific error message. No database record is created. |
| **How to Verify** | Check alert text on page; query MySQL to confirm no empty or corrupt rows were inserted. |
| **Actual Result** | |
| **Status** | NOT TESTED |
| **Notes** | |

---

### HOT-AUTH-003: Duplicate Username or Email Rejection

| Field | Details |
| :--- | :--- |
| **Test ID** | HOT-AUTH-003 |
| **Feature** | Duplicate account collision defense |
| **Purpose** | Verify system rejects existing email addresses and usernames |
| **Required Setup** | User `testmember99` exists from HOT-AUTH-001 |
| **Exact Steps** | 1. Navigate to `auth/register.php`.<br>2. Enter First Name `Another`, Last Name `User`, Username `testmember99`, Email `test.member99@alumni.edu`, Password `ValidPass@123!`.<br>3. Submit the form. |
| **Expected Result** | Registration fails. Banner displays: `"An account with that email address or username already exists. Please sign in or use different details."` |
| **How to Verify** | Confirm user is kept on registration page with input fields preserved. |
| **Actual Result** | |
| **Status** | NOT TESTED |
| **Notes** | |

---

### HOT-AUTH-004: Login with Valid Normal User Credentials

| Field | Details |
| :--- | :--- |
| **Test ID** | HOT-AUTH-004 |
| **Feature** | Member login authentication |
| **Purpose** | Verify standard alumni login and session cookie initialization |
| **Required Setup** | Seed user `johndoe` exists with password `Alumni@123` |
| **Exact Steps** | 1. Navigate to `http://localhost/alumni-network/auth/login.php`.<br>2. Enter Identity: `johndoe` (or `john.doe@alumni.edu`).<br>3. Enter Password: `Alumni@123`.<br>4. Click "Sign In". |
| **Expected Result** | HTTP POST returns 302 redirecting to `../index.php`. Header shows user name "John Doe" and "Logout". Cookie `PHPSESSID` is created. Admin links remain hidden. |
| **How to Verify** | Check `job.php?action=session` in browser -> returns `{"logged_in":true,"role":"alumni","is_admin":false}`. |
| **Actual Result** | |
| **Status** | NOT TESTED |
| **Notes** | |

---

### HOT-AUTH-005: Login with Incorrect Password or Non-Existent User

| Field | Details |
| :--- | :--- |
| **Test ID** | HOT-AUTH-005 |
| **Feature** | Invalid credential rejection and user enumeration protection |
| **Purpose** | Ensure wrong passwords or non-existent usernames do not gain access |
| **Required Setup** | On `auth/login.php` |
| **Exact Steps** | 1. Enter Username: `johndoe`, Password: `WrongPassword999!`. Click Sign In.<br>2. Enter Username: `nonexistentuser123`, Password: `AnyPassword!`. Click Sign In. |
| **Expected Result** | Both attempts are rejected with identical message: `"Invalid email/username or password. Please verify your credentials and try again."` No session is established. |
| **How to Verify** | Verify Network response is 200 OK without redirect. Check `$_SESSION['user_id']` is empty. |
| **Actual Result** | |
| **Status** | NOT TESTED |
| **Notes** | |

---

### HOT-AUTH-006: User Logout and Complete Session Destruction

| Field | Details |
| :--- | :--- |
| **Test ID** | HOT-AUTH-006 |
| **Feature** | Safe session termination |
| **Purpose** | Ensure logging out clears server session and revokes access |
| **Required Setup** | Logged in as `johndoe` |
| **Exact Steps** | 1. Click "Logout" in header (or visit `http://localhost/alumni-network/auth/logout.php`).<br>2. Click browser "Back" button.<br>3. Try refreshing page. |
| **Expected Result** | User is redirected to `index.php` (or login). Re-navigating back does not restore authenticated state. Navigation bar shows Login/Register buttons again. |
| **How to Verify** | Check `job.php?action=session` -> returns `{"logged_in":false,"role":"guest"}`. |
| **Actual Result** | |
| **Status** | NOT TESTED |
| **Notes** | |

---

# Section D: Test All Three Admin Accounts

Execute this section using the three accounts defined in [`09-admin-test-accounts.md`](file:///c:/xampp/htdocs/alumni-network/project-documentation/testing/09-admin-test-accounts.md).

> [!NOTE]
> If you have not provisioned these accounts yet, run the CLI provisioning commands from `09-admin-test-accounts.md` now before starting HOT-ADM-001.

### HOT-ADM-001: Admin Test Account 1 (`admin_qa1`) Login & Dashboard

| Field | Details |
| :--- | :--- |
| **Test ID** | HOT-ADM-001 |
| **Feature** | Admin Account 1 authentication and dashboard stats |
| **Purpose** | Verify `admin_qa1` logs in successfully and accesses the full administrative dashboard |
| **Required Setup** | Account `admin_qa1` provisioned with password `QaAdmin#2026!One` |
| **Exact Steps** | 1. Navigate to `http://localhost/alumni-network/auth/login.php`.<br>2. Enter Identity: `admin_qa1`, Password: `QaAdmin#2026!One`.<br>3. Click "Sign In".<br>4. Inspect `admin/index.php`. |
| **Expected Result** | Redirection to `http://localhost/alumni-network/admin/index.php`. Dashboard displays summary cards: Total Users, Active Users, Pending Events, Pending Jobs, Pending Reports. |
| **How to Verify** | Confirm sidebar exposes Dashboard, Users, Events, Jobs, Reports, Settings. Confirm `is_admin()` evaluates to true. |
| **Actual Result** | |
| **Status** | NOT TESTED |
| **Notes** | |

---

### HOT-ADM-002: Admin Test Account 1 Management Action & Harmless DB Change

| Field | Details |
| :--- | :--- |
| **Test ID** | HOT-ADM-002 |
| **Feature** | Administrative state-changing operation (Job moderation) |
| **Purpose** | Verify `admin_qa1` can perform moderation actions that persist in MySQL |
| **Required Setup** | Logged in as `admin_qa1`; a pending job exists (or sample job) |
| **Exact Steps** | 1. From admin sidebar, click "Jobs" (`admin/jobs.php`).<br>2. Locate a job with `pending` status (or create one as normal user first).<br>3. Click "Approve" button.<br>4. Observe the green confirmation alert on the page.<br>5. Open a private window and check `jobs.html` as visitor. |
| **Expected Result** | Page displays `"Job vacancy #X approved."` The job status changes to `approved` and is immediately visible on the public jobs board. |
| **How to Verify** | In MySQL: `SELECT id, status FROM jobs WHERE id = [target_id];` -> status is `approved`. |
| **Actual Result** | |
| **Status** | NOT TESTED |
| **Notes** | |

---

### HOT-ADM-003: Admin Test Account 1 Logout & Lockout

| Field | Details |
| :--- | :--- |
| **Test ID** | HOT-ADM-003 |
| **Feature** | Admin Account 1 logout and access revocation |
| **Purpose** | Verify logging out locks all admin URLs |
| **Required Setup** | Logged in as `admin_qa1` |
| **Exact Steps** | 1. Click "Logout" (or visit `auth/logout.php`).<br>2. In the URL bar, manually type `http://localhost/alumni-network/admin/jobs.php`. |
| **Expected Result** | Immediate redirect to `auth/login.php?admin_required=1`. Admin jobs page is NOT displayed. |
| **How to Verify** | Network tab shows HTTP 302 redirecting away from `admin/jobs.php`. |
| **Actual Result** | |
| **Status** | NOT TESTED |
| **Notes** | |

---

### HOT-ADM-004: Admin Test Account 2 (`admin_qa2`) Login & Event Moderation

| Field | Details |
| :--- | :--- |
| **Test ID** | HOT-ADM-004 |
| **Feature** | Admin Account 2 authentication and event approval |
| **Purpose** | Verify second independent admin account can log in and moderate events |
| **Required Setup** | Account `admin_qa2` provisioned with password `QaAdmin#2026!Two` |
| **Exact Steps** | 1. Log in at `auth/login.php` with `admin_qa2` / `QaAdmin#2026!Two`.<br>2. Confirm redirection to `admin/index.php`.<br>3. Click "Events" in sidebar (`admin/events.php`).<br>4. Locate a pending event and click "Approve".<br>5. Confirm green alert: `"Event #X approved successfully."`<br>6. Check public `event.html` in an Incognito window. |
| **Expected Result** | Event status becomes `approved` in database. Public events list now includes the event. |
| **How to Verify** | In MySQL: `SELECT id, status FROM events WHERE id = [event_id];` -> status is `approved`. |
| **Actual Result** | |
| **Status** | NOT TESTED |
| **Notes** | |

---

### HOT-ADM-005: Admin Test Account 2 Logout & Lockout

| Field | Details |
| :--- | :--- |
| **Test ID** | HOT-ADM-005 |
| **Feature** | Admin Account 2 session termination |
| **Purpose** | Ensure second admin account logs out completely |
| **Required Setup** | Logged in as `admin_qa2` |
| **Exact Steps** | 1. Click "Logout".<br>2. Manually enter `http://localhost/alumni-network/admin/events.php`. |
| **Expected Result** | Access denied; redirected to `auth/login.php?admin_required=1`. |
| **How to Verify** | Network tab shows HTTP 302 redirect. |
| **Actual Result** | |
| **Status** | NOT TESTED |
| **Notes** | |

---

### HOT-ADM-006: Admin Test Account 3 (`admin_qa3`) Login & User Management

| Field | Details |
| :--- | :--- |
| **Test ID** | HOT-ADM-006 |
| **Feature** | Admin Account 3 authentication and user directory access |
| **Purpose** | Verify third admin account can log in and access user controls |
| **Required Setup** | Account `admin_qa3` provisioned with password `QaAdmin#2026!Three` |
| **Exact Steps** | 1. Log in at `auth/login.php` with `admin_qa3` / `QaAdmin#2026!Three`.<br>2. From sidebar, click "Users" (`admin/users.php`).<br>3. In the user list, locate the test user created in HOT-AUTH-001 (`testmember99`).<br>4. Click the toggle to suspend the user.<br>5. Confirm message: `"User #X status updated to Suspended."`<br>6. Restore the user to active: click activate. |
| **Expected Result** | Admin user management table loads with search and filters. Status updates persist in database. |
| **How to Verify** | Query MySQL: `SELECT id, username, status FROM users WHERE username = 'testmember99';` |
| **Actual Result** | |
| **Status** | NOT TESTED |
| **Notes** | |

---

### HOT-ADM-007: Admin Test Account 3 Logout & Lockout

| Field | Details |
| :--- | :--- |
| **Test ID** | HOT-ADM-007 |
| **Feature** | Admin Account 3 session termination |
| **Purpose** | Ensure third admin account logs out completely |
| **Required Setup** | Logged in as `admin_qa3` |
| **Exact Steps** | 1. Click "Logout".<br>2. Manually enter `http://localhost/alumni-network/admin/users.php`. |
| **Expected Result** | Access denied; redirected to `auth/login.php?admin_required=1`. |
| **How to Verify** | Network tab shows HTTP 302 redirect. |
| **Actual Result** | |
| **Status** | NOT TESTED |
| **Notes** | |

---

# Section E: Test Authorization and Security

### HOT-SEC-001: Anonymous Visitor Access to Admin URLs

| Field | Details |
| :--- | :--- |
| **Test ID** | HOT-SEC-001 |
| **Feature** | Direct URL protection for anonymous visitors |
| **Purpose** | Ensure visitors cannot access admin pages by typing URLs directly |
| **Required Setup** | Anonymous browser session |
| **Exact Steps** | Manually navigate to each of these URLs:<br>1. `http://localhost/alumni-network/admin/index.php`<br>2. `http://localhost/alumni-network/admin/jobs.php`<br>3. `http://localhost/alumni-network/admin/events.php`<br>4. `http://localhost/alumni-network/admin/users.php`<br>5. `http://localhost/alumni-network/admin/reports.php` |
| **Expected Result** | Every URL immediately redirects to `auth/login.php?admin_required=1`. No admin tables, stats, or HTML are returned. |
| **How to Verify** | Check Network tab shows HTTP 302 redirect for each URL. |
| **Actual Result** | |
| **Status** | NOT TESTED |
| **Notes** | |

---

### HOT-SEC-002: Normal User Access to Admin URLs

| Field | Details |
| :--- | :--- |
| **Test ID** | HOT-SEC-002 |
| **Feature** | Privilege escalation guard on normal alumni accounts |
| **Purpose** | Verify standard users (`role = 'alumni'`) are strictly blocked from admin pages |
| **Required Setup** | Logged in as normal user `johndoe` (`Alumni@123`) |
| **Exact Steps** | In the browser URL bar, manually navigate to `http://localhost/alumni-network/admin/index.php` and `admin/jobs.php`. |
| **Expected Result** | System evaluates `is_admin()` -> returns `false`. User is redirected to `auth/login.php?admin_required=1`. User cannot view pending moderation records. |
| **How to Verify** | Verify Network response redirect; confirm no admin content rendered. |
| **Actual Result** | |
| **Status** | NOT TESTED |
| **Notes** | |

---

### HOT-SEC-003: Direct Unauthenticated API Calls to Admin Endpoints

| Field | Details |
| :--- | :--- |
| **Test ID** | HOT-SEC-003 |
| **Feature** | API-level role-based authorization |
| **Purpose** | Verify API rejects moderation actions from unauthenticated callers |
| **Required Setup** | PowerShell or curl |
| **Exact Steps** | In PowerShell, send direct POST requests to the API controllers:<br>```powershell
Invoke-RestMethod -Uri "http://localhost/alumni-network/job.php?action=approve" -Method Post -Body @{ id = 1 } -SkipHttpErrorCheck -StatusCodeVariable code1
Invoke-RestMethod -Uri "http://localhost/alumni-network/event.php?action=approve" -Method Post -Body @{ id = 1 } -SkipHttpErrorCheck -StatusCodeVariable code2
``` |
| **Expected Result** | Both requests return HTTP 403 Forbidden with JSON error: `{"success":false,"error":"Forbidden: Administrator privileges required."}` |
| **How to Verify** | Inspect `$code1` and `$code2` variables (must both equal `403`). In MySQL, job 1 and event 1 status remain unchanged. |
| **Actual Result** | |
| **Status** | NOT TESTED |
| **Notes** | |

---

### HOT-SEC-004: Registration Form Tampering (Privilege Escalation Attempt)

| Field | Details |
| :--- | :--- |
| **Test ID** | HOT-SEC-004 |
| **Feature** | Server-side role assignment integrity |
| **Purpose** | Verify that injecting `role=admin` during registration is safely ignored |
| **Required Setup** | Registration page |
| **Exact Steps** | 1. Open DevTools Elements on `auth/register.php`.<br>2. Add an input element inside the form: `<input type="hidden" name="role" value="admin">`.<br>3. Fill valid details with username `hackadmin` and submit.<br>4. Check the created user in MySQL. |
| **Expected Result** | The account is created with `role = 'alumni'`. The injected `role=admin` parameter is completely ignored because `register.php` hardcodes `'alumni'` in the SQL statement. |
| **How to Verify** | In MySQL: `SELECT username, role FROM users WHERE username = 'hackadmin';` -> role MUST be `alumni`. |
| **Actual Result** | |
| **Status** | NOT TESTED |
| **Notes** | |

---

### HOT-SEC-005: SQL Injection Resistance on Login & Search

| Field | Details |
| :--- | :--- |
| **Test ID** | HOT-SEC-005 |
| **Feature** | Parameterized query SQL injection defense |
| **Purpose** | Verify SQL injection payloads are treated as literal text |
| **Required Setup** | Login page `auth/login.php` |
| **Exact Steps** | 1. In "Email or Username", enter: `' OR '1'='1' -- `<br>2. In "Password", enter: `' OR '1'='1' -- `<br>3. Click Sign In.<br>4. On `jobs.html`, enter into search box: `'; DROP TABLE test; --` |
| **Expected Result** | Login fails with standard invalid credentials error. Search box filters for the literal string without errors. Database remains intact. |
| **How to Verify** | Confirm database tables exist; confirm zero SQL error output in Apache `error.log`. |
| **Actual Result** | |
| **Status** | NOT TESTED |
| **Notes** | |

---

# Section F: Test Jobs and Applications

### HOT-JOB-001: Anonymous Visitor Protected Action on "View & Apply"

| Field | Details |
| :--- | :--- |
| **Test ID** | HOT-JOB-001 |
| **Feature** | Unauthenticated interception on job details and applications |
| **Purpose** | Verify visitor cannot view application flow without logging in |
| **Required Setup** | Anonymous browser session |
| **Exact Steps** | 1. Navigate to `http://localhost/alumni-network/jobs.html`.<br>2. Locate Job ID 1.<br>3. Click the "View & Apply" button. |
| **Expected Result** | User is redirected to `auth/login.php?redirect=job-details.html%3Fid%3D1&msg=auth_required`. Login page displays amber alert: `"Please log in to continue."` |
| **How to Verify** | Inspect browser URL and banner message text. |
| **Actual Result** | |
| **Status** | NOT TESTED |
| **Notes** | |

---

### HOT-JOB-002: Authenticated View of Job Details Page

| Field | Details |
| :--- | :--- |
| **Test ID** | HOT-JOB-002 |
| **Feature** | Full job details rendering on dedicated page |
| **Purpose** | Verify all job metadata, requirements, and company details render correctly |
| **Required Setup** | Logged in as `johndoe` |
| **Exact Steps** | 1. Navigate to `job-details.html?id=1` (or click "View & Apply" while logged in).<br>2. Check job title, company name, location, job type tag, description, requirements, deadline, and contact email.<br>3. Check the "Return to Opportunities" button. |
| **Expected Result** | Dedicated page loads with Apple liquid glass styling. All fields populate cleanly. "Return to Opportunities" navigates smoothly back to `jobs.html`. |
| **How to Verify** | Inspect page elements; check Console for zero errors. |
| **Actual Result** | |
| **Status** | NOT TESTED |
| **Notes** | |

---

### HOT-JOB-003: Submitting a Job Application & Duplicate Prevention

| Field | Details |
| :--- | :--- |
| **Test ID** | HOT-JOB-003 |
| **Feature** | Application submission and duplicate application block |
| **Purpose** | Ensure job applications persist in MySQL and prevent duplicate submissions |
| **Required Setup** | Logged in as `johndoe` (User ID 2) |
| **Exact Steps** | 1. On `job-details.html?id=1`, click "Apply Now".<br>2. Enter message: `"I am excited to apply for this engineering position."`<br>3. Click "Submit Application".<br>4. Observe confirmation.<br>5. Try clicking "Apply Now" and submitting again for the same job. |
| **Expected Result** | First submission succeeds: modal displays success message and database inserts row into `job_applications` with `status = 'Applied'`. Second submission is blocked with: `"You have already applied for this position (Status: Applied)."` |
| **How to Verify** | In MySQL: `SELECT id, job_id, user_id, status FROM job_applications WHERE job_id = 1 AND user_id = 2;` -> exactly 1 row. |
| **Actual Result** | |
| **Status** | NOT TESTED |
| **Notes** | |

---

### HOT-JOB-004: Normal User Job Posting (Pending Moderation Lifecycle)

| Field | Details |
| :--- | :--- |
| **Test ID** | HOT-JOB-004 |
| **Feature** | User job creation and pending review workflow |
| **Purpose** | Verify normal users can submit vacancies that require admin approval |
| **Required Setup** | Logged in as `johndoe` |
| **Exact Steps** | 1. On `jobs.html`, click "+ Post a Job" (opens `jobcreate.html`).<br>2. Fill in: Title: `Cloud Security Analyst`, Company: `CyberShield Ltd`, Location: `Colombo`, Type: `Full-Time`, Description: `Security operations role.`, Requirements: `CISSP, Linux, AWS Security.`<br>3. Click "Submit Opportunity".<br>4. Observe redirect to `jobs.html?success=1`.<br>5. Check public "All Opportunities" tab vs "My Postings" tab. |
| **Expected Result** | Green banner appears on `jobs.html`: `"Opportunity submitted successfully! It has been submitted for administrator review..."` On public tab, the job does NOT appear. On "My Postings" tab, the job appears with an orange badge: `"Pending Approval"`. |
| **How to Verify** | In MySQL: `SELECT id, title, status FROM jobs WHERE title = 'Cloud Security Analyst';` -> status is `pending`. |
| **Actual Result** | |
| **Status** | NOT TESTED |
| **Notes** | |

---

### HOT-JOB-005: Form Validation on Empty Job Creation Submission

| Field | Details |
| :--- | :--- |
| **Test ID** | HOT-JOB-005 |
| **Feature** | Job submission form validation |
| **Purpose** | Ensure required fields cannot be submitted empty |
| **Required Setup** | Logged in as `johndoe` on `jobcreate.html` |
| **Exact Steps** | 1. Clear all inputs on `jobcreate.html`.<br>2. Click "Submit Opportunity". |
| **Expected Result** | Browser HTML5 validation prevents submission or page displays error banner. No record is inserted into the database. |
| **How to Verify** | Query MySQL to verify no job with empty title or company was created. |
| **Actual Result** | |
| **Status** | NOT TESTED |
| **Notes** | |

---

# Section G: Test Events and RSVP

### HOT-EVT-001: Anonymous Visitor Protected Action on Event RSVP

| Field | Details |
| :--- | :--- |
| **Test ID** | HOT-EVT-001 |
| **Feature** | Unauthenticated interception on event registration |
| **Purpose** | Verify visitors cannot RSVP without logging in |
| **Required Setup** | Anonymous browser session |
| **Exact Steps** | 1. Navigate to `http://localhost/alumni-network/event.html`.<br>2. Click "Register for Event" on Event ID 1. |
| **Expected Result** | Visitor is redirected to `auth/login.php?redirect=event.html%3Frsvp%3D1&msg=auth_required` with `"Please log in to continue."` notice. |
| **How to Verify** | Check browser URL and notice banner. |
| **Actual Result** | |
| **Status** | NOT TESTED |
| **Notes** | |

---

### HOT-EVT-002: Authenticated Event RSVP & Attendee Count Update

| Field | Details |
| :--- | :--- |
| **Test ID** | HOT-EVT-002 |
| **Feature** | Event RSVP registration |
| **Purpose** | Verify logged-in user can RSVP and attendee count updates live |
| **Required Setup** | Logged in as `johndoe` |
| **Exact Steps** | 1. Navigate to `event.html`.<br>2. Note the initial attendee count on Event ID 1 (e.g. 5 people).<br>3. Click "Register for Event".<br>4. Observe button text change and count increment. |
| **Expected Result** | Button updates to "Registered &#10003;" (or "Going"). Attendee count increments by 1. Network sends POST to `event.php?action=register` returning `{ "success": true }`. |
| **How to Verify** | In MySQL: `SELECT * FROM event_registrations WHERE event_id = 1 AND user_id = 2;` -> status is `attending`. |
| **Actual Result** | |
| **Status** | NOT TESTED |
| **Notes** | |

---

### HOT-EVT-003: Duplicate Event RSVP Handling

| Field | Details |
| :--- | :--- |
| **Test ID** | HOT-EVT-003 |
| **Feature** | Duplicate event RSVP prevention |
| **Purpose** | Ensure user cannot be double-registered |
| **Required Setup** | User `johndoe` already registered for Event 1 (from HOT-EVT-002) |
| **Exact Steps** | 1. Refresh `event.html`.<br>2. Verify button on Event 1 indicates already registered.<br>3. Click button again. |
| **Expected Result** | User is not registered a second time. Button toggles to un-register or displays registered confirmation. Attendee count does not erroneously inflate. |
| **How to Verify** | In MySQL: `SELECT COUNT(*) FROM event_registrations WHERE event_id = 1 AND user_id = 2;` -> count equals 1. |
| **Actual Result** | |
| **Status** | NOT TESTED |
| **Notes** | |

---

### HOT-EVT-004: Normal User Event Creation (Pending Moderation)

| Field | Details |
| :--- | :--- |
| **Test ID** | HOT-EVT-004 |
| **Feature** | Event creation and pending review workflow |
| **Purpose** | Verify normal users can submit events that require admin moderation |
| **Required Setup** | Logged in as `johndoe` |
| **Exact Steps** | 1. Navigate to `http://localhost/alumni-network/eventcreate.html`.<br>2. Fill in: Title: `Annual Alumni Hackathon 2026`, Date: `2026-11-20`, Start Time: `09:00`, End Time: `18:00`, Location: `Main Campus Computer Labs`, Capacity: `50`, Description: `24-hour coding challenge for alumni.`<br>3. Click "Submit Event".<br>4. Check public `event.html`. |
| **Expected Result** | Submission succeeds. Event is saved in database with `status = 'pending'`. Event does NOT appear on the public `event.html` listing yet. |
| **How to Verify** | In MySQL: `SELECT id, title, status FROM events WHERE title = 'Annual Alumni Hackathon 2026';` -> status is `pending`. |
| **Actual Result** | |
| **Status** | NOT TESTED |
| **Notes** | |

---

# Section H: Test UI, Themes, and Interactions

### HOT-UI-001: Apple/macOS Liquid Glass Buttons & Ambient Glow

| Field | Details |
| :--- | :--- |
| **Test ID** | HOT-UI-001 |
| **Feature** | Apple/macOS-inspired liquid glass button effects |
| **Purpose** | Verify visual depth, specular highlight, and purple glow on buttons |
| **Required Setup** | Any modern browser |
| **Exact Steps** | 1. Open `jobs.html` or `index.html`.<br>2. Hover mouse cursor over primary purple buttons (e.g., "+ Post a Job", "View & Apply").<br>3. Press and hold mouse button down, then release. |
| **Expected Result** | Button displays frosted translucent surface with specular highlight. Hovering smoothly increases purple ambient glow (`box-shadow`). Clicking triggers subtle press scale. |
| **How to Verify** | Inspect CSS pseudo-elements `::before` and `::after` in DevTools Elements tab. |
| **Actual Result** | |
| **Status** | NOT TESTED |
| **Notes** | |

---

### HOT-UI-002: Heading Liquid Glass Styling ("All Opportunities")

| Field | Details |
| :--- | :--- |
| **Test ID** | HOT-UI-002 |
| **Feature** | Liquid glass styling on main section heading |
| **Purpose** | Verify translucent glass effect on section titles |
| **Required Setup** | Open `jobs.html` |
| **Exact Steps** | 1. Inspect the "All Opportunities" heading banner on `jobs.html`.<br>2. Verify appearance in both Dark and Light themes. |
| **Expected Result** | Heading container renders with frosted glass backing, rounded pill border, subtle purple reflection, and legible typography in both modes. |
| **How to Verify** | Check computed styles for `backdrop-filter: blur(...)` and border opacity. |
| **Actual Result** | |
| **Status** | NOT TESTED |
| **Notes** | |

---

### HOT-UI-003: Keyboard Navigation & Focus Rings Accessibility

| Field | Details |
| :--- | :--- |
| **Test ID** | HOT-UI-003 |
| **Feature** | Keyboard accessibility (Tab navigation) |
| **Purpose** | Verify users can navigate the application without a mouse |
| **Required Setup** | Use only keyboard (`Tab`, `Shift + Tab`, `Enter`) |
| **Exact Steps** | 1. Open `jobs.html`.<br>2. Press `Tab` repeatedly through the page.<br>3. Observe focus indicators on theme options, search bar, filter dropdown, and action buttons.<br>4. Press `Enter` on a focused "View & Apply" button. |
| **Expected Result** | Focus rings (visible purple or cyan outline) are clearly visible around active elements. Pressing `Enter` triggers button action. |
| **How to Verify** | Confirm focus does not get trapped; check `:focus-visible` CSS rules. |
| **Actual Result** | |
| **Status** | NOT TESTED |
| **Notes** | |

---

### HOT-UI-004: Reduced Motion Preference Compliance

| Field | Details |
| :--- | :--- |
| **Test ID** | HOT-UI-004 |
| **Feature** | `prefers-reduced-motion` compliance |
| **Purpose** | Ensure users sensitive to motion can disable animations |
| **Required Setup** | DevTools -> More tools -> Rendering -> Emulate CSS media feature `prefers-reduced-motion: reduce` |
| **Exact Steps** | 1. Enable reduced motion in DevTools.<br>2. Hover over buttons and switch themes.<br>3. Scroll through the page. |
| **Expected Result** | Animations, button sheen movements, and pulsing glows are minimized or disabled. |
| **How to Verify** | Inspect `@media (prefers-reduced-motion: reduce)` in `theme.css`. |
| **Actual Result** | |
| **Status** | NOT TESTED |
| **Notes** | |

---

# Section I: Test Database Persistence and Error Handling

### HOT-DB-001: Database Record Verification for New Users

| Field | Details |
| :--- | :--- |
| **Test ID** | HOT-DB-001 |
| **Feature** | Relational user and profile record integrity |
| **Purpose** | Confirm registration creates properly linked records across `users` and `alumni_profiles` |
| **Required Setup** | User `testmember99` registered (from HOT-AUTH-001) |
| **Exact Steps** | In MySQL CLI or phpMyAdmin, run:<br>```sql
SELECT u.id, u.username, u.email, u.role, p.graduation_year, p.degree_programme 
FROM users u 
JOIN alumni_profiles p ON u.id = p.user_id 
WHERE u.username = 'testmember99';
``` |
| **Expected Result** | Query returns exactly 1 row joining user and profile data with matching `user_id`. |
| **How to Verify** | Check output values match submitted registration form data. |
| **Actual Result** | |
| **Status** | NOT TESTED |
| **Notes** | |

---

### HOT-DB-002: Invalid Entity ID Handling (404 / Error State)

| Field | Details |
| :--- | :--- |
| **Test ID** | HOT-DB-002 |
| **Feature** | Handling non-existent record IDs in URL |
| **Purpose** | Ensure invalid IDs do not crash the application or leak SQL errors |
| **Required Setup** | Browser open |
| **Exact Steps** | Navigate to `http://localhost/alumni-network/job-details.html?id=9999999` and `http://localhost/alumni-network/job-details.php?id=9999999`. |
| **Expected Result** | Page displays clean error notice: `"Opportunity not found or inactive."` No raw PHP fatal error stack trace is displayed. |
| **How to Verify** | Check page content; confirm no file paths or SQL query dumps are visible. |
| **Actual Result** | |
| **Status** | NOT TESTED |
| **Notes** | |

---

### HOT-DB-003: Graceful Database Offline Degradation

| Field | Details |
| :--- | :--- |
| **Test ID** | HOT-DB-003 |
| **Feature** | Service outage error handling |
| **Purpose** | Ensure application does not crash catastrophically if MySQL stops |
| **Required Setup** | In XAMPP Control Panel, temporarily stop MySQL |
| **Exact Steps** | 1. Stop MySQL service.<br>2. Refresh `http://localhost/alumni-network/jobs.html`.<br>3. Inspect page response and console.<br>4. Restart MySQL service in XAMPP. |
| **Expected Result** | Page deploys safe fallback job data (`fallbackJobs`) without broken JSON parsing error. No credentials or server paths are leaked to the client. |
| **How to Verify** | Check console logs fallback warning; verify page remains navigable. |
| **Actual Result** | |
| **Status** | NOT TESTED |
| **Notes** | |

---

# Section J: Test Cleanup & Database Restoration

After completing all hands-on tests, clean up temporary test records to leave the environment in a clean state.

### J.1 Safely Remove Test Users & Records
Execute the following SQL queries in phpMyAdmin or MySQL CLI:
```sql
USE alumni_network;

-- Remove test applications created during testing:
DELETE FROM job_applications WHERE user_id IN (
    SELECT id FROM users WHERE username LIKE 'testmember%' OR username LIKE 'hackadmin%'
);

-- Remove test event registrations created during testing:
DELETE FROM event_registrations WHERE user_id IN (
    SELECT id FROM users WHERE username LIKE 'testmember%' OR username LIKE 'hackadmin%'
);

-- Remove test job postings created during testing:
DELETE FROM jobs WHERE title IN ('Cloud Security Analyst', 'Ghost Job');

-- Remove test events created during testing:
DELETE FROM events WHERE title IN ('Annual Alumni Hackathon 2026');

-- Remove test user accounts (Profiles will cascade delete automatically):
DELETE FROM users WHERE username IN ('testmember99', 'hackadmin');
```

### J.2 Safely Remove the Three Administrator Test Accounts
```sql
USE alumni_network;

-- Delete only the three dedicated QA admin accounts:
DELETE FROM users 
WHERE username IN ('admin_qa1', 'admin_qa2', 'admin_qa3') 
  AND role = 'admin' 
  AND id > 1; -- Safeguard: Never delete primary system administrator ID 1
```

### J.3 Restoring the Test Database Backup (If Needed)
If you made irreversible modifications or wish to reset the database to a clean seed state:
```powershell
# Restore from your pre-test backup:
mysql -u root alumni_network < database/backup_pre_test.sql

# OR re-run the clean seeder:
C:\xampp\php\php.exe config/setup.php
```

### J.4 Git Repository Hygiene Check
Ensure temporary files, database dumps, and credentials are never committed:
```powershell
git status
```
*Verify that only your intended testing documentation files appear under `project-documentation/testing/`.*
