# 04 — Jobs and Events Module Test Cases

**Project:** University Alumni Network  
**Test Suite:** SUITE-JOB & SUITE-EVT  
**Target Architecture:** Vanilla JS Controllers (`jobs.js`, `event.js`, `job-details.js`, `eventcreate.js`), Native PHP APIs (`job.php`, `event.php`), MySQL Storage  
**Execution Environment:** Local Dynamic Full-Stack (XAMPP) & Static Host (GitHub Pages)  

---

## 1. Jobs Board & Opportunities Test Cases (JOB)

### JOB-001: Public Job Listings Rendering & Type Filter Tabs

| Field | Details |
| :--- | :--- |
| **Test ID** | JOB-001 |
| **Feature** | Job board retrieval, card rendering, and type filtering |
| **Prerequisites** | Website running on `http://localhost/alumni-network/` (or GitHub Pages); database seeded with active jobs |
| **Steps** | 1. Navigate to `http://localhost/alumni-network/jobs.html`.<br>2. Observe initial card load on "All Opportunities" tab.<br>3. Select the "Job Type" dropdown filter and pick "Internship".<br>4. Select "Full-Time".<br>5. Select "All Types". |
| **Expected result** | 1. Job cards render with company logo badge, title, company, location, type badge, description, and requirements.<br>2. Selecting "Internship" displays only internship postings (e.g. "DevOps & Platform Intern").<br>3. Cards update instantaneously without full-page reloads via Vanilla JS DOM manipulation.<br>4. Only postings with `status = 'approved'` are displayed on the public board. |
| **Actual result** | |
| **Status** | Not Tested |
| **Notes** | |

---

### JOB-002: Real-Time Search Filtering by Keyword and Location

| Field | Details |
| :--- | :--- |
| **Test ID** | JOB-002 |
| **Feature** | Live client-side keyword search in jobs board |
| **Prerequisites** | Jobs board loaded with multiple job listings |
| **Steps** | 1. Navigate to `http://localhost/alumni-network/jobs.html`.<br>2. In the "Search opportunities..." input, type `Architect`.<br>3. Clear input, then type `Colombo`.<br>4. Type an unmatched string, e.g. `xyznonexistentjob123`. |
| **Expected result** | 1. Typing `Architect` filters list down to matching jobs (e.g. "Cloud Solutions Architect").<br>2. Typing `Colombo` matches jobs containing Colombo in location.<br>3. Unmatched query hides all cards and displays `#noResults`: `"No job postings found matching your criteria. Check back soon!"`. |
| **Actual result** | |
| **Status** | Not Tested |
| **Notes** | |

---

### JOB-003: Anonymous Visitor Click on "View & Apply" Redirect

| Field | Details |
| :--- | :--- |
| **Test ID** | JOB-003 |
| **Feature** | Unauthenticated action protection on job details/application |
| **Prerequisites** | Browser in guest/incognito mode (no session cookies or sessionStorage) |
| **Steps** | 1. Navigate to `http://localhost/alumni-network/jobs.html`.<br>2. Locate any job card (e.g. Job ID 1).<br>3. Click the "View & Apply" button.<br>4. Inspect the resulting browser URL and rendered page. |
| **Expected result** | 1. Visitor is immediately intercepted and redirected to login page.<br>2. URL contains parameters: `auth/login.php?redirect=job-details.html%3Fid%3D1&msg=auth_required` (or `login.html?...` on static mode).<br>3. Login page displays a prominent warning banner: `"Please log in to continue."`<br>4. After entering valid credentials, user is automatically forwarded to `job-details.html?id=1`. |
| **Actual result** | |
| **Status** | Not Tested |
| **Notes** | |

---

### JOB-004: Anonymous Visitor Click on "+ Post a Job" Redirect

