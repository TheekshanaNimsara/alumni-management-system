# 03 — Authentication and Permissions Test Cases

**Project:** University Alumni Network  
**Test Suite:** SUITE-AUTH & SUITE-PERM  
**Target Architecture:** PHP 8.2 PDO, MySQL, Bcrypt Hashing, Role-Based Access Control (RBAC)  
**Execution Environment:** Local Dynamic Full-Stack (XAMPP: Apache + MySQL)  

---

## 1. Authentication Test Cases (AUTH)

### AUTH-001: User Login with Valid Credentials (Normal Alumni)

| Field | Details |
| :--- | :--- |
| **Test ID** | AUTH-001 |
| **Feature** | User authentication with verified credentials |
| **Prerequisites** | Website running on `http://localhost/alumni-network/`; database seeded with user `johndoe` (`Alumni@123`) |
| **Steps** | 1. Open browser and navigate to `http://localhost/alumni-network/auth/login.php`.<br>2. In the "Email or Username" field, input `johndoe` (or `john.doe@alumni.edu`).<br>3. In the "Password" field, input `Alumni@123`.<br>4. Click the "Sign In" button.<br>5. Inspect Network tab and Application storage tab. |
| **Expected result** | 1. HTTP POST to `auth/login.php` returns HTTP 302 Found redirecting to `../index.php`.<br>2. Browser cookie `PHPSESSID` is set with `HttpOnly` and `SameSite=Lax`.<br>3. Server session holds `$_SESSION['user_id'] = 2`, `$_SESSION['user_role'] = 'alumni'`, `$_SESSION['user_name'] = 'John Doe'`.<br>4. Navigation header displays user controls (e.g. Profile, Logout) and hides login/register links. |
| **Actual result** | |
| **Status** | Not Tested |
| **Notes** | |

---

### AUTH-002: User Login with Invalid Password

| Field | Details |
| :--- | :--- |
| **Test ID** | AUTH-002 |
| **Feature** | Password mismatch rejection and error messaging |
| **Prerequisites** | Website running on `http://localhost/alumni-network/`; database seeded |
| **Steps** | 1. Navigate to `http://localhost/alumni-network/auth/login.php`.<br>2. In "Email or Username", input `johndoe`.<br>3. In "Password", input `WrongPassword!999`.<br>4. Click "Sign In".<br>5. Observe page response and inspect DOM. |
| **Expected result** | 1. Form does not log user in; remains on `login.php`.<br>2. Alert banner displays: `"Invalid email/username or password. Please verify your credentials and try again."`<br>3. No session credentials are set in `$_SESSION`.<br>4. Password field is cleared for security. |
| **Actual result** | |
| **Status** | Not Tested |
| **Notes** | |

---

### AUTH-003: User Login with Non-Existent User

| Field | Details |
| :--- | :--- |
| **Test ID** | AUTH-003 |
| **Feature** | Non-existent account defense and user enumeration prevention |
| **Prerequisites** | Website running on `http://localhost/alumni-network/` |
| **Steps** | 1. Navigate to `http://localhost/alumni-network/auth/login.php`.<br>2. In "Email or Username", input `ghost.alumni@nonexistent.edu`.<br>3. In "Password", input `SomePass@123`.<br>4. Click "Sign In". |
| **Expected result** | 1. Form returns generic error: `"Invalid email/username or password. Please verify your credentials and try again."`<br>2. Does not reveal whether the username or email exists in the database.<br>3. Response status is 200 OK without session creation. |
| **Actual result** | |
| **Status** | Not Tested |
| **Notes** | |

---

### AUTH-004: User Registration with Valid Inputs

