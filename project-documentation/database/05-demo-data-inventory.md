# 05 — Demo Data Inventory: Exactly Two Examples Per Section

## Strategy & Data Protection Principles

To provide a consistent demonstration experience without cluttering UI tables or obscuring genuine user activity, the Alumni Connect platform defines **exactly two representative example records in every relevant demo section**.

### Critical Data Protection Safeguards
1. **Zero Table Truncation**: No production or live tables are truncated (`TRUNCATE TABLE` is strictly prohibited).
2. **Preservation of Genuine User Accounts**: Any user account created through registration or authentic testing is permanently preserved.
3. **No Blanket Deletions**: The database is never forced to contain only two total rows across all tables.
4. **Referential Integrity**: All retained demo records maintain valid, mutually consistent foreign keys.

---

## 1. Inventory of Demo Sections & Retained Records

### A. Users & Alumni Profiles (Section: Members)
* **Administrative Anchor**: User ID 1 (`admin` / `System Administrator` / `admin@alumni.edu`).
* **Retained Demo Alumni 1**:
  - **User ID**: `2` (`johndoe` / `John Doe` / `john.doe@alumni.edu`)
  - **Profile ID**: `1`
  - **Role**: Software Engineer at Google, Mountain View, CA
  - **Degree**: B.Sc. in Computer Engineering (Class of 2022)
  - **Skills**: `Distributed Cloud Systems, Go, Python, React, Kubernetes, Mentoring`
  - **Why Retained**: Represents a recently graduated alumni working in core systems engineering.
* **Retained Demo Alumni 2**:
  - **User ID**: `3` (`sarahj` / `Sarah Jenkins` / `sarah.j@alumni.edu`)
  - **Profile ID**: `2`
  - **Role**: Lead AI Researcher at Google DeepMind, London, UK
  - **Degree**: B.Sc. in Computer Engineering (Class of 2020)
  - **Skills**: `Generative AI, Large Language Models, Deep Learning, PyTorch, On-Device ML`
  - **Why Retained**: Represents an accomplished senior researcher specializing in frontier artificial intelligence.

---

### B. Events Board (Section: Events)
* **Retained Demo Event 1 (Approved / Live)**:
  - **Event ID**: `1`
  - **Title**: `ALUMNI MEET 2026`
  - **Date**: `2026-10-24` | **Time**: `17:00:00` - `21:00:00`
  - **Location**: `Main Campus Grand Auditorium`
  - **Capacity**: `250`
  - **Organizer**: User ID 1 (System Administrator)
  - **Status**: `'approved'`
  - **Why Retained**: Flagship on-campus annual gathering demonstrating approved event display and public RSVP flow.
* **Retained Demo Event 2 (Pending Moderation)**:
  - **Event ID**: `2`
  - **Title**: `GLOBAL TECH & AI SUMMIT 2026`
  - **Date**: `2026-11-15` | **Time**: `10:00:00` - `16:00:00`
  - **Location**: `Engineering Faculty Complex`
  - **Capacity**: `150`
  - **Organizer**: User ID 2 (John Doe)
  - **Status**: `'pending'`
  - **Why Retained**: Demonstrates administrative event approval/rejection workflows in the moderation queue.

---

### C. Event Registrations (Section: Event RSVPs)
* **Retained Demo RSVP 1**:
  - **Registration ID**: `1`
  - **Event ID**: `1` (`ALUMNI MEET 2026`)
  - **User ID**: `2` (`johndoe`)
  - **Status**: `'attending'`
* **Retained Demo RSVP 2**:
  - **Registration ID**: `2`
  - **Event ID**: `1` (`ALUMNI MEET 2026`)
  - **User ID**: `3` (`sarahj`)
  - **Status**: `'attending'`
* **Why Retained**: Demonstrates multi-member attendance tracking and capacity decrementing without exceeding limits.

---

### D. Career Opportunities Board (Section: Jobs)
* **Retained Demo Job 1 (Approved / Live)**:
  - **Job ID**: `1`
  - **Title**: `Software Engineer`
  - **Company**: `ABC Technologies`
  - **Location**: `Colombo, Sri Lanka`
  - **Type**: `'Full-Time'`
  - **Contact**: `careers@abctechnologies.com`
  - **Deadline**: `2026-12-31`
  - **Posted By**: User ID 2 (John Doe)
  - **Status**: `'approved'`
  - **Why Retained**: Demonstrates approved software job with external link and contact details.
