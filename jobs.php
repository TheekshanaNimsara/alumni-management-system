<?php
// ============================================================
// University Alumni Network - Jobs & Opportunities Board
// Opportunities, Internal Applications, Deadlines & Moderation
// ============================================================
$pageTitle = 'Career Opportunities';
$currentPage = 'jobs';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/functions.php';

$message = '';
$messageType = '';
$currentUserId = current_user_id();

// 1. Handle Internal Job Application
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'apply_job') {
    if (!$isLoggedIn) {
        $message = "Please log in to submit a job application.";
        $messageType = "danger";
    } else {
        $jobId = intval($_POST['job_id'] ?? 0);
        $coverMessage = trim($_POST['application_message'] ?? '');

        if ($jobId > 0 && $db_connected && $pdo) {
            try {
                // Check if already applied
                $stmt = $pdo->prepare("SELECT id, status FROM job_applications WHERE job_id = ? AND user_id = ?");
                $stmt->execute([$jobId, $currentUserId]);
                $existingApp = $stmt->fetch();

                if ($existingApp) {
                    $message = "You have already applied for this position (Current Status: " . htmlspecialchars($existingApp['status']) . ").";
                    $messageType = "warning";
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO job_applications (job_id, user_id, message, status, applied_at)
                        VALUES (?, ?, ?, 'Applied', NOW())
                    ");
                    $stmt->execute([$jobId, $currentUserId, $coverMessage]);
                    $message = "Application submitted successfully! The recruiter/alumnus will review your profile.";
                    $messageType = "success";
                }
            } catch (Exception $e) {
                $message = "Application submission error: " . $e->getMessage();
                $messageType = "danger";
            }
        }
    }
}

