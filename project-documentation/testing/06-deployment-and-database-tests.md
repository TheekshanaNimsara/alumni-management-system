# 06 — Deployment, Database Integrity, and Security Test Cases

**Project:** University Alumni Network  
**Test Suite:** SUITE-DATA & SUITE-DEP  
**Target Architecture:** MySQL Relational Integrity, Prepared PDO Statements, Dual-Hosting Architecture (XAMPP vs GitHub Pages)  
**Execution Environment:** Local Full-Stack & Live Demonstration Deployment  

---

## 1. Database Integrity & Backend Security Test Cases (DATA)

### DATA-001: SQL Injection Defense via PDO Prepared Statements

| Field | Details |
| :--- | :--- |
| **Test ID** | DATA-001 |
| **Feature** | Defense against SQL injection across inputs and API parameters |
| **Prerequisites** | Local XAMPP environment running with live MySQL database |
| **Steps** | 1. Navigate to `http://localhost/alumni-network/auth/login.php`.<br>2. In the "Email or Username" field, input: `' OR '1'='1' -- `.<br>3. In the password field, enter any string: `pass123`.<br>4. Submit the form.<br>5. Navigate to `http://localhost/alumni-network/job.php?action=get&status=' OR 1=1 --`.<br>6. Send POST to `http://localhost/alumni-network/job.php?action=approve` with payload `id=1 OR 1=1`. |
| **Expected result** | 1. Login attempt fails securely with `"Invalid email/username or password..."`<br>2. Parameterized queries (`$stmt->prepare()` with `?` or `:params`) treat malicious SQL payloads strictly as string literals.<br>3. No database syntax errors or database structure leaks are emitted.<br>4. API calls cast integer inputs safely via `intval()` or prepared statements, returning HTTP 400 or 403. |
| **Actual result** | |
| **Status** | Not Tested |
| **Notes** | |

---

### DATA-002: Foreign Key Cascade Integrity on Record Deletion

| Field | Details |
| :--- | :--- |
| **Test ID** | DATA-002 |
| **Feature** | Relational integrity and CASCADE deletion in MySQL |
| **Prerequisites** | User has created job postings and submitted job applications |
| **Steps** | 1. Identify a test user ID with linked jobs and applications (e.g. User ID 6 `dkim`).<br>2. Note related rows in `job_applications` and `event_registrations`.<br>3. Delete the user from the database: `DELETE FROM users WHERE id = 6;`<br>4. Query related tables: `SELECT * FROM job_applications WHERE user_id = 6;` and `SELECT * FROM alumni_profiles WHERE user_id = 6;`. |
| **Expected result** | 1. Foreign key constraints enforce relational integrity without database crash.<br>2. Dependent rows are either cascaded (`ON DELETE CASCADE`) or nullified according to `database/schema.sql`.<br>3. No orphaned dangling records exist that crash subsequent joins. |
| **Actual result** | |
| **Status** | Not Tested |
| **Notes** | |

---

### DATA-003: Graceful Database Connection Offline Fallback

| Field | Details |
| :--- | :--- |
| **Test ID** | DATA-003 |
| **Feature** | System resilience when MySQL service is temporarily unavailable |
| **Prerequisites** | Local XAMPP environment running |
| **Steps** | 1. In XAMPP Control Panel, click **Stop** on the MySQL service.<br>2. Navigate to `http://localhost/alumni-network/index.php`.<br>3. Navigate to `http://localhost/alumni-network/jobs.php` (or `jobs.html`).<br>4. Observe the page response and inspect HTML body. |
| **Expected result** | 1. The application does NOT emit raw fatal PHP PDO stack traces revealing database credentials or server paths.<br>2. `config/db.php` catches the `PDOException` safely.<br>3. In jobs board, fallback sample dataset (`get_sample_jobs()`) is returned or user is informed of service unavailability gracefully.<br>4. Restarting MySQL restores full dynamic functionality. |
| **Actual result** | |
| **Status** | Not Tested |
| **Notes** | |

---

### DATA-004: Stored Cross-Site Scripting (XSS) Sanitization