* **Retained Demo Job 2 (Pending Moderation)**:
  - **Job ID**: `2`
  - **Title**: `Cloud Solutions Architect`
  - **Company**: `Virtusa`
  - **Location**: `Colombo / Hybrid`
  - **Type**: `'Full-Time'`
  - **Contact**: `talent@virtusa.com`
  - **Deadline**: `2026-11-30`
  - **Posted By**: User ID 3 (Sarah Jenkins)
  - **Status**: `'pending'`
  - **Why Retained**: Demonstrates admin job approval pipeline and pending badge rendering.

---

### E. Job Applications (Section: Candidate Submissions)
* **Retained Demo Application 1**:
  - **Application ID**: `1`
  - **Job ID**: `1` (`Software Engineer`)
  - **User ID**: `3` (`sarahj`)
  - **Message**: `'Excited to apply my software engineering background to this role.'`
  - **Status**: `'Applied'`
* **Retained Demo Application 2**:
  - **Application ID**: `2`
  - **Job ID**: `2` (`Cloud Solutions Architect`)
  - **User ID**: `2` (`johndoe`)
  - **Message**: `'I have over 4 years of cloud architecture experience.'`
  - **Status**: `'Under Review'`
* **Why Retained**: Demonstrates both `Applied` and `Under Review` lifecycle stages in the applicant tracking system.

---

### F. Conversations & Messaging (Section: Messages)
* **Retained Conversation**:
  - **Conversation ID**: `1`
  - **Participants**: User ID 2 (`johndoe`) & User ID 3 (`sarahj`)
* **Retained Demo Message 1**:
  - **Message ID**: `1`
  - **Conversation ID**: `1`
  - **Sender**: User ID 3 (`sarahj`) &rarr; **Receiver**: User ID 2 (`johndoe`)
  - **Content**: `'Hi John! Welcome back to Alumni Connect.'`
  - **Status**: `is_read = 1`, `is_unsent = 0`
* **Retained Demo Message 2**:
  - **Message ID**: `2`
  - **Conversation ID**: `1`
  - **Sender**: User ID 2 (`johndoe`) &rarr; **Receiver**: User ID 3 (`sarahj`)
  - **Content**: `'Hi Sarah! Great to connect. Are you attending the Alumni Meet next month?'`
  - **Status**: `is_read = 1`, `is_unsent = 0`
* **Why Retained**: Demonstrates bi-directional communication dialogue, unsend-readiness, and avatar threading.

---

### G. Moderation & Incident Reports (Section: Reports)
* **Retained Demo Report 1 (User Report / Pending)**:
  - **Report ID**: `1`
  - **Reporter**: User ID 2 (`johndoe`)
  - **Target User**: User ID 3 (`sarahj`)
  - **Reason**: `'Suspicious / Inactive Profile Information'`
  - **Status**: `'pending'`
  - **Why Retained**: Populates the administrative "Pending Reports Queue" for testing account investigation & suspension.
* **Retained Demo Report 2 (Event Report / Reviewed)**:
  - **Report ID**: `2`
  - **Reporter**: User ID 3 (`sarahj`)
  - **Target Event**: Event ID 1 (`ALUMNI MEET 2026`)
  - **Reason**: `'Outdated Venue Location'`
  - **Status**: `'reviewed'`
  - **Why Retained**: Populates the "Under Review" filter tab and tests event content reporting.

---

### H. Dashboard Preview Lists (Section: Admin Dashboard)
* **Preview Events**: Queries `events` with `ORDER BY created_at DESC LIMIT 2`. Displays Event 1 and Event 2.
* **Preview Jobs**: Queries `jobs` with `ORDER BY posted_at DESC LIMIT 2`. Displays Job 1 and Job 2.

---

### I. Hardcoded Fallback Arrays (Section: Offline Mode)
In [`config/db.php`](file:///c:/xampp/htdocs/alumni-network/config/db.php):
* `get_sample_alumni()`: Exactly 2 alumni (John Doe and Sarah Jenkins).
* `get_sample_events()`: Exactly 2 events (Alumni Meet and Global Tech Summit).
* `get_sample_jobs()`: Exactly 2 jobs (Software Engineer and Cloud Architect).
* `get_sample_reports()`: Exactly 2 reports (Report 1 pending, Report 2 reviewed).
