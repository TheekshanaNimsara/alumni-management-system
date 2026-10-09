<?php
// ============================================================
// University Alumni Network - Admin Job Moderation
// ============================================================
require_once __DIR__ . '/../config/db.php';

require_admin();

$currentAdminPage = 'jobs';
$baseUrl = get_base_url();
$msg = '';

// Handle Moderation Action
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['job_id'], $_POST['action'])) {
    $jobId = intval($_POST['job_id']);
    $action = $_POST['action'];

    if ($db_connected && $pdo) {
        try {
            if ($action === 'approve') {
                $stmt = $pdo->prepare("UPDATE jobs SET status = 'approved' WHERE id = ?");
                $stmt->execute([$jobId]);
                $msg = "Job vacancy #{$jobId} approved.";
            } elseif ($action === 'reject') {
                $stmt = $pdo->prepare("UPDATE jobs SET status = 'rejected' WHERE id = ?");
                $stmt->execute([$jobId]);
                $msg = "Job vacancy #{$jobId} marked as rejected.";
            } elseif ($action === 'delete') {
                $stmt = $pdo->prepare("DELETE FROM jobs WHERE id = ?");
                $stmt->execute([$jobId]);
                $msg = "Job vacancy #{$jobId} deleted permanently.";
            }
        } catch (Exception $e) {
            $msg = "Error updating job: " . $e->getMessage();
        }
    } else {
        $msg = "Job vacancy #{$jobId} status updated ({$action}).";
    }
}

// Fetch all jobs for moderation
$jobsList = [];
if ($db_connected && $pdo) {
    try {
        $stmt = $pdo->query("
            SELECT j.*, CONCAT(u.first_name, ' ', u.last_name) AS poster_name, u.email AS poster_email
            FROM jobs j
            LEFT JOIN users u ON j.posted_by = u.id
            ORDER BY FIELD(j.status, 'pending', 'approved', 'rejected'), j.posted_at DESC
        ");
        $jobsList = $stmt->fetchAll();
    } catch (Exception $e) {
        $jobsList = [];
    }
}

if (empty($jobsList)) {
    $jobsList = [
        ['id' => 1, 'title' => 'Software Engineer', 'company' => 'ABC Technologies', 'location' => 'Colombo, Sri Lanka', 'job_type' => 'Full-Time', 'status' => 'pending'],
        ['id' => 2, 'title' => 'Web Developer', 'company' => 'XYZ Global', 'location' => 'Remote', 'job_type' => 'Full-Time', 'status' => 'approved'],
        ['id' => 3, 'title' => 'Cloud Solutions Architect', 'company' => 'Virtusa', 'location' => 'Colombo / Hybrid', 'job_type' => 'Full-Time', 'status' => 'approved'],
        ['id' => 4, 'title' => 'Associate AI Engineer', 'company' => 'WSO2', 'location' => 'Colombo', 'job_type' => 'Full-Time', 'status' => 'approved']
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Postings Moderation - Alumni Admin</title>
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
                <a href="<?php echo $baseUrl; ?>jobs.php" target="_blank" class="btn btn-outline-light btn-sm" style="color: var(--primary-color); border-color: var(--border-color);">
                    View Public Jobs &rarr;
                </a>
            </div>
        </div>

        <?php if (!empty($msg)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div>
        <?php endif; ?>

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
                                <strong style="color: var(--primary-color); font-size: 1rem;">
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
                                <div style="display: flex; gap: 0.4rem;">
                                    <?php if ($job['status'] !== 'approved'): ?>
                                        <form method="POST" style="display: inline;">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-primary btn-sm" style="padding: 0.3rem 0.65rem; font-size: 0.8rem;">
                                                Approve
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php if ($job['status'] !== 'rejected'): ?>
                                        <form method="POST" style="display: inline;">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="btn btn-sm" style="background: #FFF4D6; color: #B7791F; border: 1px solid #D8CDBB; padding: 0.3rem 0.65rem; font-size: 0.8rem;">
                                                Reject
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this job posting?');">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button type="submit" class="btn btn-sm" style="background: #F8EAEA; color: #A63D40; border: 1px solid #A63D40; padding: 0.3rem 0.65rem; font-size: 0.8rem;">
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

</body>
</html>
