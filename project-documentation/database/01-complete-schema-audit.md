# 01 — Complete SQL Schema Audit

## Executive Overview

* **Database Engine**: MySQL 8.0+ / MariaDB 10.4+ (InnoDB Storage Engine)
* **Default Database Name**: `alumni_network`
* **Default Character Set**: `utf8mb4`
* **Collation**: `utf8mb4_unicode_ci` (Full Unicode 4-byte support for emojis, special names, and international characters)
* **Total Tables**: 9
* **Entity Relationships**: 1-to-1, 1-to-Many, Many-to-Many (RSVP & Applications)

This audit documents every table, column, data type, key, constraint, and purpose within the Alumni Connect platform.

---

## 1. Table: `users`

### Purpose
Central authentication and identity table storing system administrators and alumni members. Handles credentials, role-based authorization, and account moderation status.

### Column Specifications
| Column Name | Data Type | Nullable | Default | Keys & Attributes | Purpose |
| :--- | :--- | :---: | :---: | :--- | :--- |
| `id` | `INT(11)` | NO | *Auto-increment* | `PRIMARY KEY` | Unique surrogate identifier for each account |
| `username` | `VARCHAR(50)` | NO | None | `UNIQUE`, `INDEX` | Unique handle for login and directory URLs (3-30 chars) |
| `first_name` | `VARCHAR(50)` | NO | None | None | User's legal/display first name |
| `last_name` | `VARCHAR(50)` | NO | None | None | User's legal/display surname |
| `email` | `VARCHAR(100)` | NO | None | `UNIQUE`, `INDEX` | Unique primary email used for login and notifications |
| `password` | `VARCHAR(255)` | NO | None | None | Bcrypt password hash (`$2y$10$...`) |
| `role` | `ENUM('alumni', 'admin')` | NO | `'alumni'` | `INDEX idx_users_role` | Access level: regular alumnus or system administrator |
| `status` | `ENUM('active', 'suspended')`| NO | `'active'` | `INDEX idx_users_status` | Account state: active or suspended by moderation |
| `created_at` | `TIMESTAMP` | NO | `CURRENT_TIMESTAMP` | None | Timestamp of account registration |

### Indexes & Constraints
* `PRIMARY KEY (id)`
* `UNIQUE KEY username (username)`
* `UNIQUE KEY email (email)`
* `INDEX idx_users_role (role)`
* `INDEX idx_users_status (status)`

---

## 2. Table: `alumni_profiles`

### Purpose
Stores extended biographical, academic, and professional details for alumni members. Has a strict 1-to-1 relationship with the `users` table.

### Column Specifications
| Column Name | Data Type | Nullable | Default | Keys & Attributes | Purpose |
| :--- | :--- | :---: | :---: | :--- | :--- |
| `id` | `INT(11)` | NO | *Auto-increment* | `PRIMARY KEY` | Profile record ID |
| `user_id` | `INT(11)` | NO | None | `FOREIGN KEY`, `INDEX` | Foreign key referencing `users(id)` |
| `graduation_year` | `INT(11)` | NO | None | `INDEX` | Four-digit graduation year (1960–2030) |
| `degree_programme`| `VARCHAR(150)`| NO | None | None | Degree title (e.g. B.Sc. in Computer Engineering) |
| `department` | `VARCHAR(150)`| NO | None | `INDEX` | Academic department or faculty |
| `current_job_title`| `VARCHAR(150)`| YES | `NULL` | None | Professional job position / role |
| `current_company` | `VARCHAR(150)`| YES | `NULL` | None | Employer or organization name |
| `location` | `VARCHAR(150)`| YES | `NULL` | None | City, region, or country of residence |
| `phone` | `VARCHAR(30)` | YES | `NULL` | None | Contact telephone / mobile number |
| `linkedin_url` | `VARCHAR(255)`| YES | `NULL` | None | Public LinkedIn profile URL |
| `bio` | `TEXT` | YES | `NULL` | None | Member biography / introductory overview |
| `skills` | `TEXT` | YES | `NULL` | None | Comma-separated list of technical/industry skills |
| `profile_picture` | `VARCHAR(255)`| YES | `'default-avatar.svg'` | None | Filename or relative path of avatar image |
| `is_public` | `TINYINT(1)` | NO | `1` | None | Privacy toggle: 1 = visible in directory, 0 = hidden |
| `updated_at` | `TIMESTAMP` | NO | `CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP` | None | Last modification timestamp |

