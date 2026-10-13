# 01 — Test Environment Setup & Configuration Guide

**Project:** University Alumni Network  
**Target Environments:** Local Dynamic Full-Stack (XAMPP / PHP 8.2 + MySQL) & Static Demonstration (GitHub Pages)  
**Document Purpose:** Detailed manual instructions to safely prepare, configure, and inspect the testing environment prior to running test suites.

---

## 1. Architectural Environments Overview

The University Alumni Network is engineered with a **hybrid architectural model**:
1. **Dynamic Full-Stack Mode (Primary / Production)**: Powered by Apache, PHP 8.2+ PDO, and MySQL. Provides server-side session management, Bcrypt password hashing, relational data persistence, file uploads, and role-based access control (RBAC).
2. **Static Client-Side Mode (GitHub Pages)**: Runs without a PHP interpreter or live SQL database. Employs graceful client-side degradation, mock fallback datasets (`fallbackJobs`, sample events), and browser `sessionStorage` simulation.

| Capability / Feature | Dynamic Mode (`http://localhost/alumni-network/`) | Static Mode (`GitHub Pages`) |
| :--- | :--- | :--- |
| **Authentication Logic** | Server-side PHP session (`$_SESSION`), Bcrypt `password_verify` | Client-side `sessionStorage.getItem('alumni_logged_in')` |
| **Data Storage** | MySQL relational database (`alumni_network`) | Read-only in-memory arrays (`fallbackJobs`, static events) |
| **Job / Event Posting** | Persistent SQL `INSERT`, moderation status (`pending`/`approved`) | Simulated browser alert or local DOM update |
| **Application / RSVP** | Writes to `job_applications` / `event_registrations` | Client-side feedback notification |
| **Admin Moderation** | Server-enforced SQL `UPDATE` / `DELETE` (RBAC guarded) | Simulated UI state in `jobadmin.html` / `eventadmin.html` |
| **Theme Switcher** | `localStorage` (`alumni_theme_preference`) | `localStorage` (`alumni_theme_preference`) |
| **Liquid Glass UI** | Full CSS3 / Vanilla JS support | Full CSS3 / Vanilla JS support |

---

## 2. Setting Up the Dynamic Testing Environment (Local XAMPP)

### Step 2.1: Verify Software Prerequisites
Ensure the following tools are installed and accessible on your local Windows system:
* **XAMPP** (located at `C:\xampp` with Apache and MySQL modules)
* **PHP CLI**: PHP 8.1+ (verified: `C:\xampp\php\php.exe -v`)
* **MySQL / MariaDB**: Verified port 3306
* **Web Browser**: Modern Chromium (Chrome / Edge) or Firefox with Developer Tools enabled

### Step 2.2: Start Apache and MySQL Services
1. Open the **XAMPP Control Panel** (`C:\xampp\xampp-control.exe`).
2. Click **Start** adjacent to **Apache** (ports 80, 443).
3. Click **Start** adjacent to **MySQL** (port 3306).
4. Verify both service badges display green.

*Alternative PowerShell command check:*
```powershell
Get-Process -Name httpd, mysqld -ErrorAction SilentlyContinue
Get-NetTCPConnection -LocalPort 80, 3306 -State Listen -ErrorAction SilentlyContinue
```

### Step 2.3: Safe Database Initialization and Seeding
The project database configuration is located in `config/db.php`:
* **Host:** `127.0.0.1`
* **Port:** `3306`
* **Database Name:** `alumni_network`
* **Username:** `root`
* **Password:** *(empty string)*

To initialize the database schema and populate seed data:

#### Method A: Using Built-in CLI Installer (Recommended)
Open PowerShell in the project root (`c:\xampp\htdocs\alumni-network`) and run:
```powershell
C:\xampp\php\php.exe config/setup.php
```
*Expected Output:*
```text
Connected to MySQL server on localhost.
Database `alumni_network` and all tables created successfully!
Seed data for users, profiles, events, and jobs inserted successfully!
Default Admin: admin@alumni.edu (Password: Admin@123)
Default Alumni: john.doe@alumni.edu (Password: Alumni@123)
```

#### Method B: Using MySQL CLI Directly
```powershell
Get-Content database\schema.sql | mysql -u root -p
```

### Step 2.4: Seed Accounts & Test Credentials
The automated seeder populates the following verified accounts:

| Role | Username / Identity | Email | Password | User ID | Initial Status |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Administrator** | `admin` | `admin@alumni.edu` | `Admin@123` | 1 | `active` |
| **Alumni (Normal)** | `johndoe` | `john.doe@alumni.edu` | `Alumni@123` | 2 | `active` |
| **Alumni (Normal)** | `sarahj` | `sarah.j@alumni.edu` | `Alumni@123` | 3 | `active` |
| **Alumni (Normal)** | `mchen` | `m.chen@alumni.edu` | `Alumni@123` | 4 | `active` |
| **Alumni (Normal)** | `ananyap` | `ananya.p@alumni.edu` | `Alumni@123` | 5 | `active` |
| **Alumni (Normal)** | `dkim` | `d.kim@alumni.edu` | `Alumni@123` | 6 | `active` |
| **Alumni (Normal)** | `priyas` | `priya.s@alumni.edu` | `Alumni@123` | 7 | `active` |

---

## 3. Browser Developer Tools Configuration

Manual verification requires observing client-server interactions, HTTP status codes, and browser storage states.

### Step 3.1: Developer Tools Setup
1. Launch your browser (e.g., Google Chrome or Microsoft Edge).
2. Open DevTools (`F12` or `Ctrl + Shift + I`).
3. Dock DevTools to the right side or bottom of the screen.