| Field | Details |
| :--- | :--- |
| **Test ID** | DATA-004 |
| **Feature** | Sanitization of user-submitted content prior to DOM rendering |
| **Prerequisites** | Logged in as normal user `johndoe` |
| **Steps** | 1. Navigate to `jobcreate.html`.<br>2. In the "Job Title" field, enter: `<script>alert('XSS_JOB')</script>`.<br>3. In "Description", enter: `<img src="invalid-image" onerror="alert('XSS_DESC')">`.<br>4. In "Company", enter: `<b>BoldCorp</b>`.<br>5. Submit the form.<br>6. View the listing on `jobs.html` (under "My Postings" tab) and in `jobadmin.html`. |
| **Expected result** | 1. No browser JavaScript alert boxes are triggered.<br>2. In `jobs.js`, strings are escaped via `safe()` helper or rendered via `textContent` rather than raw `innerHTML`.<br>3. Tags render as visible literal text (e.g. `&lt;script&gt;`) without script execution. |
| **Actual result** | |
| **Status** | Not Tested |
| **Notes** | |

---

### DATA-005: Open Redirect Protection on `redirect` Parameter

| Field | Details |
| :--- | :--- |
| **Test ID** | DATA-005 |
| **Feature** | Defense against external URL phishing via login redirect parameter |
| **Prerequisites** | Browser open |
| **Steps** | 1. Navigate to `http://localhost/alumni-network/auth/login.php?redirect=https://evil-phishing-site.com`.<br>2. Navigate to `http://localhost/alumni-network/auth/login.php?redirect=//evil-phishing-site.com`.<br>3. Navigate to `http://localhost/alumni-network/auth/login.php?redirect=javascript:alert(1)`.<br>4. In each case, log in with valid credentials (`johndoe` / `Alumni@123`).<br>5. Inspect final redirection destination. |
| **Expected result** | 1. Server regex `!preg_match('#^(https?:|//|javascript:|data:)#i', $redirect)` detects external/protocol-relative URLs.<br>2. External redirect is discarded.<br>3. User is safely redirected to internal home (`../index.php`).<br>4. No external site or malicious scheme is ever navigated to. |
| **Actual result** | |
| **Status** | Not Tested |
| **Notes** | |

---

### DATA-006: Session Cookie Security Flags & Lifetime

| Field | Details |
| :--- | :--- |
| **Test ID** | DATA-006 |
| **Feature** | Security flags on `PHPSESSID` cookie (`HttpOnly`, `SameSite`) |
| **Prerequisites** | Logged into `http://localhost/alumni-network/` |
| **Steps** | 1. Open DevTools Application / Storage -> Cookies -> `http://localhost`.<br>2. Locate `PHPSESSID`.<br>3. Inspect the column values for **HttpOnly**, **SameSite**, and **Secure**.<br>4. In DevTools Console, type: `document.cookie`. |
| **Expected result** | 1. `HttpOnly` is checked (true).<br>2. `SameSite` is set to `Lax`.<br>3. In Console, `document.cookie` does NOT reveal `PHPSESSID` (protected against client-side script theft).<br>4. Cookie lifetime is set to session (expires on browser close). |
| **Actual result** | |
| **Status** | Not Tested |
| **Notes** | |

---

## 2. Deployment & Static Hosting Test Cases (DEP)

### DEP-001: Static Host Fallback Mock Dataset (GitHub Pages)

| Field | Details |
| :--- | :--- |
| **Test ID** | DEP-001 |
| **Feature** | Graceful fallback when PHP endpoint returns unparsed source on static hosts |
| **Prerequisites** | Website accessed on GitHub Pages (`https://theekshananimsara.github.io/alumni-management-system/jobs.html`) |
| **Steps** | 1. Open DevTools Network tab and Console tab.<br>2. Navigate to `jobs.html` on GitHub Pages.<br>3. Observe network request to `job.php?action=get`.<br>4. Observe console messages and rendered cards. |
| **Expected result** | 1. When `fetch("job.php?action=get")` returns raw PHP source or HTTP 404, `jobs.js` catches it cleanly.<br>2. Console logs: `"Backend returned PHP source or HTML document. Deploying safe fallback job data."`<br>3. `fallbackJobs` array renders 4 initial job postings cleanly.<br>4. Page does NOT display broken JSON parsing errors or blank state. |
| **Actual result** | |
| **Status** | Not Tested |
| **Notes** | |