| Field | Details |
| :--- | :--- |
| **Test ID** | AUTH-004 |
| **Feature** | New member registration and Bcrypt password hashing |
| **Prerequisites** | Website running on `http://localhost/alumni-network/`; database accessible |
| **Steps** | 1. Navigate to `http://localhost/alumni-network/auth/register.php` (or click "Register" on homepage).<br>2. Fill all mandatory fields:<br>&nbsp;&nbsp;&nbsp;- First Name: `David`<br>&nbsp;&nbsp;&nbsp;- Last Name: `Silva`<br>&nbsp;&nbsp;&nbsp;- Username: `davidsilva`<br>&nbsp;&nbsp;&nbsp;- Email: `david.silva@alumni.edu`<br>&nbsp;&nbsp;&nbsp;- Graduation Year: `2024`<br>&nbsp;&nbsp;&nbsp;- Degree: `BSc Software Engineering`<br>&nbsp;&nbsp;&nbsp;- Password: `Secure@2026`<br>&nbsp;&nbsp;&nbsp;- Confirm Password: `Secure@2026`<br>3. Click "Create Account".<br>4. Query database: `SELECT id, username, email, password, role FROM users WHERE username = 'davidsilva';` |
| **Expected result** | 1. Form validates successfully and redirects to login or dashboard with success message.<br>2. Database record inserted into `users` table with `role = 'alumni'`, `status = 'active'`.<br>3. `password` column contains a valid 60-character Bcrypt hash (`$2y$10$...`), never plain text.<br>4. Linked profile record created in `alumni_profiles`. |
| **Actual result** | |
| **Status** | Not Tested |
| **Notes** | |

---

### AUTH-005: User Registration Duplicate Email & Password Mismatch Validation

| Field | Details |
| :--- | :--- |
| **Test ID** | AUTH-005 |
| **Feature** | Client-side and server-side validation error handling |
| **Prerequisites** | User `john.doe@alumni.edu` already exists in database |
| **Steps** | 1. Navigate to `http://localhost/alumni-network/auth/register.php`.<br>2. Attempt to register with email `john.doe@alumni.edu`.<br>3. Enter mismatched passwords (`Test@123` vs `Test@456`).<br>4. Click "Create Account". |
| **Expected result** | 1. Registration is halted.<br>2. Clear error message is displayed informing user of password mismatch or existing email address.<br>3. No duplicate row is inserted into the database. |
| **Actual result** | |
| **Status** | Not Tested |
| **Notes** | |

---

### AUTH-006: Session ID Regeneration upon Privilege Escalation

| Field | Details |
| :--- | :--- |
| **Test ID** | AUTH-006 |
| **Feature** | Session fixation defense (`session_regenerate_id(true)`) |
| **Prerequisites** | Website running on `http://localhost/alumni-network/` |
| **Steps** | 1. Open DevTools -> Application -> Storage -> Cookies.<br>2. Record the initial `PHPSESSID` cookie value prior to logging in.<br>3. Submit valid credentials on `auth/login.php` (`johndoe` / `Alumni@123`).<br>4. Inspect the `PHPSESSID` cookie value immediately after successful redirection. |
| **Expected result** | 1. The `PHPSESSID` string after login differs from the initial guest `PHPSESSID`.<br>2. The old session file on the server is invalidated (`session_regenerate_id(true)` executed). |
| **Actual result** | |
| **Status** | Not Tested |
| **Notes** | |

---

### AUTH-007: User Logout and Complete Session Invalidation

| Field | Details |
| :--- | :--- |
| **Test ID** | AUTH-007 |
| **Feature** | Graceful user logout and session cookie termination |
| **Prerequisites** | User is logged in as `johndoe` |
| **Steps** | 1. Click "Logout" in header navigation (or navigate to `http://localhost/alumni-network/auth/logout.php`).<br>2. Observe redirection destination.<br>3. Attempt to navigate back via browser "Back" button.<br>4. Inspect `$_SESSION` and request protected endpoint (`actions/job.php?action=session`). |
| **Expected result** | 1. User is redirected to `index.php` or `auth/login.php?logout=1`.<br>2. Server session is completely destroyed (`session_destroy()`, `$_SESSION = []`).<br>3. Session cookie is expired in browser.<br>4. Direct call to `job.php?action=session` returns `{"logged_in": false, "user_id": null, "role": "guest"}`. |
| **Actual result** | |
| **Status** | Not Tested |
| **Notes** | |

---

### AUTH-008: Suspended Account Login Rejection