### Indexes & Constraints
* `PRIMARY KEY (id)`
* `INDEX idx_profiles_user (user_id)`
* `INDEX idx_profiles_grad_year (graduation_year)`
* `INDEX idx_profiles_department (department)`
* `FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE`

---

## 3. Table: `events`

### Purpose
Manages campus and alumni events, reunions, workshops, and summits. Supports capacity tracking, RSVP deadlines, and administrator moderation approval.

### Column Specifications
| Column Name | Data Type | Nullable | Default | Keys & Attributes | Purpose |
| :--- | :--- | :---: | :---: | :--- | :--- |
| `id` | `INT(11)` | NO | *Auto-increment* | `PRIMARY KEY` | Unique event ID |
| `title` | `VARCHAR(200)`| NO | None | None | Official title of the event |
| `description` | `TEXT` | NO | None | None | Comprehensive event schedule and details |
| `event_date` | `DATE` | NO | None | `INDEX` | Date when the event occurs |
| `reg_date` | `DATE` | YES | `NULL` | None | RSVP deadline date (must be &le; `event_date`) |
| `event_time` | `TIME` | NO | None | None | Legacy / display starting time |
| `start_time` | `TIME` | YES | `NULL` | None | Event beginning time (e.g. 09:00:00) |
| `end_time` | `TIME` | YES | `NULL` | None | Event conclusion time (e.g. 17:00:00) |
| `location` | `VARCHAR(200)`| NO | None | None | Physical venue address or Virtual stream link |
| `capacity` | `INT(11)` | NO | `100` | None | Maximum allowable attendees |
| `organizer_id` | `INT(11)` | NO | None | `FOREIGN KEY`, `INDEX` | User ID of the event host / organizer |
| `status` | `ENUM('pending', 'approved', 'rejected')` | NO | `'approved'` | `INDEX` | Moderation state for public listing |
| `is_closed` | `TINYINT(1)` | NO | `0` | None | Manual lock toggle: 1 = registration closed |
| `image_url` | `VARCHAR(255)`| YES | `NULL` | None | Event banner or flyer image asset path |
| `created_at` | `TIMESTAMP` | NO | `CURRENT_TIMESTAMP` | None | Creation timestamp |

### Indexes & Constraints
* `PRIMARY KEY (id)`
* `INDEX idx_events_organizer (organizer_id)`
* `INDEX idx_events_status (status)`
* `INDEX idx_events_date (event_date)`
* `FOREIGN KEY (organizer_id) REFERENCES users(id) ON DELETE CASCADE`

---

## 4. Table: `event_registrations`

### Purpose
Represents many-to-many relationship between members and events. Tracks member RSVPs, attendance status, and cancellation history.

### Column Specifications
| Column Name | Data Type | Nullable | Default | Keys & Attributes | Purpose |
| :--- | :--- | :---: | :---: | :--- | :--- |
| `id` | `INT(11)` | NO | *Auto-increment* | `PRIMARY KEY` | Registration entry ID |
| `event_id` | `INT(11)` | NO | None | `FOREIGN KEY`, `INDEX` | Reference to target `events(id)` |
| `user_id` | `INT(11)` | NO | None | `FOREIGN KEY`, `INDEX` | Reference to attending `users(id)` |
| `status` | `ENUM('attending', 'cancelled')` | NO | `'attending'` | None | Current RSVP state |
| `registered_at` | `TIMESTAMP` | NO | `CURRENT_TIMESTAMP` | None | Timestamp when RSVP was confirmed or renewed |

### Indexes & Constraints
* `PRIMARY KEY (id)`
* `UNIQUE KEY unique_registration (event_id, user_id)` (Prevents duplicate RSVPs per event)
* `INDEX idx_er_event (event_id)`
* `INDEX idx_er_user (user_id)`
* `FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE`
* `FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE`

---

## 5. Table: `jobs`

### Purpose
Stores career vacancies, internships, and contract postings submitted by alumni or administrators, subject to moderation.

