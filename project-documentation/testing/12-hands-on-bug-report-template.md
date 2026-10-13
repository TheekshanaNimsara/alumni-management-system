# 12 — Hands-on Testing Bug Report Template & Logging Guide

**Project:** University Alumni Network  
**Target Audience:** Beginner QA Tester / Developer documenting bugs during manual testing  
**Purpose:** Provide a standardized bug reporting format to capture defects, evidence, suspected root causes, and retest outcomes.

---

## 1. Defect Classification Guide

When a test step deviates from the expected result, classify the defect using these standards:

### Severity (Impact on System)
* **Critical**: Security vulnerability (e.g. SQL injection, unauthenticated admin access), data corruption, or fatal crash that halts testing.
* **High**: Primary feature fails with no workaround (e.g. login broken, cannot submit job application, RSVP button non-functional).
* **Medium**: Feature works partially, minor error message inaccuracy, or layout glitch on specific screen widths.
* **Low**: Cosmetic visual imperfections, slight padding/color mismatch, or minor animation lag.

### Priority (Resolution Urgency)
* **P1 (Blocker)**: Must be fixed before any Git commit or push.
* **P2 (High)**: Must be resolved before testing can complete.
* **P3 (Normal)**: Standard issue to fix in code refactoring.
* **P4 (Low)**: Minor refinement or visual polish.

---

## 2. Blank Bug Report Template (Copy & Paste)

Copy the Markdown block below for each discovered bug and fill in the details:

```markdown
### [BUG-HOT-XXX]: Short Descriptive Bug Title

| Field | Details |
| :--- | :--- |
| **Bug ID** | BUG-HOT-001 |
| **Related Test ID** | e.g. HOT-AUTH-004, HOT-JOB-001, HOT-SEC-002 |
| **Date Discovered** | YYYY-MM-DD |
| **Tester Name** | [Your Name] |
| **Severity** | Critical / High / Medium / Low |
| **Priority** | P1 / P2 / P3 / P4 |
| **Page / Feature** | e.g. `jobs.html`, `auth/login.php`, `admin/jobs.php` |
| **Test Account Role** | Anonymous Visitor / Normal User (`johndoe`) / Admin (`admin_qa1`) |
| **Environment** | Local XAMPP (Apache + PHP 8.2 + MySQL) / Static GitHub Pages |
| **Browser & Version** | e.g. Chrome 129 on Windows 11 |

#### 1. Preconditions
- [State pre-requisites: user login state, database status, test record IDs]

#### 2. Exact Reproduction Steps
1. Navigate to `...`
2. Click on `...`
3. Enter `...` into input field `...`
4. Submit form / trigger action

#### 3. Expected Behavior
- [What should have happened according to the specification]

#### 4. Actual Behavior
- [What actually occurred during manual testing]

#### 5. Technical Evidence & Logs
- **Console Output:** `[Paste DevTools Console log or 'Zero errors']`
- **Network Request / Response:**
  - URL: `...`
  - Method: `GET / POST`
  - Status Code: `e.g. 500 / 403 / 200`
  - Response Body: `...`
- **Server Error Log:** `[Paste C:\xampp\apache\logs\error.log excerpt or 'None']`
- **Screenshot / Visual Evidence:** `[Describe or link screenshot]`

#### 6. Suspected Root Cause
- [Which file or logic caused the issue, e.g. missing if check in job.php]

#### 7. Suggested Fix / Code Pointer
- [Recommended code change to resolve the issue]

#### 8. Retest Result
- **Status:** Pending Fix / Pass / Fail
- **Retest Date:** YYYY-MM-DD
- **Verified By:** [Tester Name]
- **Notes:** [Outcome of retest]
```

---

## 3. Concrete Example of a Completed Bug Report

Here is an example demonstrating how to document a defect clearly:

```markdown
### [BUG-HOT-001]: Unauthenticated Click on "Apply Now" Inside Details Page Does Not Redirect

| Field | Details |
| :--- | :--- |
| **Bug ID** | BUG-HOT-001 |
| **Related Test ID** | HOT-JOB-001 |
| **Date Discovered** | 2026-10-12 |
| **Tester Name** | Alex QA |
| **Severity** | High |
| **Priority** | P1 (Must fix before push) |
| **Page / Feature** | `job-details.html` / Job Application Flow |
| **Test Account Role** | Anonymous Visitor |
| **Environment** | Local XAMPP (Apache + PHP 8.2 + MySQL) |
| **Browser & Version** | Chrome 129 on Windows 11 |

#### 1. Preconditions
- Visitor is not logged in (Incognito window, no cookies set).
- Navigated directly to `job-details.html?id=1`.

#### 2. Exact Reproduction Steps
1. Open `http://localhost/alumni-network/job-details.html?id=1`.
2. Locate the "Apply for Position" button at the bottom of the page.
3. Click the button.

#### 3. Expected Behavior
- Unauthenticated visitor should be redirected to `auth/login.php?redirect=job-details.html?id=1&msg=auth_required` with the notice: "Please log in to continue."

#### 4. Actual Behavior
- Modal opened and allowed typing an application note. Upon submitting, API returned HTTP 401 JSON error in console, but user was not redirected to the login page smoothly.

#### 5. Technical Evidence & Logs
- **Console Output:** `POST http://localhost/alumni-network/job.php?action=apply 401 (Unauthorized)`
- **Network Response:**
  ```json
  {
    "success": false,
    "error": "Please log in to continue.",
    "redirect": "auth/login.php?redirect=../job-details.html?id=1&msg=auth_required"
  }
  ```
- **Server Error Log:** No PHP fatal errors.

#### 6. Suspected Root Cause
- In `job-details.js`, the click handler on `.apply-now-btn` does not check `currentUser.logged_in` before opening modal.

#### 7. Suggested Fix / Code Pointer
- Add auth guard at the top of the click handler in `job-details.js`:
  ```javascript
  if (!currentUser || !currentUser.logged_in) {
      window.location.href = `auth/login.php?redirect=job-details.html?id=${jobId}&msg=auth_required`;
      return;
  }
  ```

#### 8. Retest Result
- **Status:** Pending Fix
- **Retest Date:** —
- **Verified By:** —
- **Notes:** Awaiting code fix before retesting.
```