| Field | Details |
| :--- | :--- |
| **Test ID** | AUTH-008 |
| **Feature** | Enforcement of user status flags (`suspended` / `inactive`) |
| **Prerequisites** | Temporarily set a test user's status to `suspended`: `UPDATE users SET status = 'suspended' WHERE id = 7;` |
| **Steps** | 1. Navigate to `http://localhost/alumni-network/auth/login.php`.<br>2. Enter credentials for user 7 (`priyas` / `Alumni@123`).<br>3. Click "Sign In".<br>4. Reset status back: `UPDATE users SET status = 'active' WHERE id = 7;` |
| **Expected result** | 1. Login fails.<br>2. Banner displays: `"This account has been suspended. Please contact the administrator."`<br>3. No session is initiated. |
| **Actual result** | |
| **Status** | Not Tested |
| **Notes** | |

---

## 2. Role-Based Access Control & Route Protection (PERM)

### PERM-001: Anonymous Visitor Navigation Bar Link Visibility

| Field | Details |
| :--- | :--- |
| **Test ID** | PERM-001 |
| **Feature** | Public header navigation visibility for unauthenticated visitors |
| **Prerequisites** | Clear all browser cookies and session storage; visitor is anonymous |
| **Steps** | 1. Open browser in Incognito / Private window.<br>2. Navigate to `http://localhost/alumni-network/index.php` (and `jobs.html`, `event.html`).<br>3. Inspect the top navigation bar and DOM elements. |
| **Expected result** | 1. Navigation displays public links: Home, Alumni, Events, Jobs, About, and Login/Register.<br>2. Element `#adminNavLink` ("Job Admin", "Event Admin", or "Admin Portal") has `display: none` or is absent.<br>3. No administrator dropdowns or moderation tabs are visible.<br>4. In `jobs.html`, the "My Postings" tab (`#myJobsTab`) is hidden. |
| **Actual result** | |
| **Status** | Not Tested |
| **Notes** | |

---

### PERM-002: Normal User Navigation Bar Link Visibility

| Field | Details |
| :--- | :--- |
| **Test ID** | PERM-002 |
| **Feature** | Standard alumni user navigation state |
| **Prerequisites** | Logged in as normal user `johndoe` (`role = 'alumni'`) |
| **Steps** | 1. Log in at `http://localhost/alumni-network/auth/login.php`.<br>2. Navigate across `index.php`, `jobs.html`, and `event.html`.<br>3. Inspect navigation bar. |
| **Expected result** | 1. Navigation displays user account menu (Profile, My Applications, Logout).<br>2. Element `#adminNavLink` remains strictly hidden (`display: none`).<br>3. In `jobs.html`, "+ Post a Job" button is active.<br>4. In `jobs.html`, "My Postings" tab (`#myJobsTab`) is visible and functional.<br>5. No "Admin", "Approve", or "Reject" management controls are rendered. |
| **Actual result** | |
| **Status** | Not Tested |
| **Notes** | |

---

### PERM-003: Administrator Navigation Bar Link Visibility

| Field | Details |
| :--- | :--- |
| **Test ID** | PERM-003 |
| **Feature** | Administrative portal navigation links for authenticated admins |
| **Prerequisites** | Logged in as administrator `admin` (`role = 'admin'`, `Admin@123`) |
| **Steps** | 1. Log in at `http://localhost/alumni-network/auth/login.php` with `admin` / `Admin@123`.<br>2. Navigate across `index.php`, `jobs.html`, and `event.html`.<br>3. Inspect navigation bar and quick links. |
| **Expected result** | 1. Navigation displays `#adminNavLink` ("Admin Portal" / "Job Admin" / "Event Admin") prominently.<br>2. Clicking `#adminNavLink` navigates to `admin/index.php` (or `jobadmin.html` / `eventadmin.html`).<br>3. Administration dashboard options (Jobs Moderation, Events Moderation, User Management, Reports) are accessible. |
| **Actual result** | |
| **Status** | Not Tested |
| **Notes** | |

---

### PERM-004: Direct URL Access Guard on Admin PHP Scripts (Anonymous)

