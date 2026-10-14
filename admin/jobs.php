<?php
// ============================================================
// University Alumni Network - Admin Job Moderation
// ============================================================
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();

$currentAdminPage = 'jobs';
$baseUrl = get_base_url();
$msg = '';

if (!empty($_SESSION['flash_msg'])) {
    $msg = $_SESSION['flash_msg'];
    unset($_SESSION['flash_msg']);
}

// Status Filter
$statusFilter = trim($_GET['status'] ?? '');

// Handle Moderation Action
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['job_id'], $_POST['action'])) {
    $jobId = intval($_POST['job_id']);
    $action = $_POST['action'];
    if ($db_connected && $pdo) {
        try {
            if ($action === 'approve') {
                $stmt = $pdo->prepare("UPDATE jobs SET status = 'approved' WHERE id = ?");
                $stmt->execute([$jobId]);
                $_SESSION['flash_msg'] = "Job vacancy #{$jobId} approved successfully.";
            } elseif ($action === 'reject') {
                $stmt = $pdo->prepare("UPDATE jobs SET status = 'rejected' WHERE id = ?");
                $stmt->execute([$jobId]);
                $_SESSION['flash_msg'] = "Job vacancy #{$jobId} marked as rejected.";
            } elseif ($action === 'delete') {
                $stmt = $pdo->prepare("DELETE FROM jobs WHERE id = ?");
                $stmt->execute([$jobId]);
                $_SESSION['flash_msg'] = "Job vacancy #{$jobId} deleted permanently.";
            }
        } catch (Exception $e) {
            $_SESSION['flash_msg'] = "Error updating job: " . $e->getMessage();
        }
    } else {
        $_SESSION['flash_msg'] = "Job vacancy #{$jobId} status updated ({$action}).";
    }

    header("Location: jobs.php" . (!empty($statusFilter) ? "?status=" . urlencode($statusFilter) : ""));
    exit;
}

// Fetch all jobs for moderation
$jobsList = [];
if ($db_connected && $pdo) {
    try {
        $sql = "
            SELECT j.*, CONCAT(u.first_name, ' ', u.last_name) AS poster_name, u.email AS poster_email
            FROM jobs j
            LEFT JOIN users u ON j.posted_by = u.id
        ";
        $params = [];
        if (!empty($statusFilter)) {
            $sql .= " WHERE j.status = ?";
            $params[] = $statusFilter;
        }
        $sql .= " ORDER BY FIELD(j.status, 'pending', 'approved', 'rejected'), j.posted_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $jobsList = $stmt->fetchAll();
    } catch (Exception $e) {
        $jobsList = [];
    }
}