| Field | Details |
| :--- | :--- |
| **Test ID** | JOB-004 |
| **Feature** | Unauthenticated action protection on job posting submission |
| **Prerequisites** | Browser in guest/incognito mode |
| **Steps** | 1. Navigate to `http://localhost/alumni-network/jobs.html`.<br>2. Click the "+ Post a Job" action button in the header.<br>3. Inspect the resulting URL and UI message. |
| **Expected result** | 1. Navigation is intercepted.<br>2. Visitor is redirected to `auth/login.php?redirect=jobcreate.html&msg=auth_required`.<br>3. Login screen presents `"Please log in to continue."` notice.<br>4. Unauthenticated visitor cannot access the blank job submission form directly without logging in. |
| **Actual result** | |
| **Status** | Not Tested |
| **Notes** | |

---

### JOB-005: Authenticated User Views Full Job & Company Details

| Field | Details |
| :--- | :--- |
| **Test ID** | JOB-005 |
| **Feature** | Dedicated job details page rendering |
| **Prerequisites** | Logged in as normal user `johndoe` |
| **Steps** | 1. On `jobs.html`, click "View & Apply" on Job ID 1.<br>2. Inspect `job-details.html?id=1` layout and data fields.<br>3. Check browser console for errors. |
| **Expected result** | 1. Dedicated page loads cleanly with liquid glass styling.<br>2. Heading displays job title and company name.<br>3. Metadata blocks render location, job type tag, application deadline, and contact email.<br>4. Full multi-paragraph job description and bulleted candidate requirements are displayed.<br>5. "Apply for Position" / "Submit Application" action button is visible and active. |
| **Actual result** | |
| **Status** | Not Tested |
| **Notes** | |

---

### JOB-006: Authenticated Job Application Submission

| Field | Details |
| :--- | :--- |
| **Test ID** | JOB-006 |
| **Feature** | Submitting job application to MySQL database |
| **Prerequisites** | Logged in as `johndoe` (User ID 2); targeting Job ID 2 |
| **Steps** | 1. On `job-details.html?id=2` (or via modal on `jobs.html`), click "Apply Now" / "Submit Application".<br>2. Enter a cover note/message: `"I am an experienced alumni engineer interested in this architecture role."`<br>3. Click "Submit Application".<br>4. Inspect Network tab response.<br>5. Query MySQL: `SELECT * FROM job_applications WHERE job_id = 2 AND user_id = 2;` |
| **Expected result** | 1. POST request sent to `job.php?action=apply` with `job_id=2` and application message.<br>2. Server responds with HTTP 200 OK and `{ "success": true, "message": "Application submitted successfully!" }`.<br>3. Modal/page updates to confirm application received.<br>4. Database verifies new record in `job_applications` table with `status = 'Applied'`. |
| **Actual result** | |
| **Status** | Not Tested |
| **Notes** | |

---

### JOB-007: Duplicate Job Application Prevention

| Field | Details |
| :--- | :--- |
| **Test ID** | JOB-007 |
| **Feature** | Duplicate application validation and user feedback |
| **Prerequisites** | User `johndoe` has already applied for Job ID 2 (from JOB-006) |
| **Steps** | 1. Return to `job-details.html?id=2` or reopen application modal.<br>2. Enter message and click "Submit Application" again.<br>3. Observe response and UI notice. |
| **Expected result** | 1. System blocks duplicate application submission.<br>2. Server returns JSON: `{ "success": false, "error": "You have already applied for this position (Status: Applied)." }`.<br>3. UI displays warning banner to user.<br>4. No duplicate row is inserted into `job_applications` table. |
| **Actual result** | |
| **Status** | Not Tested |
| **Notes** | |

---

### JOB-008: Normal User Submits Job Posting (Pending Moderation)

