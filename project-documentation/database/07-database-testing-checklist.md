# 07 — Database Testing Checklist & Verification Suite

## Overview

This checklist defines rigorous SQL-level and application-level verification tests covering constraints, entity relationships, authentication, moderation workflows, messaging, and demo-data isolation.

---

## 1. Schema & Constraints Verification Checklist

| Test ID | Test Description | Execution Method | Expected Result | Live Status |
| :---: | :--- | :--- | :--- | :---: |
| **TC-01** | Database auto-initialization | Import `database/schema.sql` into fresh test DB | Creates 9 tables with zero SQL syntax errors | **PASSED** |
| **TC-02** | Unique username constraint | `INSERT INTO users (username, ...)` with existing username | Throws Duplicate entry error (code 1062) | **PASSED** |
| **TC-03** | Unique email constraint | `INSERT INTO users (email, ...)` with existing email | Throws Duplicate entry error (code 1062) | **PASSED** |
| **TC-04** | Duplicate event RSVP prevention | Double `INSERT INTO event_registrations` with same event & user | Throws Duplicate entry error for `unique_registration` | **PASSED** |
| **TC-05** | Duplicate job application prevention | Double `INSERT INTO job_applications` with same job & user | Throws Duplicate entry error for `unique_application` | **PASSED** |
| **TC-06** | Conversation deduplication | Invert order `INSERT INTO conversations (user_one_id, user_two_id)` | Blocked by `unique_participants` key | **PASSED** |
| **TC-07** | Profile cascade on user delete | Delete dummy user with profile | Corresponding row in `alumni_profiles` is deleted | **PASSED** |
| **TC-08** | Event cascade on user delete | Delete dummy organizer user | Organized events and RSVPs deleted cleanly | **PASSED** |
| **TC-09** | Message cascade on conversation delete | Delete dummy conversation thread | All child messages deleted without orphaned rows | **PASSED** |
| **TC-10** | `skills` column support | Query and update `alumni_profiles.skills` | Executes without SQLSTATE 42S22 error | **PASSED** |

---

## 2. Application Feature Verification Checklist

| Test ID | Feature Flow | Verification Query / Action | Expected Result | Live Status |
| :---: | :--- | :--- | :--- | :---: |
| **APP-01** | Member Login | `SELECT ... FROM users WHERE email = ? OR username = ?` | Verifies bcrypt hash, routes to `login-success.php` | **PASSED** |
| **APP-02** | Member Registration | Atomic insert into `users` and `alumni_profiles` | Both records committed, session initialized | **PASSED** |
| **APP-03** | Directory Search & Skills | `SELECT ... p.skills ... FROM alumni_profiles` | Returns deduplicated alumni with skills tags | **PASSED** |
| **APP-04** | Profile Editing & Photo Upload | `UPDATE alumni_profiles SET skills = ?, profile_picture = ?` | Successfully updates both fields in database | **PASSED** |
| **APP-05** | Job Board Listing & Apply | Insert into `job_applications (job_id, user_id)` | Creates candidate record; rejects second submission | **PASSED** |
| **APP-06** | Job Moderation Workflow | `UPDATE jobs SET status = 'approved' WHERE id = ?` | Updates status; appears on public career board | **PASSED** |
| **APP-07** | Event RSVP Toggle | Toggle `status` between `'attending'` and `'cancelled'` | Updates existing row without generating duplicate rows | **PASSED** |
| **APP-08** | Event Moderation Workflow | `UPDATE events SET status = 'approved' WHERE id = ?` | Updates status; appears on public event calendar | **PASSED** |
| **APP-09** | Real-Time Direct Message | Insert into `messages` & update `conversations.updated_at` | Message inserted; thread sorted to top | **PASSED** |
| **APP-10** | Message Unsend (Soft Delete) | `UPDATE messages SET is_unsent = 1, unsent_at = NOW()` | Soft-deletes message with sender ownership check | **PASSED** |
| **APP-11** | Moderation Report Submission | Insert into `reports` referencing target ID | Generates report row in pending queue | **PASSED** |
| **APP-12** | Admin Report Resolution | Dual-update: suspend user + resolve report | Updates `users.status = 'suspended'` & `reports.status = 'resolved'` | **PASSED** |

---

## 3. Demo Data Isolation Verification Checklist

| Test ID | Demo Section | Verification Criteria | Expected Result | Live Status |
| :---: | :--- | :--- | :--- | :---: |
| **DEMO-01** | Alumni Profiles | Section contains exactly 2 demo examples | Retains John Doe (Google) & Sarah Jenkins (DeepMind) | **PASSED** |
| **DEMO-02** | Events Board | Section contains exactly 2 demo examples | Retains 1 approved event (Alumni Meet) & 1 pending | **PASSED** |
| **DEMO-03** | Career Opportunities | Section contains exactly 2 demo examples | Retains 1 approved job (Software Engineer) & 1 pending | **PASSED** |
| **DEMO-04** | Conversations & Messages | Section contains exactly 2 demo examples | Retains 1 thread with 2 messages (Sarah & John) | **PASSED** |
| **DEMO-05** | Moderation Reports | Section contains exactly 2 demo examples | Retains 1 pending user report & 1 reviewed event report | **PASSED** |
| **DEMO-06** | Fallback Arrays | `get_sample_*()` in `config/db.php` | Each fallback function returns exactly 2 records | **PASSED** |
| **DEMO-07** | Home Spotlight | Queries on `index.php` | Spotlight limits output to 2 cards per section | **PASSED** |
| **DEMO-08** | Admin Dashboard | Preview tables on `admin/index.php` | Renders latest 2 events and 2 jobs | **PASSED** |
