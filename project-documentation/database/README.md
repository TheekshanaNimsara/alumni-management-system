# Alumni Connect — Database Architecture & Documentation

## Overview

This directory provides the definitive MySQL/MariaDB database architecture documentation, complete schema audit, relationship mappings, migration guides, and testing checklists for the **Alumni Connect** web application.

---

## Documentation Index

| File | Title | Description |
| :--- | :--- | :--- |
| [**`01-complete-schema-audit.md`**](01-complete-schema-audit.md) | Complete SQL Schema Audit | Exhaustive reference for all 9 tables, columns, data types, keys, and default values. |
| [**`02-relationships-and-foreign-keys.md`**](02-relationships-and-foreign-keys.md) | Entity Relationships & Foreign Keys | Entity relationship diagrams, cascading delete rules, and uniqueness constraints. |
| [**`03-application-to-database-mapping.md`**](03-application-to-database-mapping.md) | Application to Database Mapping | Code-to-database traceability matrix linking login, profiles, jobs, events, and chat to SQL queries. |
| [**`04-schema-issues-and-fixes.md`**](04-schema-issues-and-fixes.md) | Schema Issues & Architectural Fixes | Root cause analysis of discovered discrepancies (e.g. missing `skills` column) and their fixes. |
| [**`05-demo-data-inventory.md`**](05-demo-data-inventory.md) | Demo Data Inventory | Inventory of the exact **Two-Example Demo Dataset** per section and genuine user data safeguards. |
| [**`06-safe-migrations.md`**](06-safe-migrations.md) | Safe Migrations & Rollback Procedures | Production-safe SQL migration scripts, validation queries, and non-destructive rollbacks. |
| [**`07-database-testing-checklist.md`**](07-database-testing-checklist.md) | Testing Checklist & Verification Suite | 30 comprehensive SQL and application-level test cases covering constraints, moderation, and chat. |

---

## Database Architecture Highlights

```
+---------------------------------------------------------------------------------+
|                                 ALUMNI CONNECT                                  |
|                             Database: alumni_network                            |
|                     Engine: InnoDB | Charset: utf8mb4 (Unicode)                 |
+---------------------------------------------------------------------------------+
                                         |
     +-----------------+-----------------+-----------------+-----------------+
     |                 |                 |                 |                 |
     v                 v                 v                 v                 v
[ users ]      [ events ]            [ jobs ]       [ conversations ]   [ reports ]
     |                 |                 |                 |                 |
     | (1-to-1)        | (1-to-many)     | (1-to-many)     | (1-to-many)     | (targets)
     v                 v                 v                 v                 |
[ alumni_profiles ] [ event_reg ]   [ job_apps ]    [ messages ] <-----------+
```

### Key Technical Specifications
* **Database Name**: `alumni_network`
* **Storage Engine**: `InnoDB` (ACID compliance, transactional safety, row-level locking)
* **Character Set & Collation**: `utf8mb4` / `utf8mb4_unicode_ci` (Multilingual & emoji support)
* **Primary Key Strategy**: Auto-incrementing unsigned integer `id` for surrogate stability.
* **Referential Integrity**: Cascading deletes (`ON DELETE CASCADE`) prevent orphaned child records when users, events, jobs, or conversations are removed.
* **Authentication Security**: Passwords stored as standard Bcrypt hashes (`PASSWORD_BCRYPT`, 60+ chars).

---

## Canonical Schema Files

* **Subdirectory Schema**: [`database/schema.sql`](../../database/schema.sql) — Fully updated with the complete schema and two-example demo dataset.
* **Root Schema**: [`schema.sql`](../../schema.sql) — Fully synchronized with `database/schema.sql`.
* **Database Config & Helpers**: [`config/db.php`](../../config/db.php) — Dynamic fallback mode, PDO connection, and automatic migration hooks.

---

## Quick Testing Instructions

### 1. Test Clean Schema Import in MySQL
```powershell
# Create and test on fresh database
mysql -u root -e "CREATE DATABASE IF NOT EXISTS alumni_network_test;"
mysql -u root alumni_network_test < database/schema.sql
mysql -u root -e "SHOW TABLES IN alumni_network_test;"
mysql -u root -e "DROP DATABASE alumni_network_test;"
```

### 2. Verify PHP Database Connectivity
```powershell
php -r "require 'config/db.php'; echo \$db_connected ? 'Connected!' : 'Offline Fallback';"
```