### Step 3.2: Network Tab Inspection Settings
1. Navigate to the **Network** tab in DevTools.
2. Check the box **Disable cache** (prevents stale CSS, JS, and HTML during testing).
3. Check the box **Preserve log** (retains network requests across HTTP redirects and page reloads).
4. Select the **Fetch/XHR** filter pill to observe API requests (`job.php`, `event.php`).
5. Key indicators to check during every action:
   * **Status Code:** `200 OK` (success), `302 Found` (redirect), `401 Unauthorized` (login required), `403 Forbidden` (permission denied), `400 Bad Request` (validation error), `500 Internal Server Error` (fatal PHP/SQL error).
   * **Response Payload:** Verify responses have `Content-Type: application/json` and contain `{ "success": true }` or clear error strings.

### Step 3.3: Console Tab Monitoring
1. Keep the **Console** tab visible.
2. Confirm zero uncaught exceptions:
   * `Uncaught TypeError`
   * `Uncaught SyntaxError`
   * `404 Not Found` for static assets (images, CSS, JS, favicons).
3. Observe console warnings regarding fallback activation on static hosts.

### Step 3.4: Application / Storage Inspection
1. Navigate to the **Application** (Chrome/Edge) or **Storage** (Firefox) tab.
2. Inspect the following client-side storage keys:
   * **Cookies (`http://localhost`)**:
     * `PHPSESSID`: Valid session identifier. Attributes must display `HttpOnly: true`, `SameSite: Lax`.
   * **Local Storage (`http://localhost` or `github.io`)**:
     * `alumni_theme_preference`: Stores user theme choice (`dark`, `light`, `system`).
   * **Session Storage (`github.io` only)**:
     * `alumni_logged_in`: Stores simulated login flag (`true`).

---

## 4. Monitoring PHP & Web Server Error Logs

To diagnose silent backend failures, monitor the XAMPP error logs in real-time.

### Log File Locations:
* **Apache Error Log:** `C:\xampp\apache\logs\error.log`
* **Apache Access Log:** `C:\xampp\apache\logs\access.log`
* **PHP Error Log:** `C:\xampp\php\logs\php_error_log` (or within Apache error log depending on `php.ini` configuration)

### Real-Time Log Tailing via PowerShell:
```powershell
# In a dedicated PowerShell window:
Get-Content -Path "C:\xampp\apache\logs\error.log" -Wait -Tail 30
```

---

## 5. Direct Database Verification Commands

Before and after executing manual tests, verify database state transitions directly using the MySQL CLI or phpMyAdmin (`http://localhost/phpmyadmin`).

### Essential SQL Verification Queries:

#### 1. Check User Sessions and Password Hash
```sql
USE alumni_network;
SELECT id, username, email, role, status FROM users;
```

#### 2. Verify Job Postings & Moderation Status
```sql
SELECT id, title, company, job_type, posted_by, status, posted_at 
FROM jobs 
ORDER BY id DESC LIMIT 10;
```

#### 3. Verify Job Applications
```sql
SELECT ja.id, ja.job_id, j.title, ja.user_id, u.username, ja.status, ja.applied_at 
FROM job_applications ja
JOIN jobs j ON ja.job_id = j.id
JOIN users u ON ja.user_id = u.id
ORDER BY ja.id DESC;
```

#### 4. Verify Events & Moderation Status
```sql
SELECT id, title, event_date, organizer_id, status, capacity 
FROM events 
ORDER BY id DESC LIMIT 10;
```

#### 5. Verify Event Registrations (RSVP)
```sql
SELECT er.id, er.event_id, e.title, er.user_id, u.username, er.status, er.registered_at 
FROM event_registrations er
JOIN events e ON er.event_id = e.id
JOIN users u ON er.user_id = u.id
ORDER BY er.id DESC;
```

---

## 6. Accessing Local vs Static Test Endpoints

| Resource | Local Dynamic URL (PHP/MySQL) | Static Live URL (GitHub Pages) |
| :--- | :--- | :--- |
| **Home Portal** | `http://localhost/alumni-network/index.php` | `https://theekshananimsara.github.io/alumni-management-system/index.html` |
| **Jobs Board** | `http://localhost/alumni-network/jobs.php` (or `jobs.html`) | `https://theekshananimsara.github.io/alumni-management-system/jobs.html` |
| **Job Details** | `http://localhost/alumni-network/job-details.php?id=1` | `https://theekshananimsara.github.io/alumni-management-system/job-details.html?id=1` |
| **Events Portal** | `http://localhost/alumni-network/events.php` (or `event.html`) | `https://theekshananimsara.github.io/alumni-management-system/event.html` |
| **Post Job Form** | `http://localhost/alumni-network/jobcreate.html` | `https://theekshananimsara.github.io/alumni-management-system/jobcreate.html` |
| **Create Event** | `http://localhost/alumni-network/eventcreate.html` | `https://theekshananimsara.github.io/alumni-management-system/eventcreate.html` |
| **Login** | `http://localhost/alumni-network/auth/login.php` | `https://theekshananimsara.github.io/alumni-management-system/login.html` |
| **Register** | `http://localhost/alumni-network/auth/register.php` | `https://theekshananimsara.github.io/alumni-management-system/register.html` |
| **Admin Jobs** | `http://localhost/alumni-network/admin/jobs.php` | `https://theekshananimsara.github.io/alumni-management-system/jobadmin.html` |
| **Admin Events** | `http://localhost/alumni-network/admin/events.php` | `https://theekshananimsara.github.io/alumni-management-system/eventadmin.html` |