---

### DEP-002: Static Host Demo Login Workflow

| Field | Details |
| :--- | :--- |
| **Test ID** | DEP-002 |
| **Feature** | Client-side simulation of login state on GitHub Pages |
| **Prerequisites** | Static GitHub Pages environment (`login.html`) |
| **Steps** | 1. Open `https://theekshananimsara.github.io/alumni-management-system/login.html`.<br>2. Enter test username and password.<br>3. Click "Sign In".<br>4. Observe alert banner and redirection.<br>5. Check `sessionStorage.getItem('alumni_logged_in')`. |
| **Expected result** | 1. `handleDemoLogin(e)` intercepts submission.<br>2. Banner displays: `"Authentication successful! Redirecting..."`<br>3. `sessionStorage` records `alumni_logged_in = 'true'`.<br>4. User is redirected to `index.html?login_success=1` (or specified return URL). |
| **Actual result** | |
| **Status** | Not Tested |
| **Notes** | |

---

### DEP-003: Subfolder Relative Path Resolution

| Field | Details |
| :--- | :--- |
| **Test ID** | DEP-003 |
| **Feature** | Cross-directory relative link integrity in root and subdirectories |
| **Prerequisites** | Hosted in GitHub Pages subpath (`/alumni-management-system/`) |
| **Steps** | 1. Click every navigation link from root pages (`index.html`, `jobs.html`, `event.html`).<br>2. Click return links from secondary pages (`job-details.html`, `jobcreate.html`, `login.html`).<br>3. Verify stylesheet `<link>` tags and `<script src="...">` paths in DOM. |
| **Expected result** | 1. All paths use relative addressing (`./assets/css/...`, `./index.html`, `../index.php`).<br>2. No links break or navigate to an unintended parent root domain (`https://theekshananimsara.github.io/index.html` 404).<br>3. All CSS files, JS files, and images load with HTTP 200. |
| **Actual result** | |
| **Status** | Not Tested |
| **Notes** | |

---

### DEP-004: Sensitive Configuration Direct Exposure Audit

| Field | Details |
| :--- | :--- |
| **Test ID** | DEP-004 |
| **Feature** | Direct browser access prevention on backend config and repository files |
| **Prerequisites** | Local XAMPP environment running |
| **Steps** | 1. In browser, navigate directly to `http://localhost/alumni-network/.git/HEAD`.<br>2. Navigate to `http://localhost/alumni-network/config/db.php`.<br>3. Navigate to `http://localhost/alumni-network/uploads/.htaccess`. |
| **Expected result** | 1. `.git/HEAD` returns HTTP 403 Forbidden or 404.<br>2. Direct visit to `config/db.php` renders blank white page (PHP executes safely without outputting source code or database passwords).<br>3. Upload directory `.htaccess` prevents script execution inside `uploads/`. |
| **Actual result** | |
| **Status** | Not Tested |
| **Notes** | |

---

### DEP-005: Favicons, SVG Icons, and Static Assets 404 Audit

| Field | Details |
| :--- | :--- |
| **Test ID** | DEP-005 |
| **Feature** | Comprehensive static asset availability check |
| **Prerequisites** | DevTools Network tab open with filter set to "Img" / "All" |
| **Steps** | 1. Hard reload (`Ctrl + Shift + R`) across `index.html`, `jobs.html`, `event.html`, and `login.html`.<br>2. Filter network entries by status code `404`.<br>3. Verify favicon rendering in browser tab. |
| **Expected result** | 1. Zero 404 errors for all favicon files (`favicon.ico`, `favicon-16x16.png`, `favicon-32x32.png`, `apple-touch-icon.png`, `favicon.svg`).<br>2. Zero 404 errors for SVG hero images (`campus-hero.svg`, `default-avatar.svg`).<br>3. Favicon appears clearly in browser tab bar. |
| **Actual result** | |
| **Status** | Not Tested |
| **Notes** | |
