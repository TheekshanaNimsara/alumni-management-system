<?php
// ============================================================
// University Alumni Network - Job & Company Details (PHP Edition)
// Displays Full Position Specifications & Verified Company Profile
// ============================================================

require_once __DIR__ . '/config/db.php';

$jobId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$job = null;

if ($jobId > 0 && $pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                j.*,
                CONCAT(u.first_name, ' ', u.last_name) AS poster_name,
                u.email AS poster_email
            FROM jobs j
            LEFT JOIN users u ON j.posted_by = u.id
            WHERE j.id = :id
        ");
        $stmt->execute([':id' => $jobId]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $job = null;
    }
}

// Fallback to sample data only if exact ID matches a sample job
if (!$job && $jobId > 0) {
    $samples = get_sample_jobs();
    foreach ($samples as $s) {
        if ((int)$s['id'] === $jobId) {
            $job = $s;
            break;
        }
    }
}

// If job is pending or rejected, only creator or admin can view details
if ($job && isset($job['status']) && $job['status'] !== 'approved') {
    $isAdmin = is_admin();
    $currentUserId = current_user_id();
    $isOwner = $currentUserId && isset($job['posted_by']) && ((int)$job['posted_by'] === (int)$currentUserId);
    if (!$isAdmin && !$isOwner) {
        $job = null;
    }
}

$pageTitle = ($job ? htmlspecialchars($job['title']) : 'Opportunity Not Found') . ' — Alumni Network';
require_once __DIR__ . '/includes/header.php';
?>

<link rel="stylesheet" href="jobs.css?v=4.2">

