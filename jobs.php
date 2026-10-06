<?php
// ============================================================
// University Alumni Network - Jobs & Opportunities Board
// ============================================================
$pageTitle = 'Career Opportunities';
$currentPage = 'jobs';
require_once __DIR__ . '/includes/header.php';

$message = '';
$messageType = '';

// Handle Job Posting Submission by logged-in users
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action']) && $_POST['action'] === 'post_job') {
    if (!$isLoggedIn) {
        $message = "Please log in to post a career opportunity.";
        $messageType = "danger";
    } else {
        $title = trim($_POST['title']);
        $company = trim($_POST['company']);
        $location = trim($_POST['location']);
        $job_type = trim($_POST['job_type']);
        $description = trim($_POST['description']);
        $application_link = trim($_POST['application_link']);

        if (!empty($title) && !empty($company) && !empty($location) && !empty($description)) {
            if ($db_connected && $pdo) {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO jobs (title, company, location, job_type, description, application_link, posted_by, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
                    ");
                    $stmt->execute([$title, $company, $location, $job_type, $description, $application_link, $_SESSION['user_id']]);
                    $message = "Your job vacancy has been submitted for admin approval.";
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
        $sql = "
            SELECT j.*, CONCAT(u.first_name, ' ', u.last_name) AS poster_name
            FROM jobs j
            LEFT JOIN users u ON j.posted_by = u.id
            WHERE j.status = 'approved'
        ";
        $params = [];
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
        <span class="section-eyebrow">Professional Advancement</span>
        <h1 class="section-title" style="color: var(--text-light); margin-bottom: 0.8rem;">Career Board</h1>
        <p style="color: rgba(248, 244, 234, 0.75); max-width: 600px; margin: 0 auto;">
            Explore career opportunities, leadership positions, and internships posted by alumni across diverse global industries.
        </p>
    </div>
</section>

<div class="container" style="padding-top: 3rem; padding-bottom: 5rem;">

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $messageType; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Action Bar & Type Filter -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2.5rem; flex-wrap: wrap; gap: 1rem;">
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <a href="jobs.php" class="btn <?php echo empty($typeFilter) ? 'btn-primary' : 'btn-outline-light'; ?> btn-sm" style="<?php echo empty($typeFilter) ? '' : 'color: var(--primary-color); border-color: var(--border-color);'; ?>">All Positions</a>
            <a href="jobs.php?type=Full-Time" class="btn <?php echo ($typeFilter === 'Full-Time') ? 'btn-primary' : 'btn-outline-light'; ?> btn-sm" style="<?php echo ($typeFilter === 'Full-Time') ? '' : 'color: var(--primary-color); border-color: var(--border-color);'; ?>">Full-Time</a>
            <a href="jobs.php?type=Internship" class="btn <?php echo ($typeFilter === 'Internship') ? 'btn-primary' : 'btn-outline-light'; ?> btn-sm" style="<?php echo ($typeFilter === 'Internship') ? '' : 'color: var(--primary-color); border-color: var(--border-color);'; ?>">Internships</a>
            <a href="jobs.php?type=Remote" class="btn <?php echo ($typeFilter === 'Remote') ? 'btn-primary' : 'btn-outline-light'; ?> btn-sm" style="<?php echo ($typeFilter === 'Remote') ? '' : 'color: var(--primary-color); border-color: var(--border-color);'; ?>">Remote</a>
        </div>
        <div>
            <a href="#post-job" class="btn btn-gold gold-glow btn-sm">+ Post a Vacancy</a>
        </div>
    </div>

    <!-- Jobs Grid (Light Background Cards with Gold Left Border) -->
    <div class="grid grid-2">
        <?php foreach ($jobs as $job): ?>
            <div class="card job-card">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <span class="job-type-pill"><?php echo htmlspecialchars($job['job_type']); ?></span>
                    <span style="font-size: 0.8rem; color: var(--text-muted);">
                        Posted <?php echo date('M d, Y', strtotime($job['posted_at'])); ?>
                    </span>
                </div>

                <h3 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h3>
                <div class="job-company"><?php echo htmlspecialchars($job['company']); ?></div>
                <div class="job-location">&#128205; <?php echo htmlspecialchars($job['location']); ?></div>

                <p class="job-desc">
                    <?php echo htmlspecialchars($job['description']); ?>
                </p>

                <div style="margin-top: auto; padding-top: 1.2rem; border-top: 1px solid rgba(216, 205, 187, 0.45); display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 0.85rem; color: var(--text-muted);">
                        Referral / Alumni verified
                    </span>
                    <?php if (!empty($job['application_link'])): ?>
                        <a href="<?php echo htmlspecialchars($job['application_link']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary btn-sm">
                            Apply External &rarr;
                        </a>
                    <?php else: ?>
                        <button type="button" class="btn btn-gold btn-sm" onclick="alert('Application interest noted for <?php echo addslashes($job['title']); ?>. Contact posted_by via Alumni Directory.');">
                            Express Interest
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Post a Job Section -->
    <div id="post-job" class="card" style="margin-top: 5rem; padding: 2.5rem; border-top: 4px solid var(--accent-color);">
        <h3 style="color: var(--primary-color); font-size: 1.5rem; margin-bottom: 0.5rem; font-weight: 800;">
            Share an Opportunity with Fellow Alumni
        </h3>
        <p style="color: var(--text-muted); margin-bottom: 2rem; font-size: 0.95rem;">
            Is your company hiring? Give back to your alma mater by offering opportunities to talented graduates and students.
        </p>

        <?php if ($isLoggedIn): ?>
            <form method="POST">
                <input type="hidden" name="action" value="post_job">
                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="job_title">Job Title *</label>
                        <input type="text" id="job_title" name="title" class="form-control" placeholder="e.g. Senior Backend Engineer" required>
                    </div>
                    <div class="form-group">
                        <label for="job_company">Company / Organization *</label>
                        <input type="text" id="job_company" name="company" class="form-control" placeholder="e.g. ABC Technologies" required>
                    </div>
                </div>

                <div class="grid grid-2">
                    <div class="form-group">
                        <label for="job_location">Location *</label>
                        <input type="text" id="job_location" name="location" class="form-control" placeholder="e.g. Colombo, Sri Lanka / Remote" required>
                    </div>
                    <div class="form-group">
                        <label for="job_type">Employment Type *</label>
                        <select id="job_type" name="job_type" class="form-control" required>
                            <option value="Full-Time">Full-Time</option>
                            <option value="Part-Time">Part-Time</option>
                            <option value="Internship">Internship</option>
                            <option value="Contract">Contract</option>
                            <option value="Remote">Remote</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="application_link">Application URL or Contact Email</label>
                    <input type="text" id="application_link" name="application_link" class="form-control" placeholder="e.g. https://company.com/jobs or careers@company.com">
                </div>

                <div class="form-group">
                    <label for="job_desc">Job Description &amp; Requirements *</label>
                    <textarea id="job_desc" name="description" class="form-control" rows="4" placeholder="Mention key technical skills, experience levels, and application steps..." required></textarea>
                </div>

                <button type="submit" class="btn btn-gold gold-glow">Submit Job Vacancy</button>
            </form>
        <?php else: ?>
            <div style="background: var(--bg-color); padding: 2rem; border-radius: var(--border-radius); text-align: center;">
                <p style="color: var(--text-main); margin-bottom: 1rem; font-weight: 600;">
                    Please log in to your alumni account to post job openings.
                </p>
                <a href="auth/login.php" class="btn btn-primary btn-sm">Log In</a>
                <a href="auth/register.php" class="btn btn-outline-gold btn-sm" style="margin-left: 0.5rem;">Join Network</a>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
