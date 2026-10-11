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
        header("Location: auth/login.php?redirect=" . urlencode("jobs.php#post-job"));
        exit;
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
$tabFilter  = isset($_GET['tab']) ? trim($_GET['tab']) : 'all';

$jobs = [];
if ($db_connected && $pdo) {
    try {
        $userIdParam = $currentUserId ? (int)$currentUserId : 0;
        
        if ($tabFilter === 'my' && $isLoggedIn) {
            $sql = "
                SELECT j.*, 
                       CONCAT(u.first_name, ' ', u.last_name) AS poster_name,
                       ja.id AS application_id, ja.status AS application_status
                FROM jobs j
                LEFT JOIN users u ON j.posted_by = u.id
                LEFT JOIN job_applications ja ON ja.job_id = j.id AND ja.user_id = ?
                WHERE j.posted_by = ?
            ";
            $params = [$userIdParam, $userIdParam];
        } else {
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
        }

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

if (empty($jobs) && empty($typeFilter) && $tabFilter !== 'my') {
    $jobs = get_sample_jobs();
}
?>

<!-- Cinematic Header -->
<section style="background: radial-gradient(circle at 50% 20%, rgba(147, 51, 234, 0.22) 0%, rgba(16, 9, 29, 0.95) 70%, #080510 100%); color: var(--text-light); padding: 4rem 0 3rem; border-bottom: 1px solid var(--border-color);">
    <div class="container text-center" style="text-align: center;">
        <span class="section-eyebrow">Professional Opportunities &bull; Internships &bull; Mentorship</span>
        <h1 class="section-title" style="color: var(--text-light); margin-bottom: 0.8rem;">Career Board</h1>
        <p style="color: var(--text-secondary); max-width: 600px; margin: 0 auto;">
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
            <h2 style="color: var(--text-light); font-size: 1.6rem; font-weight: 800; margin: 0 0 0.3rem 0;">Available Positions</h2>
            <p style="color: var(--text-muted); font-size: 0.95rem; margin: 0;">Verified opportunities from alumni organizations worldwide</p>
        </div>
        <div style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
            <a href="index.php" class="btn btn-return-home btn-outline-light btn-sm">&larr; Return to Homepage</a>
            <?php if ($isLoggedIn): ?>
                <a href="#post-job" class="btn btn-gold gold-glow btn-sm">
                    + Post an Opportunity
                </a>
            <?php else: ?>
                <a href="auth/login.php?redirect=<?php echo urlencode('jobs.php#post-job'); ?>" class="btn btn-gold gold-glow btn-sm">
                    + Post an Opportunity
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Main Tab Selection (All vs My Submissions) -->
    <div style="display: flex; gap: 0.8rem; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); padding-bottom: 1rem;">
        <a href="jobs.php?tab=all<?php echo !empty($typeFilter) ? '&type=' . urlencode($typeFilter) : ''; ?>" 
           class="btn btn-sm <?php echo ($tabFilter !== 'my') ? 'btn-gold' : 'btn-outline-light'; ?>">
            All Positions
        </a>
        <?php if ($isLoggedIn): ?>
            <a href="jobs.php?tab=my<?php echo !empty($typeFilter) ? '&type=' . urlencode($typeFilter) : ''; ?>" 
               class="btn btn-sm <?php echo ($tabFilter === 'my') ? 'btn-gold' : 'btn-outline-light'; ?>">
                My Submissions
            </a>
        <?php endif; ?>
    </div>

    <!-- Filter Pills -->
    <div style="display: flex; gap: 0.6rem; margin-bottom: 2rem; flex-wrap: wrap;">
        <a href="jobs.php?tab=<?php echo urlencode($tabFilter); ?>" class="btn btn-sm <?php echo empty($typeFilter) ? 'btn-gold' : 'btn-outline-light'; ?>" style="<?php echo empty($typeFilter) ? '' : 'color: var(--text-color); border-color: var(--border-color);'; ?>">
            All Roles
        </a>
        <a href="jobs.php?tab=<?php echo urlencode($tabFilter); ?>&type=Full-Time" class="btn btn-sm <?php echo ($typeFilter === 'Full-Time') ? 'btn-gold' : 'btn-outline-light'; ?>" style="<?php echo ($typeFilter === 'Full-Time') ? '' : 'color: var(--text-color); border-color: var(--border-color);'; ?>">
            Full-Time
        </a>
        <a href="jobs.php?tab=<?php echo urlencode($tabFilter); ?>&type=Internship" class="btn btn-sm <?php echo ($typeFilter === 'Internship') ? 'btn-gold' : 'btn-outline-light'; ?>" style="<?php echo ($typeFilter === 'Internship') ? '' : 'color: var(--text-color); border-color: var(--border-color);'; ?>">
            Internships
        </a>
        <a href="jobs.php?tab=<?php echo urlencode($tabFilter); ?>&type=Remote" class="btn btn-sm <?php echo ($typeFilter === 'Remote') ? 'btn-gold' : 'btn-outline-light'; ?>" style="<?php echo ($typeFilter === 'Remote') ? '' : 'color: var(--text-color); border-color: var(--border-color);'; ?>">
            Remote
        </a>
        <a href="jobs.php?tab=<?php echo urlencode($tabFilter); ?>&type=Contract" class="btn btn-sm <?php echo ($typeFilter === 'Contract') ? 'btn-gold' : 'btn-outline-light'; ?>" style="<?php echo ($typeFilter === 'Contract') ? '' : 'color: var(--text-color); border-color: var(--border-color);'; ?>">
            Contract
        </a>
    </div>

    <!-- Jobs Grid -->
    <div class="grid grid-2" style="gap: 1.8rem;">
        <?php foreach ($jobs as $job): ?>
            <?php 
                $appStatus = $job['application_status'] ?? null;
                $jStatus = $job['status'] ?? 'approved';
            ?>
            <div class="card job-card" style="display: flex; flex-direction: column;">
                
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.8rem; gap: 0.8rem;">
                    <div>
                        <span class="job-company" style="display: inline-block; margin-bottom: 0.4rem;">
                            <?php echo htmlspecialchars($job['company']); ?>
                        </span>
                        <h3 class="job-title" style="margin: 0;">
                            <a href="job-details.html?id=<?php echo $job['id']; ?>" style="color: inherit; text-decoration: none;">
                                <?php echo htmlspecialchars($job['title']); ?>
                            </a>
                        </h3>
                    </div>

                    <div style="text-align: right; flex-shrink: 0; display: flex; flex-direction: column; align-items: flex-end; gap: 4px;">
                        <span class="job-type-pill">
                            <?php echo htmlspecialchars($job['job_type']); ?>
                        </span>
                        <?php if ($jStatus === 'pending'): ?>
                            <span class="badge" style="background: rgba(245, 158, 11, 0.18); color: #F59E0B; border: 1px solid rgba(245, 158, 11, 0.35); padding: 0.2rem 0.5rem; font-size: 0.72rem; border-radius: 4px; font-weight: 700;">Pending Approval</span>
                        <?php elseif ($jStatus === 'rejected'): ?>
                            <span class="badge" style="background: rgba(239, 68, 68, 0.18); color: #EF4444; border: 1px solid rgba(239, 68, 68, 0.35); padding: 0.2rem 0.5rem; font-size: 0.72rem; border-radius: 4px; font-weight: 700;">Rejected</span>
                        <?php elseif ($tabFilter === 'my'): ?>
                            <span class="badge" style="background: rgba(16, 185, 129, 0.18); color: #10B981; border: 1px solid rgba(16, 185, 129, 0.35); padding: 0.2rem 0.5rem; font-size: 0.72rem; border-radius: 4px; font-weight: 700;">Approved</span>
                        <?php endif; ?>
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

                <p class="job-desc" style="margin-bottom: 1rem; flex: 1;">
                    <?php echo nl2br(htmlspecialchars($job['description'])); ?>
                </p>

                <?php if (!empty($job['requirements'])): ?>
                    <div style="background: rgba(124, 58, 237, 0.12); border-left: 3px solid var(--accent-color); border: 1px solid var(--border-color); padding: 0.7rem 1rem; border-radius: 4px; font-size: 0.86rem; color: var(--text-color); margin-bottom: 1.2rem;">
                        <strong style="color: var(--accent-light);">Requirements:</strong> <?php echo htmlspecialchars($job['requirements']); ?>
                    </div>
                <?php endif; ?>

                <!-- Footer Action Area -->
                <div style="padding-top: 1rem; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; gap: 0.8rem; flex-wrap: wrap;">
                    <div style="font-size: 0.82rem; color: var(--text-muted);">
                        Posted by: <strong style="color: var(--accent-light);"><?php echo htmlspecialchars(!empty($job['poster_name']) ? $job['poster_name'] : 'Alumni Network'); ?></strong>
                    </div>

                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <!-- Report Job -->
                        <button type="button" class="btn btn-outline-light btn-sm" style="font-size: 0.72rem; padding: 0.25rem 0.5rem; color: var(--text-muted);" 
                                onclick="openReportModal(<?php echo $job['id']; ?>, 'job', '<?php echo htmlspecialchars(addslashes($job['title'])); ?>');" title="Report Job">
                            &#9873;
                        </button>

                        <a href="job-details.html?id=<?php echo $job['id']; ?>" class="btn btn-outline-glass btn-sm">
                            View Details &rarr;
                        </a>

                        <?php if ($appStatus): ?>
                            <span class="badge badge-success" style="padding: 0.4rem 0.75rem;">
                                &#10003; <?php echo htmlspecialchars($appStatus); ?>
                            </span>
                        <?php else: ?>
                            <?php if ($isLoggedIn): ?>
                                <button type="button" class="btn btn-gold btn-sm gold-glow" 
                                        data-job='<?php echo htmlspecialchars(json_encode([
                                            "id" => $job["id"],
                                            "title" => $job["title"],
                                            "company" => $job["company"],
                                            "location" => $job["location"] ?? "",
                                            "job_type" => $job["job_type"] ?? "Full-Time",
                                            "deadline" => $job["deadline"] ?? "",
                                            "description" => $job["description"] ?? "",
                                            "requirements" => $job["requirements"] ?? "",
                                            "contact_email" => $job["contact_email"] ?? "",
                                            "application_link" => $job["application_link"] ?? ""
                                        ]), ENT_QUOTES, "UTF-8"); ?>'
                                        onclick="openApplyModalWithData(this);">
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
        <h3 style="color: var(--text-light); font-size: 1.5rem; margin-bottom: 0.5rem; font-weight: 800;">
            Post a Career Opportunity
        </h3>
        <p style="color: var(--text-muted); margin-bottom: 2rem; font-size: 0.95rem;">
            Support the university community by offering full-time positions, research roles, and internships at your organization.
        </p>

        <?php if ($isLoggedIn): ?>
            <form method="POST" action="jobs.php#post-job">
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
                    <a href="auth/login.php?redirect=<?php echo urlencode('jobs.php#post-job'); ?>" class="btn btn-primary btn-sm">Login to Your Account</a>
                    <a href="auth/register.php" class="btn btn-gold btn-sm">Register as Alumni</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- Modal for Direct Job Application -->
