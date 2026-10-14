# KDU Alumni Network — How to Grant Admin Access to Registered Users

This guide explains how to grant administrative privileges (`role = 'admin'`) to any registered member of the **KDU Alumni Network**.

---

## Overview of Administrator Privileges

When a user's role is set to `admin`, they gain access to the complete **Admin Management Suite** (`/admin/`):
* **User Management (`/admin/users.php`):** View member rosters, activate/suspend accounts, promote/demote administrators, and delete spam profiles.
* **Job Board Moderation (`/admin/jobs.php`):** Review pending job and internship postings, approve them for the public career board, or reject unsuitable submissions.
* **Campus Event Moderation (`/admin/events.php`):** Review pending alumni event proposals, approve gatherings, edit details, and monitor RSVP headcounts.
* **Trust & Safety Dossiers (`/admin/reports.php`):** Investigate member misconduct reports filed against profiles, jobs, events, or messages.
* **Platform Configuration (`/admin/settings.php`):** Manage university branding tokens, system guidelines, and operational parameters.

---

## 3 Ways to Grant Admin Access

Choose whichever method best fits your workflow:

```
┌─────────────────────────────────────────────────────────────────────────────┐
│ 1. Web Admin Panel      → Best for standard day-to-day administrative use   │
│ 2. Command Line Tool    → Best for developers, DevOps, and terminal users   │
│ 3. Direct SQL Query     → Best for database administrators / phpMyAdmin     │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

### Method 1: Via the Web Admin Panel (Recommended)

If you already have access to an administrator account (such as the default `admin@alumni.edu`):

1. **Log In as an Administrator:**
   * Navigate to `http://localhost/alumni-network/auth/login.php`.
   * Log in using your admin credentials (default seed: `admin@alumni.edu` / `Admin@123`).
2. **Open the Admin Console:**
   * Click **Admin Panel** in the top navigation bar, or go directly to:
     ```text
     http://localhost/alumni-network/admin/users.php
     ```
3. **Locate the Registered User:**
   * Use the **Search bar** to search for the user by their name, email address, or username.
   * Or filter by `Role: Alumni` and `Status: Active`.
4. **Click "Make Admin":**
   * In the **Actions** column next to the target user, click the **"Make Admin"** button.
5. **Confirm the Action:**
   * A confirmation dialog will appear: `"Change user role to Admin?"`. Click **OK**.
6. **Confirmation:**
   * The page will refresh with a green banner: `User #X role updated to Admin.`
   * The user's role badge in the table will immediately change from `Alumni` to `Admin`.

---

### Method 2: Via the Command-Line Tool (`bin/grant_admin.php`)

A dedicated CLI management tool is included in the repository at `bin/grant_admin.php`.

#### 1. Promote by Email Address
```bash
php bin/grant_admin.php user@example.com
```

#### 2. Promote by Username
```bash
php bin/grant_admin.php john_doe
```

#### 3. Promote by User ID
```bash
php bin/grant_admin.php 3
```

#### Output Example:
```text
============================================================
 Alumni Network — Administrator Management Tool
============================================================

[SUCCESS] Administrator privileges GRANTED successfully!
------------------------------------------------------------
  User ID:    #3
  Full Name:  Sarah Jenkins
  Username:   @sarahj
  Email:      sarah.j@alumni.edu
  Previous:   alumni
  New Role:   admin
  Status:     active
------------------------------------------------------------
The user can now log in at http://localhost/alumni-network/auth/login.php
and access the Admin Panel at http://localhost/alumni-network/admin/
```

#### Additional CLI Options:
* **List all administrators:**
  ```bash
  php bin/grant_admin.php --list
  ```
* **Demote an administrator back to alumni:**
  ```bash
  php bin/grant_admin.php --demote user@example.com
  ```

---

### Method 3: Via Direct MySQL / phpMyAdmin Query

If you do not have terminal access or an existing admin session, you can execute a direct SQL statement in **phpMyAdmin** or the **MySQL CLI**:

#### 1. Open phpMyAdmin
* Navigate to `http://localhost/phpmyadmin` in your web browser.
* Select the `alumni_network` database from the left sidebar.
* Click on the **SQL** tab.

#### 2. Run the UPDATE Query
Replace `'user@example.com'` with the target user's registered email address:

```sql
UPDATE users 
SET role = 'admin', status = 'active' 
WHERE email = 'user@example.com';
```

*(Alternatively, update by username:)*
```sql
UPDATE users 
SET role = 'admin', status = 'active' 
WHERE username = 'john_doe';
```

*(Or by User ID:)*
```sql
UPDATE users 
SET role = 'admin', status = 'active' 
WHERE id = 3;
```

#### 3. Verify the Update
```sql
SELECT id, first_name, last_name, username, email, role, status 
FROM users 
WHERE email = 'user@example.com';
```

---

## Security & Architectural Safeguards

1. **Root Administrator Protection (ID 1):**
   * User ID 1 (`admin@alumni.edu`) is permanently safeguarded. The system prevents changing the role, suspending, or deleting User ID 1 through both the web UI and the CLI tool.
2. **Admin Self-Demotion / Lockout Guard:**
   * Active administrators cannot demote or suspend their own currently logged-in account in the web panel.
3. **Session Refresh:**
   * User roles are stored in the active PHP session (`$_SESSION['role']`). If a user is actively logged in when promoted, their privileges will update on their next login session or upon re-authentication.