### Column Specifications
| Column Name | Data Type | Nullable | Default | Keys & Attributes | Purpose |
| :--- | :--- | :---: | :---: | :--- | :--- |
| `id` | `INT(11)` | NO | *Auto-increment* | `PRIMARY KEY` | Unique job opportunity ID |
| `title` | `VARCHAR(200)`| NO | None | None | Job title / position name |
| `company` | `VARCHAR(150)`| NO | None | None | Hiring organization / employer |
| `description` | `TEXT` | NO | None | None | Detailed overview of job duties and responsibilities |
| `contact_email` | `VARCHAR(200)`| YES | `NULL` | None | Inquiries or direct application email |
| `requirements` | `TEXT` | YES | `NULL` | None | Technical qualifications, experience, and certifications |
| `location` | `VARCHAR(150)`| NO | None | None | Geographical location or "Remote" |
| `job_type` | `ENUM('Full-Time', 'Part-Time', 'Internship', 'Contract', 'Remote')` | NO | `'Full-Time'` | `INDEX` | Employment structure |
| `application_link`| `VARCHAR(255)`| YES | `NULL` | None | External career portal or ATS URL |
| `deadline` | `DATE` | YES | `NULL` | None | Application cutoff date |
| `posted_by` | `INT(11)` | NO | None | `FOREIGN KEY`, `INDEX` | User ID of the alumnus/admin who posted the job |
| `status` | `ENUM('pending', 'approved', 'rejected')` | NO | `'approved'` | `INDEX` | Moderation state for job board listing |
| `posted_at` | `TIMESTAMP` | NO | `CURRENT_TIMESTAMP` | None | Timestamp when the job was posted |
| `expires_at` | `DATE` | YES | `NULL` | None | Optional automatic expiry date |

### Indexes & Constraints
* `PRIMARY KEY (id)`
* `INDEX idx_jobs_posted_by (posted_by)`
* `INDEX idx_jobs_status (status)`
* `INDEX idx_jobs_type (job_type)`
* `FOREIGN KEY (posted_by) REFERENCES users(id) ON DELETE CASCADE`

---

## 6. Table: `job_applications`

### Purpose
Stores internal candidate submissions for jobs posted directly on the platform. Prevents duplicate submissions and tracks application review status.

### Column Specifications
| Column Name | Data Type | Nullable | Default | Keys & Attributes | Purpose |
| :--- | :--- | :---: | :---: | :--- | :--- |
| `id` | `INT(11)` | NO | *Auto-increment* | `PRIMARY KEY` | Application record ID |
| `job_id` | `INT(11)` | NO | None | `FOREIGN KEY`, `INDEX` | Reference to target `jobs(id)` |
| `user_id` | `INT(11)` | NO | None | `FOREIGN KEY`, `INDEX` | Reference to applicant `users(id)` |
| `message` | `TEXT` | YES | `NULL` | None | Cover letter or applicant pitch note |
| `status` | `ENUM('Applied', 'Under Review', 'Accepted', 'Rejected')` | NO | `'Applied'` | None | Stage in hiring pipeline |
| `applied_at` | `TIMESTAMP` | NO | `CURRENT_TIMESTAMP` | None | Submission timestamp |

### Indexes & Constraints
* `PRIMARY KEY (id)`
* `UNIQUE KEY unique_application (job_id, user_id)` (Prevents duplicate applications to same job)
* `INDEX idx_ja_job (job_id)`
* `INDEX idx_ja_user (user_id)`
* `FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE`
* `FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE`

---

## 7. Table: `conversations`

### Purpose
Defines 1-to-1 direct messaging channels between two alumni members. Enforces canonical participant ordering (`user_one_id < user_two_id`) to prevent duplicate dialogue threads.

### Column Specifications
| Column Name | Data Type | Nullable | Default | Keys & Attributes | Purpose |
| :--- | :--- | :---: | :---: | :--- | :--- |
| `id` | `INT(11)` | NO | *Auto-increment* | `PRIMARY KEY` | Thread identifier |
| `user_one_id` | `INT(11)` | NO | None | `FOREIGN KEY`, `INDEX` | Smaller user ID of the conversation pair |
| `user_two_id` | `INT(11)` | NO | None | `FOREIGN KEY`, `INDEX` | Larger user ID of the conversation pair |
| `created_at` | `TIMESTAMP` | NO | `CURRENT_TIMESTAMP` | None | Initial conversation creation time |
| `updated_at` | `TIMESTAMP` | NO | `CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP` | None | Timestamp of the most recent message |