<main class="content job-detail-wrap" style="padding-top: 2rem;">
    <!-- Breadcrumbs & Return Bar -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="index.php">Home</a>
            <span>&rsaquo;</span>
            <a href="jobs.php">Alumni Job Board</a>
            <span>&rsaquo;</span>
            <span class="current"><?php echo htmlspecialchars($job['title'] ?? 'Opportunity Not Found'); ?></span>
        </nav>
        <a href="jobs.php" class="btn btn-return-home btn-outline-glass btn-sm">&larr; Return to Job Board</a>
    </div>

    <?php if (!$job): ?>
    <section class="job-detail-hero" style="text-align: center; padding: 3.5rem 1.5rem; max-width: 680px; margin: 2rem auto;">
        <div style="font-size: 3rem; margin-bottom: 1rem;">&#128269;</div>
        <h1 style="color: #FFFFFF; font-size: 1.8rem; margin-bottom: 0.8rem;">Opportunity Not Found</h1>
        <p style="color: var(--text-muted); font-size: 0.95rem; line-height: 1.6; margin-bottom: 1.5rem;">
            The requested career posting could not be found, has expired, or is currently awaiting administrator moderation.
        </p>
        <a href="jobs.php" class="btn btn-primary" style="display: inline-flex; justify-content: center;">
            &larr; Return to Alumni Job Board
        </a>
    </section>
    <?php else: ?>
    <!-- Hero Header Card (Apple Liquid Glass) -->
    <section class="job-detail-hero" id="heroCard">
        <div class="job-hero-main">
            <div class="job-hero-logo" id="detailLogo">
                <?php echo strtoupper(substr($job['company'] ?? 'J', 0, 1)); ?>
            </div>
            <div class="job-hero-info">
                <h1 id="detailTitle"><?php echo htmlspecialchars($job['title'] ?? 'Career Opportunity'); ?></h1>
                <div class="job-hero-company">
                    <span id="detailCompany"><?php echo htmlspecialchars($job['company'] ?? 'Alumni Employer'); ?></span>
                    <span class="tag" style="background: rgba(16, 185, 129, 0.18); color: #10B981; border: 1px solid rgba(16, 185, 129, 0.35); font-size: 0.75rem; padding: 0.2rem 0.55rem; border-radius: 9999px;">
                        &#10004; Verified Alumni Employer
                    </span>
                </div>
                <div class="job-hero-tags">
                    <span class="tag" id="detailTypeTag"><?php echo htmlspecialchars($job['job_type'] ?? 'Full-Time'); ?></span>
                    <span class="tag" id="detailLocation" style="background: rgba(84, 25, 35, 0.4); border-color: rgba(216, 185, 106, 0.35); color: #E8CB83;">
                        &#128205; <?php echo htmlspecialchars($job['location'] ?? 'Colombo, Sri Lanka'); ?>
                    </span>
                    <?php if (!empty($job['deadline'])): ?>
                        <span class="tag" id="detailDeadline" style="background: rgba(239, 68, 68, 0.15); border-color: rgba(239, 68, 68, 0.30); color: #FCA5A5;">
                            &#9203; Deadline: <?php echo date('d M Y', strtotime($job['deadline'])); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="job-hero-actions">
            <?php if ($isLoggedIn): ?>
                <a href="#applySection" class="btn btn-primary" id="heroApplyBtn" style="justify-content: center; width: 100%;">
                    Apply for Position &rarr;
                </a>
            <?php else: ?>
                <a href="auth/login.php?redirect=<?php echo urlencode('../job-details.php?id=' . ($job['id'] ?? 1) . '#applySection'); ?>&msg=auth_required" class="btn btn-primary" id="heroApplyBtn" style="justify-content: center; width: 100%;">
                    Apply for Position &rarr;
                </a>
            <?php endif; ?>
            <?php if (!empty($job['application_link'])): ?>
                <a href="<?php echo htmlspecialchars($job['application_link']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-gold" id="heroPortalBtn" style="justify-content: center; width: 100%;">
                    Official Portal &rarr;
                </a>
            <?php endif; ?>
            <button type="button" class="btn btn-outline-glass" id="heroShareBtn" style="justify-content: center; width: 100%;">
                &#128279; Share Job
            </button>
        </div>
    </section>

    <!-- Two-Column Grid: Details & Company -->
    <div class="job-detail-grid">
        <!-- Left Column: Role & Requirements -->
        <div class="job-detail-left">
            <!-- Role Description -->
            <article class="job-detail-card">
                <h3>
                    <span style="color: var(--accent-light); font-size: 1.3rem;">&#9776;</span>
                    Role Description &amp; Scope
                </h3>
                <div id="detailDescription" style="white-space: pre-line; color: var(--text-color); font-size: 0.95rem; line-height: 1.7;">
                    <?php echo nl2br(htmlspecialchars($job['description'] ?? '')); ?>
                </div>
            </article>

            <!-- Qualifications & Requirements -->
            <?php if (!empty($job['requirements'])): ?>
                <article class="job-detail-card" id="requirementsCard">
                    <h3>
                        <span style="color: var(--accent-light); font-size: 1.3rem;">&#10003;</span>
                        Qualifications &amp; Technical Requirements
                    </h3>
                    <div id="detailRequirements" style="white-space: pre-line; color: var(--text-color); font-size: 0.95rem; line-height: 1.7;">
                        <?php echo nl2br(htmlspecialchars($job['requirements'])); ?>
                    </div>
                </article>
            <?php endif; ?>

            <!-- Perks & Opportunities -->
            <article class="job-detail-card">
                <h3>
                    <span style="color: var(--accent-light); font-size: 1.3rem;">&#9733;</span>
                    What This Opportunity Offers
                </h3>
                <ul class="perks-list">
                    <li>
                        <span class="perk-icon">&#128101;</span>
                        <span>Direct mentorship by senior university alumni</span>
                    </li>
                    <li>
                        <span class="perk-icon">&#128187;</span>
                        <span>Modern tech stack &amp; high engineering autonomy</span>
                    </li>
                    <li>
                        <span class="perk-icon">&#128218;</span>
                        <span>Professional certification &amp; learning budget</span>
                    </li>
                    <li>
                        <span class="perk-icon">&#127970;</span>
                        <span>Collaborative hybrid / on-site work culture</span>
                    </li>
                    <li>
                        <span class="perk-icon">&#128640;</span>
                        <span>Fast-track promotion &amp; leadership pathways</span>
                    </li>
                    <li>
                        <span class="perk-icon">&#127881;</span>
                        <span>Exclusive university alumni network events</span>
                    </li>
                </ul>
            </article>

            <!-- Application Box -->
            <article class="job-detail-card" id="applySection" style="border-top: 3px solid var(--accent-color);">
                <h3>
                    <span style="color: var(--accent-light); font-size: 1.3rem;">&#128233;</span>
                    Submit Your Application
                </h3>
                <p style="color: var(--text-muted); font-size: 0.92rem; margin-bottom: 1.2rem;">
                    Connect directly with the hiring team or apply through the verified alumni portal:
                </p>

                <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.5rem;">
                    <?php if (!empty($job['application_link'])): ?>
                        <a href="<?php echo htmlspecialchars($job['application_link']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary" id="directPortalBtn">
                            Apply on Official Career Portal &rarr;
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($job['contact_email'])): ?>
                        <a href="mailto:<?php echo htmlspecialchars($job['contact_email']); ?>?subject=<?php echo urlencode('Application for ' . $job['title'] . ' - Alumni Network'); ?>" class="btn btn-outline" id="directEmailBtn">
                            Apply via Email Client &rarr;
                        </a>
                        <button type="button" class="btn btn-outline-glass" id="copyEmailBtn">
                            &#128203; Copy Recruiter Email
                        </button>
                    <?php endif; ?>
                </div>

                <!-- In-Page Direct Alumni Application Form -->
                <?php if ($isLoggedIn): ?>
                    <div style="background: rgba(28, 23, 26, 0.85); border: 1px solid rgba(216, 185, 106, 0.22); border-radius: 10px; padding: 1.4rem;">
                        <h4 style="color: #FFFFFF; font-size: 1rem; margin-bottom: 0.4rem; font-weight: 700;">
                            Quick Alumni Application Note
                        </h4>
                        <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 1rem;">
                            Submit a direct note with your alumni graduation year and profile link directly to the employer recruiter.
                        </p>
                        <form id="quickApplyForm">
                            <div style="margin-bottom: 1rem;">
                                <label for="applicantName" style="display: block; font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.35rem; font-weight: 600;">
                                    Your Full Name
                                </label>
                                <input type="text" id="applicantName" class="form-control" value="<?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>" placeholder="e.g. Anuka Perera" required style="width: 100%; padding: 0.7rem 0.9rem; border: 1px solid var(--glass-border); border-radius: 8px; background: rgba(18, 3, 5, 0.85); color: #FFFFFF; font-family: inherit;">
                            </div>
                            <div style="margin-bottom: 1rem;">
                                <label for="applicantEmail" style="display: block; font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.35rem; font-weight: 600;">
                                    University / Contact Email
                                </label>
                                <input type="email" id="applicantEmail" class="form-control" value="<?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?>" placeholder="e.g. anuka.alumni@kdu.ac.lk" required style="width: 100%; padding: 0.7rem 0.9rem; border: 1px solid var(--glass-border); border-radius: 8px; background: rgba(18, 3, 5, 0.85); color: #FFFFFF; font-family: inherit;">
                            </div>
                            <div style="margin-bottom: 1.2rem;">
                                <label for="applicationMessage" style="display: block; font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.35rem; font-weight: 600;">
                                    Introduction &amp; Why You Are a Strong Fit (Include LinkedIn/GitHub/Portfolio)
                                </label>
                                <textarea id="applicationMessage" rows="4" class="form-control" placeholder="Introduce yourself, mention your faculty/intake, and share links to your resume, portfolio, or GitHub repository..." required style="width: 100%; padding: 0.7rem 0.9rem; border: 1px solid var(--glass-border); border-radius: 8px; background: rgba(18, 3, 5, 0.85); color: #FFFFFF; font-family: inherit; resize: vertical;"></textarea>
                            </div>
                            <button type="submit" class="btn btn-gold gold-glow" style="padding: 0.75rem 1.8rem; font-size: 0.95rem;">
                                Submit Verified Application
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <div style="background: rgba(28, 23, 26, 0.85); border: 1px solid rgba(216, 185, 106, 0.22); border-radius: 10px; padding: 1.8rem; text-align: center;">
                        <h4 style="color: #FFFFFF; font-size: 1.1rem; margin-bottom: 0.5rem; font-weight: 700;">
                            Sign In Required to Apply
                        </h4>
                        <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.3rem; max-width: 500px; margin-left: auto; margin-right: auto;">
                            To ensure verified alumni applications and protect our employer partner network, please sign in with your verified alumni account before submitting an application.
                        </p>
                        <a href="auth/login.php?redirect=<?php echo urlencode('../job-details.php?id=' . ($job['id'] ?? 1) . '#applySection'); ?>&msg=auth_required" class="btn btn-gold gold-glow">
                            Sign In to Apply
                        </a>
                    </div>
                <?php endif; ?>
            </article>
        </div>

        <!-- Right Column: Company Details & Overview -->
        <aside class="job-detail-right">
            <!-- Company Overview Card (Apple Liquid Glass) -->
            <article class="job-detail-card" style="position: sticky; top: 1.5rem;">
                <h3>
                    <span style="color: var(--accent-light); font-size: 1.3rem;">&#127970;</span>
                    Company Details
                </h3>

                <div style="margin-bottom: 1.25rem;">
                    <h4 id="companyNameSidebar" style="color: #FFFFFF; font-size: 1.15rem; font-weight: 800; margin-bottom: 0.35rem;">
                        <?php echo htmlspecialchars($job['company'] ?? 'Employer Partner'); ?>
                    </h4>
                    <p id="companyOverview" style="color: var(--text-muted); font-size: 0.88rem; line-height: 1.6; margin-bottom: 1.2rem;">
                        Leading technology enterprise and verified recruitment partner with the University Alumni Network.
                    </p>
                </div>

                <!-- Structured Company Stats -->
                <div style="background: rgba(18, 3, 5, 0.75); border: 1px solid rgba(216, 185, 106, 0.2); border-radius: 10px; padding: 0.8rem 1.1rem; margin-bottom: 1.5rem;">
                    <div class="company-stat-row">
                        <span class="company-stat-label">Industry</span>
                        <span class="company-stat-value" id="companyIndustry">Software Engineering</span>
                    </div>
                    <div class="company-stat-row">
                        <span class="company-stat-label">Headquarters</span>
                        <span class="company-stat-value" id="companyHq"><?php echo htmlspecialchars($job['location'] ?? 'Colombo, Sri Lanka'); ?></span>
                    </div>
                    <div class="company-stat-row">
                        <span class="company-stat-label">Company Size</span>
                        <span class="company-stat-value" id="companySize">Verified Partner</span>
                    </div>
                    <div class="company-stat-row">
                        <span class="company-stat-label">Alumni Network</span>
                        <span class="company-stat-value" style="color: #6EE7B7;">Active Partner</span>
                    </div>
                </div>

                <?php if (!empty($job['application_link'])): ?>
                    <div id="companyWebsiteWrap" style="margin-bottom: 1.5rem;">
                        <a href="<?php echo htmlspecialchars($job['application_link']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-glass" style="width: 100%; justify-content: center;">
                            Visit Official Website &rarr;
                        </a>
                    </div>
                <?php endif; ?>

                <!-- Posting Metadata -->
                <div style="border-top: 1px solid rgba(216, 185, 106, 0.2); padding-top: 1.2rem; font-size: 0.82rem; color: var(--text-muted);">
                    <p style="margin-bottom: 0.4rem;">
                        <strong>Date Posted:</strong> <span id="metaPostedDate"><?php echo !empty($job['posted_at']) ? date('d M Y', strtotime($job['posted_at'])) : 'Recently'; ?></span>
                    </p>
                    <p style="margin-bottom: 0.4rem;">
                        <strong>Application Closes:</strong> <span id="metaDeadlineDate"><?php echo !empty($job['deadline']) ? date('d M Y', strtotime($job['deadline'])) : 'Ongoing'; ?></span>
                    </p>
                    <p style="margin-bottom: 0;">
                        <strong>Verification:</strong> <span style="color: #6EE7B7;">KDU Alumni Career Board</span>
                    </p>
                </div>
            </article>
        </aside>
    </div>
    <?php endif; ?>
</main>

<?php if ($job): ?>
<script>
    window.INITIAL_JOB_DATA = <?php echo json_encode($job); ?>;
</script>
<script src="job-details.js?v=4.2"></script>
<?php endif; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