| Field | Details |
| :--- | :--- |
| **Test ID** | JOB-008 |
| **Feature** | Normal user job creation and pending review lifecycle |
| **Prerequisites** | Logged in as normal user `johndoe` |
| **Steps** | 1. Click "+ Post a Job" to open `jobcreate.html`.<br>2. Fill in posting details:<br>&nbsp;&nbsp;&nbsp;- Job Title: `Frontend QA Engineer`<br>&nbsp;&nbsp;&nbsp;- Company: `Alumni Tech Labs`<br>&nbsp;&nbsp;&nbsp;- Location: `Kandy, Sri Lanka`<br>&nbsp;&nbsp;&nbsp;- Job Type: `Full-Time`<br>&nbsp;&nbsp;&nbsp;- Description: `Looking for skilled QA specialist for automated and manual web audits.`<br>&nbsp;&nbsp;&nbsp;- Requirements: `HTML5, CSS3, vanilla JS, manual QA checklists.`<br>&nbsp;&nbsp;&nbsp;- Contact Email: `qa@alumnitechlabs.com`<br>&nbsp;&nbsp;&nbsp;- Deadline: `2026-12-31`<br>3. Click "Submit Opportunity".<br>4. Query database: `SELECT id, title, status, posted_by FROM jobs WHERE title = 'Frontend QA Engineer';`<br>5. View `jobs.html` public tab vs "My Postings" tab. |
| **Expected result** | 1. Form submits to `job.php?action=create`.<br>2. Database record inserted with `posted_by = 2` and `status = 'pending'`.<br>3. User redirected to `jobs.html` with green success banner: `"Opportunity submitted successfully! It has been submitted for administrator review..."`<br>4. On public "All Opportunities" tab, this job is NOT visible to visitors.<br>5. On "My Postings" tab (`#myJobsTab`), the job IS visible and displays orange badge `"Pending Approval"`. |
| **Actual result** | |
| **Status** | Not Tested |
| **Notes** | |

---

### JOB-009: Administrator Approves Pending Job Posting

| Field | Details |
| :--- | :--- |
| **Test ID** | JOB-009 |
| **Feature** | Administrator moderation workflow — approval |
| **Prerequisites** | Pending job exists in database (from JOB-008); logged in as `admin` (`Admin@123`) |
| **Steps** | 1. Log in as `admin` and navigate to `jobadmin.html` (or `admin/jobs.php`).<br>2. Locate "Frontend QA Engineer" in pending jobs table.<br>3. Click the "Approve" button.<br>4. Query database: `SELECT status FROM jobs WHERE title = 'Frontend QA Engineer';`<br>5. Open an Incognito window and visit `jobs.html` as an anonymous visitor. |
| **Expected result** | 1. Network sends POST to `job.php?action=approve` with job ID.<br>2. Server responds with `{ "success": true }`.<br>3. Database updates `jobs.status = 'approved'`.<br>4. Job card immediately appears on the public `jobs.html` board for all visitors. |
| **Actual result** | |
| **Status** | Not Tested |
| **Notes** | |

---

### JOB-010: Administrator Rejects and Deletes Job Posting

| Field | Details |
| :--- | :--- |
| **Test ID** | JOB-010 |
| **Feature** | Administrator moderation workflow — rejection & deletion |
| **Prerequisites** | Logged in as `admin`; test job exists |
| **Steps** | 1. On `jobadmin.html`, locate a job posting.<br>2. Click "Reject".<br>3. Check database status: `SELECT status FROM jobs WHERE id = ...;`<br>4. Click "Delete" and confirm prompt.<br>5. Check database: `SELECT * FROM jobs WHERE id = ...;` |
| **Expected result** | 1. Reject action updates `status = 'rejected'` in database.<br>2. Delete action removes row from `jobs` table.<br>3. Job is no longer present in admin view or public view. |
| **Actual result** | |
| **Status** | Not Tested |
| **Notes** | |

---

## 2. Events Portal Test Cases (EVT)

### EVT-001: Public Events Rendering & Category Filters

| Field | Details |
| :--- | :--- |
| **Test ID** | EVT-001 |
| **Feature** | Public event list retrieval and date filtering |
| **Prerequisites** | Website running on `http://localhost/alumni-network/` (or GitHub Pages); database seeded with events |
| **Steps** | 1. Navigate to `http://localhost/alumni-network/event.html` (or `events.php`).<br>2. Inspect rendered event cards.<br>3. Click "Upcoming Events" filter tab.<br>4. Click "Past Events" filter tab.<br>5. Click "All Events". |
| **Expected result** | 1. Cards render event title, date, time, location, description, attendee count, and max capacity.<br>2. Past events display muted or "Event Concluded" styling.<br>3. Upcoming events display active registration buttons.<br>4. Only approved events appear on public listing. |
| **Actual result** | |
| **Status** | Not Tested |
| **Notes** | |