### Indexes & Constraints
* `PRIMARY KEY (id)`
* `UNIQUE KEY unique_participants (user_one_id, user_two_id)` (Guarantees exactly one thread per user pair)
* `INDEX idx_conv_user_one (user_one_id)`
* `INDEX idx_conv_user_two (user_two_id)`
* `FOREIGN KEY (user_one_id) REFERENCES users(id) ON DELETE CASCADE`
* `FOREIGN KEY (user_two_id) REFERENCES users(id) ON DELETE CASCADE`

---

## 8. Table: `messages`

### Purpose
Stores discrete direct messages exchanged inside conversations. Supports read receipts and soft-deletion ("message unsend") with authorization enforcement.

### Column Specifications
| Column Name | Data Type | Nullable | Default | Keys & Attributes | Purpose |
| :--- | :--- | :---: | :---: | :--- | :--- |
| `id` | `INT(11)` | NO | *Auto-increment* | `PRIMARY KEY` | Message record ID |
| `conversation_id` | `INT(11)` | YES | `NULL` | `FOREIGN KEY`, `INDEX` | Owning conversation thread reference |
| `sender_id` | `INT(11)` | NO | None | `FOREIGN KEY`, `INDEX` | Author user ID |
| `receiver_id` | `INT(11)` | NO | None | `FOREIGN KEY`, `INDEX` | Recipient user ID |
| `message` | `TEXT` | NO | None | None | Message content (max 10,000 characters) |
| `is_read` | `TINYINT(1)` | NO | `0` | None | Read state: 1 = read, 0 = unread |
| `is_unsent` | `TINYINT(1)` | NO | `0` | None | Soft-deletion flag: 1 = unsent by author |
| `unsent_at` | `TIMESTAMP` | YES | `NULL` | None | Timestamp when unsend action was triggered |
| `sent_at` | `TIMESTAMP` | NO | `CURRENT_TIMESTAMP` | None | Delivery timestamp |

### Indexes & Constraints
* `PRIMARY KEY (id)`
* `INDEX idx_messages_conv (conversation_id)`
* `INDEX idx_messages_sender (sender_id)`
* `INDEX idx_messages_receiver (receiver_id)`
* `FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE`
* `FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE`
* `FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE`

---

## 9. Table: `reports`

### Purpose
Manages user-generated moderation reports targeting inappropriate profiles, abusive direct messages, or outdated events/job postings.

### Column Specifications
| Column Name | Data Type | Nullable | Default | Keys & Attributes | Purpose |
| :--- | :--- | :---: | :---: | :--- | :--- |
| `id` | `INT(11)` | NO | *Auto-increment* | `PRIMARY KEY` | Report incident identifier |
| `reporter_id` | `INT(11)` | NO | None | `FOREIGN KEY`, `INDEX` | User ID of reporting member |
| `reported_user_id`| `INT(11)` | YES | `NULL` | `FOREIGN KEY`, `INDEX` | Targeted user ID (if user reported) |
| `event_id` | `INT(11)` | YES | `NULL` | `FOREIGN KEY`, `INDEX` | Targeted event ID (if event reported) |
| `job_id` | `INT(11)` | YES | `NULL` | `FOREIGN KEY`, `INDEX` | Targeted job ID (if job reported) |
| `message_id` | `INT(11)` | YES | `NULL` | `FOREIGN KEY`, `INDEX` | Targeted message ID (if message reported) |
| `reason` | `VARCHAR(150)`| NO | None | None | Category of report |
| `description` | `TEXT` | YES | `NULL` | None | Free-form explanation provided by reporter |
| `status` | `ENUM('pending', 'reviewed', 'resolved', 'dismissed')` | NO | `'pending'` | `INDEX` | Current investigation status |
| `created_at` | `TIMESTAMP` | NO | `CURRENT_TIMESTAMP` | None | Submission timestamp |

### Indexes & Constraints
* `PRIMARY KEY (id)`
* `INDEX idx_reports_reporter (reporter_id)`
* `INDEX idx_reports_status (status)`
* `FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE`
* `FOREIGN KEY (reported_user_id) REFERENCES users(id) ON DELETE CASCADE`
* `FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE`
* `FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE`
* `FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE`
