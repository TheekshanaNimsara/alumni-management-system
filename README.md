# General Sir John Kotelawala Defence University (KDU) Alumni Network

[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-blue.svg)](https://www.php.net/)
[![Database](https://img.shields.io/badge/MySQL-8.0%2B%20%2F%20MariaDB-orange.svg)](https://www.mysql.com/)
[![Web Server](https://img.shields.io/badge/Server-Apache%20(XAMPP)-red.svg)](https://www.apachefriends.org/)
[![Architecture](https://img.shields.io/badge/Architecture-Native%20MPA%20%2F%20PDO-green.svg)]()
[![License](https://img.shields.io/badge/License-Academic%20Project-lightgrey.svg)]()

The **KDU Alumni Network** is a dedicated institutional web platform designed for the graduates, current students, faculty members, and administrative staff of **General Sir John Kotelawala Defence University**. The platform provides a secure digital ecosystem for alumni engagement, professional networking, knowledge exchange, mentorship, event participation, and career opportunity dissemination.

---

## Key Features

* **Authentication & Identity:** BCRYPT password hashing, session hardening (`HttpOnly`, `SameSite=Lax`), session fixation defense (`session_regenerate_id`), role-based routing (`alumni`, `admin`).
* **Alumni Profiles & Media Uploads:** Biographical portfolios, academic credentials, skills tags, LinkedIn links, privacy visibility toggles, and secure avatar uploads protected by `.htaccess` execution denial.
* **Alumni Discovery & Directory:** Search by keywords, company, or skills with multi-attribute filtering (cohort year, degree programme, department) and server-side pagination (6 profiles/page).
* **Campus & Alumni Events:** Gathering listings with interactive RSVP toggles (Attending / Cancel), seat capacity enforcement, and member event proposals.
* **Career Opportunities Board:** Vacancy indexing by job type (Full-Time, Internship, Remote), internal cover-letter applications, duplicate application guards, and member job submissions.
* **1-to-1 Private Messaging:** Direct peer-to-peer conversations, auto-initializing threads, unread notification badges in the navigation bar, and chronological message transcripts with autoscroll.
* **Trust, Safety & Moderation:** Member misconduct reporting across profiles, events, jobs, and messages directly to university administrators.
* **Central Administrative Console:** Analytical metrics, user lifecycle controls (activate, suspend, change role, delete), and moderation queues for events, jobs, and reports.

---

## Technology Stack

The platform intentionally uses **pure native web technologies** without external runtime frameworks:

* **Backend:** PHP 8+ (Procedural Page Controllers & Modular Components)
* **Database Abstraction:** PHP Data Objects (PDO) with strict parameterized prepared statements
* **Database Engine:** MySQL 8.0+ / MariaDB 10.4+ (InnoDB, utf8mb4 encoding, foreign key constraints)
* **Frontend:** Semantic HTML5, Vanilla JavaScript (ES6+), Responsive CSS3
* **Visual Palette:** Custom **KDU Cinematic Maroon & Gold** palette (`#24070A`, `#120305`, `#4A1116`, `#D4AF37`, `#F1D77A`, `#F3EFE6`, `#FFFFFF`)
* **Framework Policy:** **Zero external dependencies** (no React, Vue, Angular, Laravel, CodeIgniter, Bootstrap, Tailwind, jQuery, Node.js, Express, etc.).

---

## Repository Structure Summary

```text
alumni-network/
├── index.php                 # Public landing page & featured showcase
├── about.php                 # University mission & leadership background
├── directory.php             # Alumni discovery & paginated directory
├── events.php                # Campus events & dynamic RSVP hub
├── jobs.php                  # Career vacancies & internal applications
├── profile.php               # Member profile view, editor & photo upload
├── messages.php              # 1-to-1 private messaging center
├── actions/                  # Non-rendering background POST controllers (reports)
├── admin/                    # Administrative management suite & moderation
├── assets/                   # CSS stylesheets, SVG graphics, Vanilla JS
├── auth/                     # Login, register, and logout controllers
├── config/                   # Session security, PDO singleton, database setup
├── database/                 # Relational schema.sql with seed records
├── docs/                     # Comprehensive technical documentation package
└── uploads/                  # Uploaded avatars protected by .htaccess execution denial
```

---

## Getting Started & Local Installation

### Prerequisites
* **XAMPP** (or equivalent WAMP/LAMP stack) with **PHP 8.0+** and **MySQL 8.0+ / MariaDB 10.4+**.
* Apache web server with `mod_rewrite` and `AllowOverride` enabled.

### Installation Steps
1. **Clone or Copy Repository:**
   Place the project files into your local web root:
   ```text
   c:\xampp\htdocs\alumni-network
   ```
2. **Start Services:**
   Open the XAMPP Control Panel and start **Apache** and **MySQL**.
3. **Initialize the Database:**
   * **Method 1 (Browser Tool):** Navigate to `http://localhost/alumni-network/config/setup.php` and click **"Initialize / Reset Database Now"**.
   * **Method 2 (phpMyAdmin):** Open `http://localhost/phpmyadmin`, create database `alumni_network`, and import `database/schema.sql`.
4. **Access the Website:**
   Open your browser and navigate to:
   ```text
   http://localhost/alumni-network/
   ```

---

## Default Demo Accounts

For local evaluation and demonstration, the database seed script provides pre-configured accounts:

| Role | Username / Email | Password | Primary Purpose |
| :--- | :--- | :--- | :--- |
| **System Administrator** | `admin@alumni.edu` | `Admin@123` | Platform governance, user management, moderation |
| **Verified Alumnus (John Doe)** | `john.doe@alumni.edu` | `Alumni@123` | Profile management, RSVP, job applications, messaging |
| **Verified Alumna (Sarah Jenkins)** | `sarah.jenkins@alumni.edu` | `Alumni@123` | Direct peer-to-peer messaging testing |

---

## Security Architecture

* **BCRYPT Hashing:** Passwords encrypted using `password_hash($pwd, PASSWORD_BCRYPT)` and checked via constant-time `password_verify()`.
* **SQL Injection Defense:** 100% of queries use PDO prepared statements with parameter binding; emulated prepares are disabled.
* **Cross-Site Scripting (XSS):** Dynamic output wrapped in `htmlspecialchars($val, ENT_QUOTES, 'UTF-8')`.
* **Session Security:** Cookies configured with `HttpOnly`, `SameSite=Lax`, strict mode enabled; session ID regenerated upon login via `session_regenerate_id(true)`.
* **Upload Defense-in-Depth:** Uploaded images validated by MIME type via `getimagesize()`, capped at 2MB, renamed with random hashes, and protected by `.htaccess` rules disabling the PHP engine (`php_flag engine off`).
* **Root Administrator Guard:** System safeguards prevent modification or deletion of User ID 1 and block admin self-lockout.

---

## Four-Phase Development Roadmap

The platform engineering lifecycle is organized into four logical phases:

1. **Phase 1 — Foundation, Architecture & Authentication:** Normalized schema, PDO connection, session security, registration, login, logout, shared layout, and master CSS design system. `[Verified Complete]`
2. **Phase 2 — Alumni Profiles & Directory:** Biographical portfolios, avatar uploads, upload folder security, multi-filter directory search, and server-side pagination. `[Verified Complete]`
3. **Phase 3 — Events, RSVP, Job Board & Applications:** Campus events, dynamic RSVP toggles, capacity meters, career vacancies, cover-letter applications, and moderation endpoints. `[Verified Complete]`
4. **Phase 4 — Messaging, Administration, Reporting & Final Verification:** 1-to-1 private messaging, unread notification tracking, admin dashboard, user lifecycle management, misconduct reporting, and end-to-end testing. `[Verified Complete]`

---

## Documentation Package

Comprehensive technical documentation is maintained in the [`docs/`](./docs/README.md) directory:

* [`docs/README.md`](./docs/README.md) — Documentation index and reading order guide.
* [`docs/01-architectural-system-overview.md`](./docs/01-architectural-system-overview.md) — System architecture, request lifecycle, Mermaid diagrams, ER schema, security, and deployment.
* [`docs/02-directory-and-folder-taxonomy.md`](./docs/02-directory-and-folder-taxonomy.md) — Directory tree, folder responsibilities, layer separation, and Mermaid dependency map.
* [`docs/03-complete-file-by-file-technical-description.md`](./docs/03-complete-file-by-file-technical-description.md) — Rigorous 16-point technical specification covering every source file individually.
* [`docs/04-four-phase-project-roadmap.md`](./docs/04-four-phase-project-roadmap.md) — Four-phase project roadmap, deliverables, checklists, and implementation task matrix.
* [`docs/05-testing-and-acceptance-guide.md`](./docs/05-testing-and-acceptance-guide.md) — Practical manual testing guide with 29 structured test cases across all modules.

---

## Known Deployment Limitations

* Database connection parameters in `config/db.php` default to local development settings (`root` without password). When deploying to a production host, database credentials must be updated.
* HTTPS should be configured in production so that the secure cookie flag activates automatically.