| Field | Details |
| :--- | :--- |
| **Test ID** | PERM-004 |
| **Feature** | Server-side enforcement of `require_admin()` for anonymous requests |
| **Prerequisites** | Logged out (Anonymous session) |
| **Steps** | 1. Open DevTools Network tab with "Preserve log" enabled.<br>2. Attempt direct browser navigation to each of the following URLs:<br>&nbsp;&nbsp;&nbsp;- `http://localhost/alumni-network/admin/index.php`<br>&nbsp;&nbsp;&nbsp;- `http://localhost/alumni-network/admin/jobs.php`<br>&nbsp;&nbsp;&nbsp;- `http://localhost/alumni-network/admin/events.php`<br>&nbsp;&nbsp;&nbsp;- `http://localhost/alumni-network/admin/users.php`<br>&nbsp;&nbsp;&nbsp;- `http://localhost/alumni-network/admin/reports.php` |
| **Expected result** | 1. Server denies direct access immediately via PHP `require_admin()`.<br>2. Browser receives HTTP 302 redirecting to `auth/login.php?admin_required=1`.<br>3. No administration data, tables, or sensitive user records are leaked in HTML body. |
| **Actual result** | |
| **Status** | Not Tested |
| **Notes** | |

---

### PERM-005: Normal User Blocked from Admin Pages (RBAC Enforcement)

| Field | Details |
| :--- | :--- |
| **Test ID** | PERM-005 |
| **Feature** | Server-side enforcement preventing normal alumni from accessing admin backend |
| **Prerequisites** | Logged in as normal user `johndoe` (`role = 'alumni'`) |
| **Steps** | 1. Log in with `johndoe` / `Alumni@123`.<br>2. In the browser URL bar, manually enter `http://localhost/alumni-network/admin/jobs.php`.<br>3. Observe response and redirection. |
| **Expected result** | 1. Access is denied because `$_SESSION['user_role'] !== 'admin'`.<br>2. User is redirected to `auth/login.php?admin_required=1` or presented with HTTP 403 Forbidden.<br>3. The user cannot view pending jobs belonging to other alumni. |
| **Actual result** | |
| **Status** | Not Tested |
| **Notes** | |

---

### PERM-006: API Endpoint RBAC Guard on Jobs Controller (`job.php`)

| Field | Details |
| :--- | :--- |
| **Test ID** | PERM-006 |
| **Feature** | Server-side protection on API actions (`approve`, `reject`, `delete`) |
| **Prerequisites** | Web browser / Postman / PowerShell `Invoke-RestMethod` |
| **Steps** | 1. As an unauthenticated caller (or logged in as normal user `johndoe`), send a POST request to `http://localhost/alumni-network/job.php?action=approve` with body `id=1`.<br>2. Send a POST request to `http://localhost/alumni-network/job.php?action=reject` with body `id=1`.<br>3. Send a POST request to `http://localhost/alumni-network/job.php?action=delete` with body `id=1` (where job 1 is not owned by caller). |
| **Expected result** | 1. For `approve`: Server responds with HTTP 403 Forbidden and JSON `{ "success": false, "error": "Forbidden: Administrator privileges required." }`.<br>2. For `reject`: Server responds with HTTP 403 Forbidden and JSON error.<br>3. For `delete`: Server responds with HTTP 403 Forbidden and JSON error.<br>4. Database state of job 1 remains unchanged in `jobs` table. |
| **Actual result** | |
| **Status** | Not Tested |
| **Notes** | |

---

### PERM-007: API Endpoint RBAC Guard on Events Controller (`event.php`)

| Field | Details |
| :--- | :--- |
| **Test ID** | PERM-007 |
| **Feature** | Server-side protection on Event administrative API actions |
| **Prerequisites** | Logged in as normal user `johndoe` |
| **Steps** | 1. Send POST request to `http://localhost/alumni-network/event.php?action=approve` with body `id=1`.<br>2. Send POST request to `http://localhost/alumni-network/event.php?action=reject` with body `id=1`.<br>3. Inspect response status code and body. |
| **Expected result** | 1. Server responds with HTTP 403 Forbidden.<br>2. JSON payload indicates administrator privileges are required.<br>3. The event status in `events` table remains unchanged. |
| **Actual result** | |
| **Status** | Not Tested |
| **Notes** | |
