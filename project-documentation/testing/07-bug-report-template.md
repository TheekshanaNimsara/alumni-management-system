# 07 — Bug Reporting Standard & Defect Template

**Project:** University Alumni Network  
**Purpose:** Standardized defect documentation protocol for manual QA testing, security audits, and issue triage prior to GitHub push.

---

## 1. Defect Severity & Priority Classification

When encountering a failed test case, classify the defect according to the following matrix:

### Severity Definitions (Impact on System)
* **Critical (S1)**: Complete system crash, database corruption, critical security breach (e.g. SQL injection, unauthenticated admin access, privilege escalation), or primary authentication failure that blocks all testing.
* **High (S2)**: Major feature broken with no workaround (e.g. inability to submit job postings, failure to record event RSVPs, broken redirect loops).
* **Medium (S3)**: Feature partially functional or works with a workaround; UI layout breakage on specific viewports; improper error message displayed.
* **Low (S4)**: Cosmetic imperfections, minor spacing issues, typography mismatches, or non-blocking animations.

### Priority Definitions (Urgency to Resolve)
* **P1 (Immediate Blocker)**: Must be fixed and verified immediately before pushing to GitHub or deploying live.
* **P2 (High Priority)**: Must be resolved before general release.
* **P3 (Normal Priority)**: Standard bug to address in next development cycle.
* **P4 (Low Priority)**: Nice-to-have visual refinement or optimization.

---

## 2. Standard Bug Report Template (Copy & Paste)

Copy the Markdown block below for each discovered bug and append it to your bug tracking log.

```markdown
### [BUG-ID]: Short Descriptive Summary of the Defect

| Field | Details |
| :--- | :--- |
| **Bug ID** | BUG-XXX-001 |
| **Linked Test ID** | e.g. AUTH-001, JOB-003, UI-005 |
| **Date Discovered** | YYYY-MM-DD |
| **Reporter** | QA Engineer |
| **Severity** | Critical / High / Medium / Low |
| **Priority** | P1 / P2 / P3 / P4 |
| **Affected Role(s)** | Anonymous Visitor / Normal Alumni / Administrator |
| **Environment** | Local XAMPP (Apache + PHP 8.2 + MySQL) / Static GitHub Pages |
| **Browser & OS** | e.g. Chrome 129 / Edge 129 on Windows 11 |
| **Affected File(s)** | e.g. `jobs.js`, `job.php`, `assets/css/theme.css` |

#### 1. Prerequisites
- [State pre-conditions, user account status, database state]

#### 2. Steps to Reproduce
1. Navigate to `...`
2. Click on `...`
3. Enter `...` into input field `...`
4. Submit form / trigger action

#### 3. Expected Result
- [Detailed description of what should have occurred according to the specification]

#### 4. Actual Result
- [Exact behavior observed during execution]

#### 5. Technical Evidence & Logs
- **Console Errors:** `[Paste DevTools Console log here or 'None']`
- **Network Request / Response:**
  - Request URL: `...`
  - Method: `GET / POST`
  - Status Code: `e.g. 500 Internal Server Error`
  - Response Body: `...`
- **Server / PHP Error Log:** `[Paste C:\xampp\apache\logs\error.log excerpt here]`
- **Database State:** `[SQL query result before and after]`

#### 6. Suspected Root Cause
- [Technical hypothesis on which line or logic structure caused the issue]

#### 7. Recommended Fix / Code Pointer
- [Suggested resolution or code pointer]
```

---

## 3. Concrete Example of a Completed Bug Report

For reference, here is an example of an actionable, high-quality bug report:

```markdown
### [BUG-JOB-002]: Anonymous Visitor Able to Post Job via Direct API Request

| Field | Details |
| :--- | :--- |
| **Bug ID** | BUG-JOB-002 |
| **Linked Test ID** | PERM-006, JOB-004 |
| **Date Discovered** | 2026-10-12 |
| **Reporter** | Senior QA Engineer |
| **Severity** | High |
| **Priority** | P1 (Must fix before push) |
| **Affected Role(s)** | Anonymous Visitor |
| **Environment** | Local XAMPP (PHP 8.2 + MySQL) |
| **Browser & OS** | Chrome 129 on Windows 11 |
| **Affected File(s)** | `c:\xampp\htdocs\alumni-network\job.php` |

#### 1. Prerequisites
- Local Apache and MySQL running; client has no active session cookie (`PHPSESSID` absent).

#### 2. Steps to Reproduce
1. Open PowerShell or Postman.
2. Send POST request to `http://localhost/alumni-network/job.php?action=create`:
   ```powershell
   Invoke-RestMethod -Uri "http://localhost/alumni-network/job.php?action=create" -Method Post -Body @{
       title = "Ghost Job"
       company = "Ghost Inc"
       location = "Remote"
       description = "Sample"
   }
   ```
3. Observe HTTP status code and check database.

#### 3. Expected Result
- Server intercepts request, checks `current_user_id()`, rejects with HTTP 401 Unauthorized and JSON error:
  `{"success": false, "error": "Please log in to continue."}`.

#### 4. Actual Result
- If authentication check is omitted in controller, API accepts payload and sets `posted_by = 0` or NULL, inserting unauthenticated row into `jobs` table.

#### 5. Technical Evidence & Logs
- **Response Status:** HTTP 200 OK
- **Database Query Result:**
  ```sql
  SELECT id, title, posted_by FROM jobs WHERE title = 'Ghost Job';
  -- Returns row with posted_by = 0
  ```

#### 6. Suspected Root Cause
- In `job.php`, line 120, missing guard:
  `if (!$currentUserId) { http_response_code(401); echo json_encode(...); exit; }`

#### 7. Recommended Fix / Code Pointer
- Ensure `job.php` strictly verifies `if (!$currentUserId)` and returns HTTP 401 with redirect parameter before handling POST data.
```