<div id="applyModal" class="modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(8, 5, 16, 0.75); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); z-index: 9999; align-items: center; justify-content: center; padding: 1rem;">
    <div class="modal-box" style="max-width: 620px; width: 100%; max-height: 90vh; overflow-y: auto; background: linear-gradient(145deg, rgba(32, 18, 56, 0.95) 0%, rgba(16, 9, 29, 0.98) 100%); border: 1px solid rgba(196, 181, 253, 0.28); border-top: 4px solid var(--accent-color); border-radius: 12px; padding: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
            <div>
                <h3 id="applyJobTitle" style="color: var(--text-light); margin-bottom: 0.2rem; font-weight: 800; font-size: 1.4rem;">Apply for Position</h3>
                <p id="applyCompany" style="color: var(--accent-light); font-weight: 600; margin: 0; font-size: 0.95rem;"></p>
            </div>
            <button type="button" onclick="closeApplyModal();" style="background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(196, 181, 253, 0.2); color: var(--text-light); width: 32px; height: 32px; border-radius: 50%; cursor: pointer; font-size: 1.2rem; display: flex; align-items: center; justify-content: center;">&times;</button>
        </div>

        <div id="applyJobMeta" style="display: flex; flex-wrap: wrap; gap: 1rem; font-size: 0.85rem; color: var(--text-muted); padding: 0.6rem 0; border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color); margin-bottom: 1rem;">
            <span id="applyLocation">&#128205; </span>
            <span id="applyType">&#128188; </span>
            <span id="applyDeadline" style="color: var(--danger-color);">&#9203; </span>
        </div>

        <div style="margin-bottom: 1rem;">
            <h5 style="color: var(--accent-light); font-size: 0.82rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 0.3rem;">Position Overview</h5>
            <p id="applyDesc" style="color: var(--text-color); font-size: 0.9rem; line-height: 1.5; margin: 0; max-height: 120px; overflow-y: auto;"></p>
        </div>

        <div id="applyReqSection" style="margin-bottom: 1.2rem; background: rgba(124, 58, 237, 0.10); border: 1px solid var(--border-color); border-radius: 6px; padding: 0.75rem 0.9rem; display: none;">
            <strong style="color: var(--accent-light); font-size: 0.84rem;">Requirements &amp; Tech Stack:</strong>
            <p id="applyReqs" style="color: var(--text-color); font-size: 0.86rem; margin: 0.3rem 0 0 0; line-height: 1.4;"></p>
        </div>

        <form method="POST" action="jobs.php">
            <input type="hidden" name="action" value="apply_job">
            <input type="hidden" name="job_id" id="apply_job_id" value="">
            
            <div class="form-group" style="margin-bottom: 1rem;">
                <label for="application_message" style="font-size: 0.88rem; font-weight: 700;">Cover Note / Motivation *</label>
                <textarea name="application_message" id="application_message" rows="3" class="form-control" placeholder="Introduce yourself, your academic background, and relevant experience..." required></textarea>
            </div>

            <p style="font-size: 0.78rem; color: var(--text-muted); margin-bottom: 1.2rem;">
                Your verified alumni profile details and contact email will be securely forwarded to the poster.
            </p>

            <div style="display: flex; gap: 0.8rem; justify-content: flex-end;">
                <button type="button" class="btn btn-outline-light" onclick="closeApplyModal();">Cancel</button>
                <button type="submit" class="btn btn-gold">Submit Application</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal for reporting job -->