// Fallback dummy records (reduced to two examples)
if (empty($jobsList) && empty($statusFilter)) {
    $jobsList = [
        ['id' => 1, 'title' => 'Software Engineer', 'company' => 'ABC Technologies', 'location' => 'Colombo, Sri Lanka', 'job_type' => 'Full-Time', 'status' => 'pending'],
        ['id' => 2, 'title' => 'Web Developer', 'company' => 'XYZ Global', 'location' => 'Remote', 'job_type' => 'Full-Time', 'status' => 'approved']
    ];
} elseif (empty($jobsList) && $statusFilter === 'pending') {
    $jobsList = [
        ['id' => 1, 'title' => 'Software Engineer', 'company' => 'ABC Technologies', 'location' => 'Colombo, Sri Lanka', 'job_type' => 'Full-Time', 'status' => 'pending']
    ];
} elseif (empty($jobsList) && $statusFilter === 'approved') {
    $jobsList = [
        ['id' => 2, 'title' => 'Web Developer', 'company' => 'XYZ Global', 'location' => 'Remote', 'job_type' => 'Full-Time', 'status' => 'approved']
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Postings Moderation - Alumni Admin</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo $baseUrl; ?>assets/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo $baseUrl; ?>assets/favicon-16x16.png">
    <link rel="shortcut icon" href="<?php echo $baseUrl; ?>favicon.ico">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo $baseUrl; ?>assets/apple-touch-icon.png">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/css/style.css">
</head>
<body>

<div class="admin-layout">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <main class="admin-main">
        <div class="admin-header">
            <div>
                <h1 class="admin-title">Job Postings Moderation</h1>
                <p style="color: var(--text-muted); font-size: 0.95rem;">Review employer vacancies and verify career opportunities</p>
            </div>
            <div>
                <a href="<?php echo $baseUrl; ?>jobs.php" target="_blank" class="btn btn-outline-light btn-sm" style="color: var(--text-light); border-color: var(--border-color);">
                    View Public Jobs &rarr;
                </a>
            </div>
        </div>

        <?php if (!empty($msg)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div>
        <?php endif; ?>

        <!-- Status Filter Tabs -->
        <div style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
            <a href="jobs.php" class="btn <?php echo empty($statusFilter) ? 'btn-gold' : 'btn-outline-light'; ?> btn-sm" style="<?php echo empty($statusFilter) ? '' : 'color: var(--text-color); border-color: var(--border-color);'; ?>">
                All Jobs
            </a>
            <a href="jobs.php?status=pending" class="btn <?php echo ($statusFilter === 'pending') ? 'btn-gold' : 'btn-outline-light'; ?> btn-sm" style="<?php echo ($statusFilter === 'pending') ? '' : 'color: var(--text-color); border-color: var(--border-color);'; ?>">
                Pending Review
            </a>
            <a href="jobs.php?status=approved" class="btn <?php echo ($statusFilter === 'approved') ? 'btn-gold' : 'btn-outline-light'; ?> btn-sm" style="<?php echo ($statusFilter === 'approved') ? '' : 'color: var(--text-color); border-color: var(--border-color);'; ?>">
                Approved
            </a>
            <a href="jobs.php?status=rejected" class="btn <?php echo ($statusFilter === 'rejected') ? 'btn-gold' : 'btn-outline-light'; ?> btn-sm" style="<?php echo ($statusFilter === 'rejected') ? '' : 'color: var(--text-color); border-color: var(--border-color);'; ?>">
                Rejected
            </a>
        </div>

        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Job Title</th>
                        <th>Company</th>
                        <th>Type &amp; Location</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($jobsList as $job): ?>
                        <tr>
                            <td>
                                <strong style="color: var(--text-light); font-size: 1rem;">
                                    <?php echo htmlspecialchars($job['title']); ?>
                                </strong>
                            </td>
                            <td><?php echo htmlspecialchars($job['company']); ?></td>
                            <td>
                                <div><?php echo htmlspecialchars($job['job_type']); ?></div>
                                <div style="font-size: 0.8rem; color: var(--text-muted);"><?php echo htmlspecialchars($job['location']); ?></div>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo strtolower($job['status']); ?>">
                                    <?php echo ucfirst($job['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.4rem; align-items: center; flex-wrap: wrap;">
                                    <button type="button" class="btn btn-outline-glass btn-sm"
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
                                                "application_link" => $job["application_link"] ?? "",
                                                "status" => $job["status"] ?? "pending",
                                                "poster_name" => $job["poster_name"] ?? "Alumnus",
                                                "poster_email" => $job["poster_email"] ?? ""
                                            ]), ENT_QUOTES, "UTF-8"); ?>'
                                            onclick="openJobReviewModal(this);"
                                            style="padding: 0.3rem 0.65rem; font-size: 0.8rem;">
                                        Review
                                    </button>

                                    <?php if ($job['status'] !== 'approved'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-glass-success btn-sm">
                                                Approve
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($job['status'] !== 'rejected'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="btn btn-glass-danger btn-sm">
                                                Reject
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this job posting permanently?');">
                                        <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button type="submit" class="btn btn-glass-danger btn-sm">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<!-- Admin Job Review Modal -->
<div id="jobReviewModal" class="modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(10, 2, 4, 0.78); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); z-index: 9999; align-items: center; justify-content: center; padding: 1rem;">
    <div class="modal-box" style="max-width: 650px; width: 100%; max-height: 90vh; overflow-y: auto; background: linear-gradient(145deg, rgba(28, 23, 26, 0.96) 0%, rgba(18, 3, 5, 0.98) 100%); border: 1px solid rgba(216, 185, 106, 0.28); border-top: 4px solid var(--accent-color); border-radius: 12px; padding: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.5rem;">
            <div>
                <span id="reviewJobBadge" class="badge badge-pending" style="font-size: 0.75rem; margin-bottom: 0.4rem; display: inline-block;">Pending</span>
                <h3 id="reviewJobTitle" style="color: var(--text-light); margin-bottom: 0.2rem; font-weight: 800; font-size: 1.4rem;">Job Specifications</h3>
                <p id="reviewJobCompany" style="color: var(--accent-light); font-weight: 600; margin: 0; font-size: 0.95rem;"></p>
            </div>
            <button type="button" onclick="closeJobReviewModal();" style="background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(216, 185, 106, 0.2); color: var(--text-light); width: 32px; height: 32px; border-radius: 50%; cursor: pointer; font-size: 1.2rem; display: flex; align-items: center; justify-content: center;">&times;</button>
        </div>

        <div style="display: flex; flex-wrap: wrap; gap: 1rem; font-size: 0.85rem; color: var(--text-muted); padding: 0.6rem 0; border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color); margin-bottom: 1rem;">
            <span id="reviewJobLocation">&#128205; </span>
            <span id="reviewJobType">&#128188; </span>
            <span id="reviewJobDeadline" style="color: var(--danger-color);">&#9203; </span>
        </div>

        <div style="font-size: 0.82rem; color: var(--text-secondary); margin-bottom: 1.2rem; padding: 0.5rem 0.8rem; background: rgba(0,0,0,0.3); border-radius: 4px;">
            <span id="reviewJobPoster">Posted by: Alumnus</span>
        </div>

        <div style="margin-bottom: 1.2rem;">
            <h5 style="color: var(--accent-light); font-size: 0.82rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 0.3rem;">Role Description</h5>
            <p id="reviewJobDesc" style="color: var(--text-color); font-size: 0.9rem; line-height: 1.5; margin: 0; max-height: 140px; overflow-y: auto; white-space: pre-wrap;"></p>
        </div>

        <div id="reviewJobReqSection" style="margin-bottom: 1.2rem; background: rgba(90, 26, 43, 0.35); border: 1px solid var(--border-color); border-radius: 6px; padding: 0.75rem 0.9rem; display: none;">
            <strong style="color: var(--accent-light); font-size: 0.84rem;">Candidate Requirements:</strong>
            <p id="reviewJobReqs" style="color: var(--text-color); font-size: 0.86rem; margin: 0.3rem 0 0 0; line-height: 1.4; white-space: pre-wrap;"></p>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-color); padding-top: 1.2rem; margin-top: 1rem; flex-wrap: wrap; gap: 0.8rem;">
            <button type="button" class="btn btn-outline-light btn-sm" onclick="closeJobReviewModal();">Close</button>
            <div style="display: flex; gap: 0.5rem;">
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="job_id" id="modalJobIdApprove" value="">
                    <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                    <input type="hidden" name="action" value="approve">
                    <button type="submit" class="btn btn-glass-success btn-sm">Approve Job</button>
                </form>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="job_id" id="modalJobIdReject" value="">
                    <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                    <input type="hidden" name="action" value="reject">
                    <button type="submit" class="btn btn-glass-danger btn-sm">Reject Job</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function openJobReviewModal(btn) {
    try {
        const job = JSON.parse(btn.dataset.job);
        document.getElementById('reviewJobTitle').innerText = job.title || 'Job Specifications';
        document.getElementById('reviewJobCompany').innerText = job.company || '';
        document.getElementById('reviewJobLocation').innerHTML = '&#128205; ' + (job.location || 'Not specified');
        document.getElementById('reviewJobType').innerHTML = '&#128188; ' + (job.job_type || 'Full-Time');
        document.getElementById('reviewJobDeadline').innerHTML = job.deadline ? ('&#9203; Deadline: ' + job.deadline) : 'No deadline set';
        document.getElementById('reviewJobPoster').innerText = 'Posted by: ' + (job.poster_name || 'Alumnus') + (job.poster_email ? (' (' + job.poster_email + ')') : '');
        document.getElementById('reviewJobDesc').innerText = job.description || 'No description provided.';

        const reqSection = document.getElementById('reviewJobReqSection');
        if (job.requirements && job.requirements.trim()) {
            document.getElementById('reviewJobReqs').innerText = job.requirements;
            reqSection.style.display = 'block';
        } else {
            reqSection.style.display = 'none';
        }

        const badge = document.getElementById('reviewJobBadge');
        if (badge) {
            badge.className = 'badge badge-' + (job.status || 'pending').toLowerCase();
            badge.innerText = (job.status || 'Pending').toUpperCase();
        }

        document.getElementById('modalJobIdApprove').value = job.id;
        document.getElementById('modalJobIdReject').value = job.id;

        document.getElementById('jobReviewModal').style.display = 'flex';
    } catch(e) {
        console.error('Error opening job modal:', e);
    }
}
function closeJobReviewModal() {
    document.getElementById('jobReviewModal').style.display = 'none';
}
</script>

<script src="<?php echo $baseUrl; ?>assets/js/main.js"></script>
</body>
</html>