---

### EVT-002: Anonymous Visitor Click on "Register" / "RSVP" Redirect

| Field | Details |
| :--- | :--- |
| **Test ID** | EVT-002 |
| **Feature** | Unauthenticated action protection on event RSVP |
| **Prerequisites** | Browser in guest/incognito mode (unauthenticated) |
| **Steps** | 1. Navigate to `http://localhost/alumni-network/event.html`.<br>2. Click "Register for Event" (or "RSVP") on Event ID 1.<br>3. Inspect browser URL and page state. |
| **Expected result** | 1. Action intercepted immediately.<br>2. Visitor redirected to `auth/login.php?redirect=event.html%3Frsvp%3D1&msg=auth_required`.<br>3. Login page displays `"Please log in to continue."`<br>4. After login, visitor is returned back to `event.html` with target event ready for RSVP. |
| **Actual result** | |
| **Status** | Not Tested |
| **Notes** | |

---

### EVT-003: Anonymous Visitor Click on "+ Create Event" Redirect

| Field | Details |
| :--- | :--- |
| **Test ID** | EVT-003 |
| **Feature** | Unauthenticated action protection on event creation form |
| **Prerequisites** | Browser in guest/incognito mode |
| **Steps** | 1. Navigate to `http://localhost/alumni-network/event.html`.<br>2. Click "+ Create Event" button in page header.<br>3. Inspect browser redirect. |
| **Expected result** | 1. Visitor redirected to `auth/login.php?redirect=eventcreate.html&msg=auth_required`.<br>2. Login page displays `"Please log in to continue."` notice.<br>3. Event creation form cannot be submitted without an authenticated user ID. |
| **Actual result** | |
| **Status** | Not Tested |
| **Notes** | |

---

### EVT-004: Authenticated Event RSVP Submission

| Field | Details |
| :--- | :--- |
| **Test ID** | EVT-004 |
| **Feature** | Event attendee registration and count incrementation |
| **Prerequisites** | Logged in as `johndoe` (User ID 2) |
| **Steps** | 1. Navigate to `http://localhost/alumni-network/event.html`.<br>2. Click "Register for Event" on an upcoming event (e.g. Event ID 1).<br>3. Inspect Network response.<br>4. Check attendee count on card.<br>5. Query MySQL: `SELECT * FROM event_registrations WHERE event_id = 1 AND user_id = 2;` |
| **Expected result** | 1. POST request sent to `event.php?action=register` with `event_id=1`.<br>2. Response returns `{ "success": true, "registered": true, "attendee_count": N }`.<br>3. Database record inserted in `event_registrations` with `status = 'attending'`.<br>4. Card button changes to "Registered &#10003;" (or "Cancel RSVP") and attendee counter increases by 1. |
| **Actual result** | |
| **Status** | Not Tested |
| **Notes** | |

---

### EVT-005: Duplicate Event RSVP Prevention

| Field | Details |
| :--- | :--- |
| **Test ID** | EVT-005 |
| **Feature** | Duplicate RSVP handling and toggle support |
| **Prerequisites** | User `johndoe` is already registered for Event ID 1 (from EVT-004) |
| **Steps** | 1. Refresh `http://localhost/alumni-network/event.html`.<br>2. Inspect Event ID 1 card registration button state.<br>3. Click the registration button again. |
| **Expected result** | 1. Card loads with button indicating user is already registered.<br>2. If button functions as toggle / un-register, it prompts or updates status to cancelled.<br>3. User is not double-counted in attendee tally. |
| **Actual result** | |
| **Status** | Not Tested |
| **Notes** | |

---

