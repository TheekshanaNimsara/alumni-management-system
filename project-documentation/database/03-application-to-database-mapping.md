# 03 — Application-to-Database Mapping

## Feature-to-Schema Traceability Matrix

This document maps every active website feature in Alumni Connect directly to the underlying MySQL tables, columns, queries, and transactions.

---

## 1. Authentication & Security Flow

### A. Member Login
* **Entry Points**: [`auth/login.php`](file:///c:/xampp/htdocs/alumni-network/auth/login.php)
* **Tables Queried**: `users`
* **Columns Referenced**: `id`, `username`, `first_name`, `last_name`, `email`, `password`, `role`, `status`
* **Query Pattern**:
  ```sql
  SELECT id, username, first_name, last_name, email, password, role, status 
  FROM users 
  WHERE email = ? OR username = ? 
  LIMIT 1
  ```
* **Security Checks**: Bcrypt `password_verify()`, check `status !== 'suspended'`, `session_regenerate_id(true)`.

### B. Member Registration
* **Entry Points**: [`auth/register.php`](file:///c:/xampp/htdocs/alumni-network/auth/register.php)
* **Tables Modified**: `users`, `alumni_profiles`
* **Columns Modified**:
  - `users`: `username`, `first_name`, `last_name`, `email`, `password`, `role ('alumni')`, `status ('active')`
  - `alumni_profiles`: `user_id`, `graduation_year`, `degree_programme`, `department`, `profile_picture ('default-avatar.svg')`, `is_public (1)`
* **Transaction Pattern**: Atomic PDO Transaction (`beginTransaction()`, commit on both records, `rollBack()` on any duplicate or constraint failure).

---

## 2. Profile Management & Photo Upload

### A. View Profile & Skills Cloud
* **Entry Points**: [`profile.php`](file:///c:/xampp/htdocs/alumni-network/profile.php)
* **Tables Queried**: `users`, `alumni_profiles`
* **Columns Queried**:
  - `users`: `id`, `username`, `first_name`, `last_name`, `email`, `role`, `status`, `created_at`
  - `alumni_profiles`: `graduation_year`, `degree_programme`, `department`, `current_job_title`, `current_company`, `location`, `phone`, `linkedin_url`, `bio`, `skills`, `profile_picture`
* **Query Pattern**:
  ```sql
  SELECT u.*, p.* 
  FROM users u
  LEFT JOIN alumni_profiles p ON p.user_id = u.id
  WHERE u.id = ?
  ```

### B. Edit Profile & Technical Skills
* **Entry Points**: [`profile.php`](file:///c:/xampp/htdocs/alumni-network/profile.php) (Form POST `update_profile`)
* **Tables Modified**: `users`, `alumni_profiles`
* **Columns Modified**:
  - `users`: `first_name`, `last_name`, `email`
  - `alumni_profiles`: `graduation_year`, `degree_programme`, `department`, `current_job_title`, `current_company`, `location`, `phone`, `linkedin_url`, `bio`, `skills`
* **Query Pattern**:
  ```sql
  UPDATE alumni_profiles 
  SET graduation_year = ?, degree_programme = ?, department = ?, 
      current_job_title = ?, current_company = ?, location = ?, 
      phone = ?, linkedin_url = ?, bio = ?, skills = ?
  WHERE user_id = ?
  ```

### C. Profile Photo Upload
* **Entry Points**: [`profile.php`](file:///c:/xampp/htdocs/alumni-network/profile.php) (Form POST `upload_photo`)
* **Tables Modified**: `alumni_profiles`
* **Columns Modified**: `profile_picture`
* **Query Pattern**:
  ```sql
  UPDATE alumni_profiles SET profile_picture = ? WHERE user_id = ?
  ```
* **Storage Location**: `uploads/avatars/avatar_{user_id}_{timestamp}.{ext}`.

---

## 3. Alumni Directory & Search

* **Entry Points**: [`directory.php`](file:///c:/xampp/htdocs/alumni-network/directory.php)
* **Tables Queried**: `users`, `alumni_profiles`
* **Columns Queried**: `u.id`, `u.username`, `u.first_name`, `u.last_name`, `u.email`, `p.graduation_year`, `p.degree_programme`, `p.department`, `p.current_job_title`, `p.current_company`, `p.location`, `p.linkedin_url`, `p.bio`, `p.skills`, `p.profile_picture`
* **Deduplication Pattern**:
  ```sql
  SELECT u.id, u.username, u.first_name, u.last_name, u.email,
         p.graduation_year, p.degree_programme, p.department,
         p.current_job_title, p.current_company, p.location,
         p.linkedin_url, p.bio, p.skills, p.profile_picture
  FROM users u
  JOIN (
      SELECT ap.* FROM alumni_profiles ap
      INNER JOIN (SELECT MAX(id) as max_id FROM alumni_profiles GROUP BY user_id) latest 
      ON ap.id = latest.max_id
  ) p ON u.id = p.user_id
  WHERE p.is_public = 1 AND u.status = 'active'
    AND (u.first_name LIKE :s1 OR u.last_name LIKE :s2 OR p.current_company LIKE :s4 OR p.skills LIKE :s6)
  ORDER BY p.graduation_year DESC, u.first_name ASC
  LIMIT :limit OFFSET :offset
  ```

---

## 4. Career Opportunities & Applications

### A. Job Listing & Filtering
* **Entry Points**: [`jobs.php`](file:///c:/xampp/htdocs/alumni-network/jobs.php), [`job.php`](file:///c:/xampp/htdocs/alumni-network/job.php)
* **Tables Queried**: `jobs`, `users`, `job_applications`
* **Columns Queried**: `j.*`, `CONCAT(u.first_name, ' ', u.last_name) AS poster_name`, `ja.status AS application_status`
* **Filter Conditions**: `j.status = 'approved'` (public board) or `j.posted_by = :uid` (my submissions tab).

### B. Job Creation & Moderation Submission
* **Entry Points**: [`job.php?action=create`](file:///c:/xampp/htdocs/alumni-network/job.php), [`jobs.php`](file:///c:/xampp/htdocs/alumni-network/jobs.php)
* **Tables Modified**: `jobs`
* **Columns Modified**: `title`, `company`, `location`, `job_type`, `description`, `requirements`, `contact_email`, `deadline`, `application_link`, `posted_by`, `status` (`'approved'` for admin, `'pending'` for alumni).

### C. Internal Job Application
* **Entry Points**: [`job.php?action=apply`](file:///c:/xampp/htdocs/alumni-network/job.php), [`jobs.php`](file:///c:/xampp/htdocs/alumni-network/jobs.php)
* **Tables Modified**: `job_applications`
* **Columns Modified**: `job_id`, `user_id`, `message`, `status ('Applied')`, `applied_at`
* **Deduplication**: Enforced via `UNIQUE KEY (job_id, user_id)`.

---

## 5. Campus & Alumni Events

### A. Event Board & Details
* **Entry Points**: [`events.php`](file:///c:/xampp/htdocs/alumni-network/events.php), [`event.php`](file:///c:/xampp/htdocs/alumni-network/event.php)
* **Tables Queried**: `events`, `users`, `event_registrations`
* **Columns Queried**: `e.*`, `CONCAT(u.first_name, ' ', u.last_name) AS organizer_name`, attendee count subquery, attendance flag subquery.

### B. Interactive RSVP Management
* **Entry Points**: [`events.php`](file:///c:/xampp/htdocs/alumni-network/events.php) (`toggle_rsvp`), [`event.php?action=register`](file:///c:/xampp/htdocs/alumni-network/event.php)
* **Tables Modified**: `event_registrations`
* **Columns Modified**: `event_id`, `user_id`, `status ('attending' | 'cancelled')`, `registered_at`
* **Capacity Safeguard**:
  ```sql
  SELECT e.*, (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id AND status = 'attending') AS current_attendees
  FROM events e WHERE e.id = ?
  ```
  If `current_attendees >= e.capacity`, the API rejects further RSVPs.

---

## 6. Real-Time 1-to-1 Messaging & Message Unsend

### A. Conversation Discovery & Setup
* **Entry Points**: [`messages.php`](file:///c:/xampp/htdocs/alumni-network/messages.php), [`send_message.php`](file:///c:/xampp/htdocs/alumni-network/send_message.php)
* **Tables Modified**: `conversations`
* **Columns Modified**: `user_one_id`, `user_two_id`, `created_at`, `updated_at`
* **Deduplication**: Canonical `min(id1, id2)` and `max(id1, id2)` ordering with `ON DUPLICATE KEY UPDATE`.

### B. Send Direct Message
* **Entry Points**: [`messages.php`](file:///c:/xampp/htdocs/alumni-network/messages.php) (`send_message`), [`send_message.php`](file:///c:/xampp/htdocs/alumni-network/send_message.php)
* **Tables Modified**: `messages`, `conversations`
* **Columns Modified**: `conversation_id`, `sender_id`, `receiver_id`, `message`, `is_read (0)`, `is_unsent (0)`, `sent_at`
* **Side-Effect**: Updates `conversations.updated_at = NOW()` to move active chats to top of sidebar.

### C. Message Unsend (Soft Deletion)
* **Entry Points**: [`messages.php`](file:///c:/xampp/htdocs/alumni-network/messages.php) (`unsend_message`)
* **Tables Modified**: `messages`
* **Columns Modified**: `is_unsent (1)`, `unsent_at (NOW())`
* **Ownership Verification**: Strictly checks `sender_id === current_user_id()`. Messages sent by other users cannot be unsent.

---

## 7. Moderation & Administrative Dashboard

### A. Dashboard Metrics
* **Entry Points**: [`admin/index.php`](file:///c:/xampp/htdocs/alumni-network/admin/index.php)
* **Tables Queried**: `users`, `events`, `jobs`, `reports`
* **Aggregate Queries**:
  - `SELECT COUNT(*) FROM users` (Total Users)
  - `SELECT COUNT(*) FROM users WHERE status = 'active'` (Active Users)
  - `SELECT COUNT(*) FROM users WHERE status = 'suspended'` (Blocked / Suspended)
  - `SELECT COUNT(*) FROM events WHERE status = 'pending'` (Pending Events)
  - `SELECT COUNT(*) FROM events WHERE status = 'approved'` (Approved Events)
  - `SELECT COUNT(*) FROM jobs WHERE status = 'pending'` (Pending Jobs)
  - `SELECT COUNT(*) FROM jobs WHERE status = 'approved'` (Approved Jobs)
  - `SELECT COUNT(*) FROM reports WHERE status = 'pending'` (Reports Queue)

### B. Report Resolution & User Suspension
* **Entry Points**: [`admin/reports.php`](file:///c:/xampp/htdocs/alumni-network/admin/reports.php)
* **Tables Modified**: `reports`, `users`
* **Actions**:
  - `set_status`: Updates `reports.status = ?` (`reviewed`, `resolved`, `dismissed`).
  - `suspend_user`: Atomic dual-update updating `users.status = 'suspended'` and `reports.status = 'resolved'`.