<div id="reportModal" class="modal-overlay">
    <div class="modal-box">
        <h3 style="color: var(--text-light); margin-bottom: 0.5rem; font-weight: 800;">Report Job Vacancy</h3>
        <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.2rem;">
            Report fraudulent positions, expired listings, or inappropriate company posts.
        </p>

        <form method="POST" action="actions/report.php">
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
                <button type="button" class="btn btn-outline-light" onclick="closeReportModal();">Cancel</button>
                <button type="submit" class="btn btn-gold">Submit Report</button>
            </div>
        </form>
    </div>
</div>

<script>
function openApplyModalWithData(btn) {
    try {
        const job = JSON.parse(btn.dataset.job);
        document.getElementById('apply_job_id').value = job.id;
        document.getElementById('applyJobTitle').innerText = job.title || 'Apply for Position';
        document.getElementById('applyCompany').innerText = job.company || '';
        document.getElementById('applyLocation').innerText = '📍 ' + (job.location || 'Location not specified');
        document.getElementById('applyType').innerText = '💼 ' + (job.job_type || 'Full-Time');
        
        const dl = document.getElementById('applyDeadline');
        if (job.deadline) {
            dl.innerText = '⏱ Deadline: ' + job.deadline;
            dl.style.display = 'inline';
        } else {
            dl.style.display = 'none';
        }

        document.getElementById('applyDesc').innerText = job.description || 'No description provided.';
        
        const reqSection = document.getElementById('applyReqSection');
        if (job.requirements && job.requirements.trim()) {
            document.getElementById('applyReqs').innerText = job.requirements;
            reqSection.style.display = 'block';
        } else {
            reqSection.style.display = 'none';
        }

        document.getElementById('applyModal').style.display = 'flex';
    } catch (e) {
        console.error('Modal error:', e);
    }
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