### EVT-006: Normal User Creates Event (Pending Moderation)

| Field | Details |
| :--- | :--- |
| **Test ID** | EVT-006 |
| **Feature** | Normal user event creation and moderation lifecycle |
| **Prerequisites** | Logged in as `johndoe` |
| **Steps** | 1. Navigate to `http://localhost/alumni-network/eventcreate.html`.<br>2. Fill in event fields:<br>&nbsp;&nbsp;&nbsp;- Title: `Alumni AI & Robotics Workshop 2026`<br>&nbsp;&nbsp;&nbsp;- Date: `2026-11-15`<br>&nbsp;&nbsp;&nbsp;- Start Time: `10:00`<br>&nbsp;&nbsp;&nbsp;- End Time: `14:00`<br>&nbsp;&nbsp;&nbsp;- Location: `Faculty of Computing Auditorium`<br>&nbsp;&nbsp;&nbsp;- Capacity: `75`<br>&nbsp;&nbsp;&nbsp;- Description: `Hands-on workshop demonstrating LLMs and ROS robotics.`<br>3. Click "Submit Event".<br>4. Query database: `SELECT id, title, status, organizer_id FROM events WHERE title = 'Alumni AI & Robotics Workshop 2026';` |
| **Expected result** | 1. POST request submitted to `event.php?action=save`.<br>2. Database record inserted with `organizer_id = 2` and `status = 'pending'`.<br>3. Page confirms submission and informs user that event requires administrator review.<br>4. Event does NOT appear on public `event.html` until approved. |
| **Actual result** | |
| **Status** | Not Tested |
| **Notes** | |

---

### EVT-007: Administrator Approves Pending Event

| Field | Details |
| :--- | :--- |
| **Test ID** | EVT-007 |
| **Feature** | Administrator event moderation — approval |
| **Prerequisites** | Logged in as `admin`; pending event exists (from EVT-006) |
| **Steps** | 1. Navigate to `eventadmin.html` (or `admin/events.php`).<br>2. Find "Alumni AI & Robotics Workshop 2026" in pending moderation queue.<br>3. Click "Approve".<br>4. Check database: `SELECT status FROM events WHERE title = 'Alumni AI & Robotics Workshop 2026';`<br>5. Check public `event.html`. |
| **Expected result** | 1. POST to `event.php?action=approve` with event ID returns `{ "success": true }`.<br>2. Database updates `events.status = 'approved'`.<br>3. Event is now visible to all visitors on `event.html`. |
| **Actual result** | |
| **Status** | Not Tested |
| **Notes** | |

---

### EVT-008: Administrator Rejects and Deletes Event

| Field | Details |
| :--- | :--- |
| **Test ID** | EVT-008 |
| **Feature** | Administrator event moderation — rejection & deletion |
| **Prerequisites** | Logged in as `admin` |
| **Steps** | 1. On `eventadmin.html`, locate test event.<br>2. Click "Reject".<br>3. Confirm status changes to rejected.<br>4. Click "Delete" and confirm dialog. |
| **Expected result** | 1. Status updates to `rejected` in database.<br>2. Delete action removes event row and cascade deletes associated registrations.<br>3. Event removed from UI table. |
| **Actual result** | |
| **Status** | Not Tested |
| **Notes** | |

---

### EVT-009: Event Capacity Limit Enforcement

| Field | Details |
| :--- | :--- |
| **Test ID** | EVT-009 |
| **Feature** | Maximum event capacity check |
| **Prerequisites** | Event exists with `capacity = 1` and already has 1 attendee |
| **Steps** | 1. Log in with a different user (e.g. `sarahj`).<br>2. Attempt to click "Register" on the full event.<br>3. Inspect response and button state. |
| **Expected result** | 1. Registration is blocked.<br>2. Button displays "Event Full" (disabled) or server returns `{ "success": false, "error": "Event capacity reached." }`.<br>3. No additional registration is accepted beyond capacity. |
| **Actual result** | |
| **Status** | Not Tested |
| **Notes** | |
