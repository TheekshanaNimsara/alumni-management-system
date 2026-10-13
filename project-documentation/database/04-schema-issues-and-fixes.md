# 04 — Schema Issues, Confirmed Bugs, and Architectural Fixes

## Executive Findings

During the comprehensive audit of the database schema against the active PHP codebase, several discrepancies were identified, analyzed, and corrected.

---

## 1. Issue 1: Missing `skills` Column in `alumni_profiles` (High Severity)

### Description
Both [`profile.php`](file:///c:/xampp/htdocs/alumni-network/profile.php) and [`directory.php`](file:///c:/xampp/htdocs/alumni-network/directory.php) actively manage, query, and search member skills:
* `profile.php` lines 158 & 161: Prepares `UPDATE alumni_profiles SET ..., skills = ? WHERE user_id = ?`.
* `profile.php` line 191: Selects `p.skills` to render the "Key Skills & Expertise" tag cloud.
* `directory.php` lines 30 & 74: Executes `SELECT ... p.skills ...` and `WHERE ... OR p.skills LIKE :s6`.

However, neither the database table `alumni_profiles` nor the original `database/schema.sql` included the `skills` column.

### Runtime Impact
1. When alumni members edited and submitted their technical skills on the profile page, the update failed with a fatal PDOException:
   `SQLSTATE[42S22]: Column not found: 1054 Unknown column 'skills' in 'field list'`
2. When [`directory.php`](file:///c:/xampp/htdocs/alumni-network/directory.php) attempted to load alumni from the database, the query threw the same exception, was caught by `catch (Exception $e)`, and fell back to hardcoded dummy sample alumni.

### Resolution Implemented
1. Executed zero-downtime, non-destructive migration on live database:
   ```sql
   ALTER TABLE alumni_profiles ADD COLUMN skills TEXT NULL AFTER bio;
   ```
2. Added automatic safety hook in [`config/db.php`](file:///c:/xampp/htdocs/alumni-network/config/db.php):
   ```php
   try { $pdo->exec("ALTER TABLE alumni_profiles ADD COLUMN skills TEXT NULL AFTER bio"); } catch (Exception $e) {}
   ```
3. Updated canonical [`database/schema.sql`](file:///c:/xampp/htdocs/alumni-network/database/schema.sql) and [`schema.sql`](file:///c:/xampp/htdocs/alumni-network/schema.sql) table definitions.
4. Populated representative skills for the two demo profiles (John Doe and Sarah Jenkins).

---

## 2. Issue 2: Inconsistency Between `schema.sql` and `database/schema.sql` (Medium Severity)

### Description
The project had two separate SQL schema scripts:
* `database/schema.sql` (located in subfolder)
* `schema.sql` (located at project root)

The root `schema.sql` was an outdated legacy snapshot:
* In `events`: missing `reg_date`, `start_time`, `end_time`, `capacity`, `is_closed`.
* In `jobs`: missing `contact_email`, `requirements`, `deadline`.

Because of this discrepancy, [`config/db.php`](file:///c:/xampp/htdocs/alumni-network/config/db.php) was running 7 repetitive `ALTER TABLE` statements on every incoming HTTP request:
```php
try { $pdo->exec("ALTER TABLE events ADD COLUMN reg_date DATE DEFAULT NULL AFTER event_date"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE events ADD COLUMN start_time TIME DEFAULT NULL AFTER event_time"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE events ADD COLUMN end_time TIME DEFAULT NULL AFTER start_time"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE events ADD COLUMN capacity INT NOT NULL DEFAULT 100 AFTER location"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE jobs ADD COLUMN contact_email VARCHAR(200) DEFAULT NULL AFTER description"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE jobs ADD COLUMN requirements TEXT DEFAULT NULL AFTER contact_email"); } catch (Exception $e) {}
try { $pdo->exec("ALTER TABLE jobs ADD COLUMN deadline DATE DEFAULT NULL AFTER application_link"); } catch (Exception $e) {}
```

### Resolution Implemented
1. Synchronized root [`schema.sql`](file:///c:/xampp/htdocs/alumni-network/schema.sql) and [`database/schema.sql`](file:///c:/xampp/htdocs/alumni-network/database/schema.sql) with the exact modern schema.
2. Verified that both files include all necessary columns, constraints, and indexes.

---

## 3. Issue 3: Missing Foreign Key from `messages.conversation_id` (Low Severity)

### Description
In the original schema:
* `messages.conversation_id` had an index (`INDEX (conversation_id)`), but lacked a formal `FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE`.

### Runtime Impact
If a conversation thread was deleted, its child messages would remain in the table with orphaned `conversation_id` values, consuming storage and creating invalid state.

### Resolution Implemented
Added explicit cascading foreign key constraint:
```sql
FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE
```
Now, if an administrator or participant deletes a conversation, all corresponding messages are cleanly purged without dangling keys.

---

## 4. Issue 4: Index Optimization on High-Traffic Query Filters (Performance)

### Description
The following columns are frequently evaluated in `WHERE` clauses, `JOIN` conditions, and `ORDER BY` operations across the application:
* `users.role` (admin checks)
* `users.status` (active vs suspended checks)
* `alumni_profiles.graduation_year` (directory filter dropdown)
* `alumni_profiles.department` (department filter dropdown)
* `events.status` and `events.event_date` (event board and dashboard)
* `jobs.status` and `jobs.job_type` (career board and dashboard)
* `reports.status` (moderation queue filtering)

In the initial schema, several of these columns lacked secondary indexes, requiring table scans as record volume expands.

### Resolution Implemented
Added explicit secondary indexes in the schema:
* `INDEX idx_users_role (role)`
* `INDEX idx_users_status (status)`
* `INDEX idx_profiles_grad_year (graduation_year)`
* `INDEX idx_profiles_department (department)`
* `INDEX idx_events_status (status)`
* `INDEX idx_events_date (event_date)`
* `INDEX idx_jobs_status (status)`
* `INDEX idx_jobs_type (job_type)`
* `INDEX idx_reports_status (status)`