// 2. Handle Job Posting Submission by logged-in users
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'post_job') {
    if (!$isLoggedIn) {
        $message = "Please log in to post a career opportunity.";
        $messageType = "danger";
    } else {
        $title = trim($_POST['title'] ?? '');
        $company = trim($_POST['company'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $job_type = trim($_POST['job_type'] ?? 'Full-Time');
        $description = trim($_POST['description'] ?? '');
        $requirements = trim($_POST['requirements'] ?? '');
        $deadline = !empty($_POST['deadline']) ? trim($_POST['deadline']) : null;
        $application_link = trim($_POST['application_link'] ?? '');

        if (!empty($title) && !empty($company) && !empty($location) && !empty($description)) {
            if ($db_connected && $pdo) {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO jobs (title, company, location, job_type, description, requirements, deadline, application_link, posted_by, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
                    ");
                    $stmt->execute([$title, $company, $location, $job_type, $description, $requirements, $deadline, $application_link, $currentUserId]);
                    $message = "Your job opportunity has been submitted for admin approval.";
                    $messageType = "success";
                } catch (Exception $e) {
                    $message = "Error posting job: " . $e->getMessage();
                    $messageType = "danger";
                }
            } else {
                $message = "Job vacancy recorded successfully!";
                $messageType = "success";
            }
        } else {
            $message = "Please complete all mandatory fields.";
            $messageType = "danger";
        }
    }
}

// Filter
$typeFilter = isset($_GET['type']) ? trim($_GET['type']) : '';

$jobs = [];
if ($db_connected && $pdo) {
    try {
        $userIdParam = $currentUserId ? $currentUserId : 0;
        $sql = "
            SELECT j.*, 
                   CONCAT(u.first_name, ' ', u.last_name) AS poster_name,
                   ja.id AS application_id, ja.status AS application_status
            FROM jobs j
            LEFT JOIN users u ON j.posted_by = u.id
            LEFT JOIN job_applications ja ON ja.job_id = j.id AND ja.user_id = ?
            WHERE j.status = 'approved'
        ";
        $params = [$userIdParam];
        if (!empty($typeFilter)) {
            $sql .= " AND j.job_type = ?";
            $params[] = $typeFilter;
        }
        $sql .= " ORDER BY j.posted_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $jobs = $stmt->fetchAll();
    } catch (Exception $e) {
        $jobs = [];
    }
}

if (empty($jobs) && empty($typeFilter)) {
    $jobs = get_sample_jobs();
}
?>

<!-- Cinematic Header -->
<section style="background: var(--primary-color); color: var(--text-light); padding: 4rem 0 3rem; border-bottom: 2px solid var(--accent-color);">
    <div class="container text-center" style="text-align: center;">
        <span class="section-eyebrow">Professional Opportunities &bull; Internships &bull; Mentorship</span>
        <h1 class="section-title" style="color: var(--text-light); margin-bottom: 0.8rem;">Career Board</h1>
        <p style="color: rgba(248, 244, 234, 0.75); max-width: 600px; margin: 0 auto;">
            Exclusive career postings and high-impact industry roles shared by distinguished alumni.
        </p>
    </div>
</section>

<div class="container" style="padding-top: 3rem; padding-bottom: 5rem;">

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Header Actions and Filters -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h2 style="color: var(--primary-color); font-size: 1.6rem; font-weight: 800; margin: 0 0 0.3rem 0;">Available Positions</h2>
            <p style="color: var(--text-muted); font-size: 0.95rem; margin: 0;">Verified opportunities from alumni organizations worldwide</p>
        </div>
        <div>
            <a href="#post-job" class="btn btn-gold gold-glow btn-sm">
                + Post an Opportunity
            </a>
        </div>
    </div>

    <!-- Filter Pills -->
    <div style="display: flex; gap: 0.6rem; margin-bottom: 2rem; flex-wrap: wrap;">
        <a href="jobs.php" class="btn btn-sm <?php echo empty($typeFilter) ? 'btn-gold' : 'btn-outline-light'; ?>" style="<?php echo empty($typeFilter) ? '' : 'color: var(--text-color); border-color: var(--border-color);'; ?>">
            All Roles
        </a>
        <a href="jobs.php?type=Full-Time" class="btn btn-sm <?php echo ($typeFilter === 'Full-Time') ? 'btn-gold' : 'btn-outline-light'; ?>" style="<?php echo ($typeFilter === 'Full-Time') ? '' : 'color: var(--text-color); border-color: var(--border-color);'; ?>">
            Full-Time
        </a>
        <a href="jobs.php?type=Internship" class="btn btn-sm <?php echo ($typeFilter === 'Internship') ? 'btn-gold' : 'btn-outline-light'; ?>" style="<?php echo ($typeFilter === 'Internship') ? '' : 'color: var(--text-color); border-color: var(--border-color);'; ?>">
            Internships
        </a>
        <a href="jobs.php?type=Remote" class="btn btn-sm <?php echo ($typeFilter === 'Remote') ? 'btn-gold' : 'btn-outline-light'; ?>" style="<?php echo ($typeFilter === 'Remote') ? '' : 'color: var(--text-color); border-color: var(--border-color);'; ?>">
            Remote
        </a>
        <a href="jobs.php?type=Contract" class="btn btn-sm <?php echo ($typeFilter === 'Contract') ? 'btn-gold' : 'btn-outline-light'; ?>" style="<?php echo ($typeFilter === 'Contract') ? '' : 'color: var(--text-color); border-color: var(--border-color);'; ?>">
            Contract
        </a>
    </div>

    <!-- Jobs Grid -->
    <div class="grid grid-2" style="gap: 1.8rem;">
        <?php foreach ($jobs as $job): ?>
            <?php 
                $appStatus = $job['application_status'] ?? null;
            ?>
            <div class="card job-card" style="border-top: 3px solid var(--accent-color); display: flex; flex-direction: column;">
                
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.8rem; gap: 0.8rem;">
                    <div>
                        <span class="badge" style="background: rgba(36, 7, 10, 0.08); color: var(--primary-color); border: 1px solid var(--border-color); margin-bottom: 0.5rem; display: inline-block;">
                            <?php echo htmlspecialchars($job['company']); ?>
                        </span>
                        <h3 style="color: var(--primary-color); font-size: 1.35rem; margin: 0; font-weight: 800;">
                            <?php echo htmlspecialchars($job['title']); ?>
                        </h3>
                    </div>

                    <div style="text-align: right; flex-shrink: 0;">
                        <span class="badge" style="background: rgba(212, 175, 55, 0.15); color: var(--accent-dark); border: 1px solid var(--accent-color);">
                            <?php echo htmlspecialchars($job['job_type']); ?>
                        </span>
                    </div>
                </div>

                <div class="job-meta" style="margin-bottom: 1rem; color: var(--text-muted); font-size: 0.88rem; display: flex; gap: 1rem; flex-wrap: wrap;">
                    <span>&#128205; <?php echo htmlspecialchars($job['location']); ?></span>
                    <?php if (!empty($job['posted_at'])): ?>
                        <span>&#128197; <?php echo date('d M Y', strtotime($job['posted_at'])); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($job['deadline'])): ?>
                        <span style="color: var(--danger-color); font-weight: 600;">&#9203; Deadline: <?php echo date('d M Y', strtotime($job['deadline'])); ?></span>
                    <?php endif; ?>
                </div>

                <p style="color: var(--text-color); font-size: 0.94rem; line-height: 1.6; margin-bottom: 1rem; flex: 1;">
                    <?php echo nl2br(htmlspecialchars($job['description'])); ?>
                </p>

                <?php if (!empty($job['requirements'])): ?>
                    <div style="background: #faf9f6; border-left: 3px solid var(--accent-color); padding: 0.7rem 1rem; border-radius: 4px; font-size: 0.86rem; color: var(--text-color); margin-bottom: 1.2rem;">
                        <strong style="color: var(--primary-color);">Requirements:</strong> <?php echo htmlspecialchars($job['requirements']); ?>
                    </div>
                <?php endif; ?>

                <!-- Footer Action Area -->
                <div style="padding-top: 1rem; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; gap: 0.8rem; flex-wrap: wrap;">
                    <div style="font-size: 0.82rem; color: var(--text-muted);">
                        Posted by: <strong style="color: var(--primary-color);"><?php echo htmlspecialchars(!empty($job['poster_name']) ? $job['poster_name'] : 'Alumni Network'); ?></strong>
                    </div>

                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <!-- Report Job -->
                        <button type="button" class="btn btn-outline-light btn-sm" style="font-size: 0.72rem; padding: 0.25rem 0.5rem; color: var(--text-muted);" 
                                onclick="openReportModal(<?php echo $job['id']; ?>, 'job', '<?php echo htmlspecialchars(addslashes($job['title'])); ?>');" title="Report Job">
                            &#9873;
                        </button>

                        <?php if ($appStatus): ?>
                            <span class="badge badge-success" style="padding: 0.4rem 0.75rem;">
                                &#10003; <?php echo htmlspecialchars($appStatus); ?>
                            </span>
                        <?php else: ?>
                            <?php if ($isLoggedIn): ?>
                                <button type="button" class="btn btn-gold btn-sm gold-glow" onclick="openApplyModal(<?php echo $job['id']; ?>, '<?php echo htmlspecialchars(addslashes($job['title'])); ?>', '<?php echo htmlspecialchars(addslashes($job['company'])); ?>');">
                                    Apply Directly
                                </button>
                            <?php else: ?>
                                <a href="auth/login.php" class="btn btn-gold btn-sm">
                                    Login to Apply
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if (!empty($job['application_link'])): ?>
                            <a href="<?php echo htmlspecialchars($job['application_link']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-gold btn-sm">
                                External Portal &rarr;
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        <?php endforeach; ?>
    </div>

    <!-- Post a Career Opportunity Form -->
    <div id="post-job" class="card" style="margin-top: 5rem; padding: 2.5rem; border-top: 4px solid var(--accent-color);">
        <h3 style="color: var(--primary-color); font-size: 1.5rem; margin-bottom: 0.5rem; font-weight: 800;">
            Post a Career Opportunity
        </h3>
        <p style="color: var(--text-muted); margin-bottom: 2rem; font-size: 0.95rem;">
            Support the university community by offering full-time positions, research roles, and internships at your organization.
        </p>

        <?php if ($isLoggedIn): ?>
            <form method="POST" action="jobs.php#post-job">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="post_job">
                
                <div class="grid grid-2" style="margin-bottom: 1.2rem; gap: 1.5rem;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="title">Job Title *</label>
                        <input type="text" id="title" name="title" class="form-control" placeholder="e.g. Associate Cloud Engineer" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="company">Company / Organization *</label>
                        <input type="text" id="company" name="company" class="form-control" placeholder="e.g. Virtusa, WSO2, London Stock Exchange Group" required>
                    </div>
                </div>

                <div class="grid grid-3" style="margin-bottom: 1.2rem; gap: 1.5rem;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="location">Location *</label>
                        <input type="text" id="location" name="location" class="form-control" placeholder="e.g. Colombo, Sri Lanka (Hybrid)" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="job_type">Employment Type</label>
                        <select id="job_type" name="job_type" class="form-control">
                            <option value="Full-Time">Full-Time</option>
                            <option value="Part-Time">Part-Time</option>
                            <option value="Internship">Internship</option>
                            <option value="Contract">Contract</option>
                            <option value="Remote">Remote</option>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="deadline">Application Deadline</label>
                        <input type="date" id="deadline" name="deadline" class="form-control" min="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 1.2rem;">
                    <label for="requirements">Candidate Requirements & Qualifications</label>
                    <input type="text" id="requirements" name="requirements" class="form-control" placeholder="e.g. B.Sc. in Computing/Engineering, knowledge of Python, Git, and Docker">
                </div>

                <div class="form-group" style="margin-bottom: 1.2rem;">
                    <label for="application_link">External Application URL (Optional)</label>
                    <input type="url" id="application_link" name="application_link" class="form-control" placeholder="https://careers.yourcompany.com/apply/1234">
                </div>

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label for="description">Role Description & Responsibilities *</label>
                    <textarea id="description" name="description" class="form-control" rows="4" placeholder="Detail the day-to-day responsibilities, learning outcomes, tech stack, and benefits..." required></textarea>
                </div>

                <button type="submit" class="btn btn-gold gold-glow">
                    Submit Job for Moderation
                </button>
            </form>
        <?php else: ?>
            <div style="background: var(--bg-color); border: 1px dashed var(--border-color); padding: 2rem; border-radius: var(--border-radius); text-align: center;">
                <p style="color: var(--text-color); margin-bottom: 1rem; font-weight: 600;">
                    Please log in with your verified alumni account to submit job opportunities.
                </p>
                <div style="display: flex; gap: 1rem; justify-content: center;">
                    <a href="auth/login.php" class="btn btn-primary btn-sm">Login to Your Account</a>
                    <a href="auth/register.php" class="btn btn-gold btn-sm">Register as Alumni</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- Modal for Direct Job Application -->
<div id="applyModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: #ffffff; width: 90%; max-width: 520px; border-radius: var(--border-radius); padding: 2rem; border-top: 4px solid var(--accent-color); box-shadow: 0 15px 40px rgba(0,0,0,0.5);">
        <h3 id="applyJobTitle" style="color: var(--primary-color); margin-bottom: 0.3rem; font-weight: 800;">Apply for Position</h3>
        <p id="applyCompany" style="color: var(--accent-dark); font-weight: 600; margin-bottom: 1.2rem; font-size: 0.92rem;"></p>

        <form method="POST" action="jobs.php">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="apply_job">
            <input type="hidden" name="job_id" id="apply_job_id" value="">
            
            <div class="form-group">
                <label for="application_message">Cover Note / Why You are a Great Fit</label>
                <textarea name="application_message" id="application_message" rows="4" class="form-control" placeholder="Introduce yourself, your degree background, and your relevant projects or GitHub/portfolio links..." required></textarea>
            </div>

            <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 1rem;">
                Your verified KDU alumni profile details and contact email will be automatically shared with the posting alumnus.
            </p>

            <div style="display: flex; gap: 0.8rem; justify-content: flex-end;">
                <button type="button" class="btn btn-outline-light" style="color: var(--text-color);" onclick="closeApplyModal();">Cancel</button>
                <button type="submit" class="btn btn-gold">Submit Application</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal for reporting job -->
<div id="reportModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: #ffffff; width: 90%; max-width: 480px; border-radius: var(--border-radius); padding: 2rem; border-top: 4px solid var(--accent-color); box-shadow: 0 15px 40px rgba(0,0,0,0.5);">
        <h3 style="color: var(--primary-color); margin-bottom: 0.5rem; font-weight: 800;">Report Job Vacancy</h3>
        <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.2rem;">
            Report fraudulent positions, expired listings, or inappropriate company posts.
        </p>

        <form method="POST" action="actions/report.php">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="job_id" id="report_job_id" value="">
            
            <div class="form-group">
                <label for="report_reason">Reason for Report *</label>
                <select name="reason" id="report_reason" class="form-control" required>
                    <option value="">Select a reason...</option>
                    <option value="Fraudulent or Suspicious Posting">Fraudulent or Suspicious Posting</option>
                    <option value="Expired or Closed Position">Expired or Closed Position</option>
                    <option value="Misleading Requirements or Salary">Misleading Requirements or Salary</option>
                    <option value="Other Policy Violation">Other Policy Violation</option>
                </select>
            </div>

            <div class="form-group">
                <label for="report_desc">Details</label>
                <textarea name="description" id="report_desc" rows="3" class="form-control" placeholder="Provide additional details..."></textarea>
            </div>

            <div style="display: flex; gap: 0.8rem; justify-content: flex-end; margin-top: 1.5rem;">
                <button type="button" class="btn btn-outline-light" style="color: var(--text-color);" onclick="closeReportModal();">Cancel</button>
                <button type="submit" class="btn btn-gold">Submit Report</button>
            </div>
        </form>
    </div>
</div>

<script>
function openApplyModal(jobId, title, company) {
    document.getElementById('apply_job_id').value = jobId;
    document.getElementById('applyJobTitle').innerText = 'Apply for ' + title;
    document.getElementById('applyCompany').innerText = company;
    document.getElementById('applyModal').style.display = 'flex';
}
function closeApplyModal() {
    document.getElementById('applyModal').style.display = 'none';
}

function openReportModal(jobId, type, title) {
    document.getElementById('report_job_id').value = jobId;
    document.getElementById('reportModal').style.display = 'flex';
}
function closeReportModal() {
    document.getElementById('reportModal').style.display = 'none';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
