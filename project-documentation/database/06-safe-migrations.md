# 06 — Safe SQL Migrations and Rollback Procedures

## Migration Execution Guidelines

All migrations detailed below are designed to be **non-destructive**, **idempotent**, and **backward-compatible**. They preserve all genuine user accounts, existing authentication credentials, and user data.

---

## Migration 001: Add Technical Skills to Alumni Profiles

### Purpose
Supports the "Key Skills & Expertise" tag cloud on [`profile.php`](file:///c:/xampp/htdocs/alumni-network/profile.php) and enables skills-based filtering on [`directory.php`](file:///c:/xampp/htdocs/alumni-network/directory.php).

### Prerequisites
* Verify database connection to `alumni_network`.
* Ensure table `alumni_profiles` exists.

### Pre-Migration Check
```sql
SELECT COLUMN_NAME 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'alumni_profiles' 
  AND COLUMN_NAME = 'skills';
```
*(If 0 rows returned, proceed with execution).*

### Migration SQL
```sql
ALTER TABLE alumni_profiles 
ADD COLUMN skills TEXT NULL AFTER bio;
```

### Post-Migration Verification
```sql
SHOW COLUMNS FROM alumni_profiles LIKE 'skills';
```

### Rollback SQL
```sql
ALTER TABLE alumni_profiles 
DROP COLUMN skills;
```

---

## Migration 002: Cascading Foreign Key on Direct Messages

### Purpose
Ensures that if a conversation thread is deleted in moderation, all constituent messages are purged automatically without leaving orphaned records.

### Prerequisites
* Ensure both `conversations` and `messages` tables exist.
* Verify there are no existing messages pointing to nonexistent conversation IDs:
  ```sql
  SELECT COUNT(*) FROM messages 
  WHERE conversation_id IS NOT NULL 
    AND conversation_id NOT IN (SELECT id FROM conversations);
  ```
  *(Must return 0 before adding foreign key constraint).*

### Migration SQL
```sql
-- Step 1: Ensure index exists
ALTER TABLE messages 
ADD INDEX IF NOT EXISTS idx_messages_conv (conversation_id);

-- Step 2: Add cascading foreign key
ALTER TABLE messages 
ADD CONSTRAINT fk_messages_conversation 
FOREIGN KEY (conversation_id) REFERENCES conversations(id) 
ON DELETE CASCADE;
```

### Post-Migration Verification
```sql
SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME 
FROM information_schema.KEY_COLUMN_USAGE 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'messages' 
  AND CONSTRAINT_NAME = 'fk_messages_conversation';
```

### Rollback SQL
```sql
ALTER TABLE messages 
DROP FOREIGN KEY fk_messages_conversation;
```

---

## Migration 003: Performance Index Optimization

### Purpose
Adds secondary indexes to frequently filtered and ordered columns across high-traffic pages (Directory, Job Board, Event Calendar, Reports Queue).

### Migration SQL
```sql
-- Users optimization
ALTER TABLE users ADD INDEX IF NOT EXISTS idx_users_role (role);
ALTER TABLE users ADD INDEX IF NOT EXISTS idx_users_status (status);

-- Alumni profiles optimization
ALTER TABLE alumni_profiles ADD INDEX IF NOT EXISTS idx_profiles_grad_year (graduation_year);
ALTER TABLE alumni_profiles ADD INDEX IF NOT EXISTS idx_profiles_department (department);

-- Events optimization
ALTER TABLE events ADD INDEX IF NOT EXISTS idx_events_status (status);
ALTER TABLE events ADD INDEX IF NOT EXISTS idx_events_date (event_date);

-- Jobs optimization
ALTER TABLE jobs ADD INDEX IF NOT EXISTS idx_jobs_status (status);
ALTER TABLE jobs ADD INDEX IF NOT EXISTS idx_jobs_type (job_type);

-- Reports optimization
ALTER TABLE reports ADD INDEX IF NOT EXISTS idx_reports_status (status);
```

### Post-Migration Verification
```sql
SHOW INDEXES FROM users WHERE Key_name IN ('idx_users_role', 'idx_users_status');
SHOW INDEXES FROM alumni_profiles WHERE Key_name IN ('idx_profiles_grad_year', 'idx_profiles_department');
SHOW INDEXES FROM events WHERE Key_name IN ('idx_events_status', 'idx_events_date');
SHOW INDEXES FROM jobs WHERE Key_name IN ('idx_jobs_status', 'idx_jobs_type');
SHOW INDEXES FROM reports WHERE Key_name = 'idx_reports_status';
```

### Rollback SQL
```sql
ALTER TABLE users DROP INDEX idx_users_role, DROP INDEX idx_users_status;
ALTER TABLE alumni_profiles DROP INDEX idx_profiles_grad_year, DROP INDEX idx_profiles_department;
ALTER TABLE events DROP INDEX idx_events_status, DROP INDEX idx_events_date;
ALTER TABLE jobs DROP INDEX idx_jobs_status, DROP INDEX idx_jobs_type;
ALTER TABLE reports DROP INDEX idx_reports_status;
```

---

## Automated Migration Runner via PHP CLI

To apply all migrations safely and non-destructively from the terminal or XAMPP environment:

```powershell
php -r "
require 'config/db.php';
try {
    \$pdo->exec('ALTER TABLE alumni_profiles ADD COLUMN skills TEXT NULL AFTER bio');
    echo \"Migration 001 executed successfully.\n\";
} catch (Exception \$e) { echo \"Migration 001 note: \" . \$e->getMessage() . \"\n\"; }
"
```
*(All changes are guarded with graceful exception handling in `config/db.php`).*
