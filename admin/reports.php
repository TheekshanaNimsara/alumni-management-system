<?php
// ============================================================
// University Alumni Network - Admin Content & User Reports
// Review, moderate, resolve, or dismiss member reports
// ============================================================
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();

$currentPage = 'reports';
$pageTitle = 'Moderation Reports';
$message = '';
$messageType = '';

// Handle Moderation Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        $message = "Security token invalid.";
        $messageType = "danger";
    } else {
        $action = $_POST['action'] ?? '';
        $reportId = intval($_POST['report_id'] ?? 0);

        if ($reportId > 0 && $db_connected && $pdo) {
            try {
                if ($action === 'set_status') {
                    $newStatus = $_POST['new_status'] ?? 'reviewed';
                    if (in_array($newStatus, ['pending', 'reviewed', 'resolved', 'dismissed'])) {
                        $stmt = $pdo->prepare("UPDATE reports SET status = ? WHERE id = ?");
                        $stmt->execute([$newStatus, $reportId]);
                        $message = "Report #{$reportId} status updated to " . ucfirst($newStatus) . ".";
                        $messageType = "success";
                    }
                } elseif ($action === 'suspend_user') {
                    $targetUserId = intval($_POST['target_user_id'] ?? 0);
                    if ($targetUserId > 1) { // Do not suspend main admin
                        $stmt = $pdo->prepare("UPDATE users SET status = 'suspended' WHERE id = ?");
                        $stmt->execute([$targetUserId]);

                        // Auto-resolve report
                        $stmt = $pdo->prepare("UPDATE reports SET status = 'resolved' WHERE id = ?");
                        $stmt->execute([$reportId]);

                        $message = "User suspended and report #{$reportId} marked as resolved.";
                        $messageType = "warning";
                    }
                }
            } catch (Exception $e) {
                $message = "Action error: " . $e->getMessage();
                $messageType = "danger";
            }
        }
    }
}

// Filter
$statusFilter = trim($_GET['status'] ?? '');

$reports = [];
if ($db_connected && $pdo) {
    try {
        $sql = "
            SELECT r.*,
                   CONCAT(u_rep.first_name, ' ', u_rep.last_name) AS reporter_name,
                   u_rep.email AS reporter_email,
                   CONCAT(u_tgt.first_name, ' ', u_tgt.last_name) AS reported_user_name,
                   u_tgt.email AS reported_user_email,
                   u_tgt.status AS reported_user_status,
                   e.title AS event_title,
                   j.title AS job_title
            FROM reports r
            LEFT JOIN users u_rep ON r.reporter_id = u_rep.id
            LEFT JOIN users u_tgt ON r.reported_user_id = u_tgt.id
            LEFT JOIN events e ON r.event_id = e.id
            LEFT JOIN jobs j ON r.job_id = j.id
        ";
        $params = [];
        if (!empty($statusFilter)) {
            $sql .= " WHERE r.status = ?";
            $params[] = $statusFilter;
        }
        $sql .= " ORDER BY r.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $reports = $stmt->fetchAll();
    } catch (Exception $e) {
        $reports = [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Admin Console</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body style="background: var(--bg-color);">

    <div style="display: flex; min-height: 100vh;">
        
        <!-- Shared Admin Sidebar -->
        <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main style="flex: 1; padding: 2.5rem 3rem; overflow-y: auto;">
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h1 style="color: var(--primary-color); font-size: 1.8rem; font-weight: 800; margin: 0 0 0.4rem 0;">
                        Moderation & Content Reports
                    </h1>
                    <p style="color: var(--text-muted); font-size: 0.95rem; margin: 0;">
                        Review incident reports submitted by alumni and community members.
                    </p>
                </div>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <!-- Filter Tabs -->
            <div style="display: flex; gap: 0.5rem; margin-bottom: 1.5rem; flex-wrap: wrap;">
                <a href="reports.php" class="btn <?php echo empty($statusFilter) ? 'btn-gold' : 'btn-outline-light'; ?> btn-sm" style="<?php echo empty($statusFilter) ? '' : 'color: var(--text-color); border-color: var(--border-color);'; ?>">
                    All Reports
                </a>
                <a href="reports.php?status=pending" class="btn <?php echo ($statusFilter === 'pending') ? 'btn-gold' : 'btn-outline-light'; ?> btn-sm" style="<?php echo ($statusFilter === 'pending') ? '' : 'color: var(--text-color); border-color: var(--border-color);'; ?>">
                    Pending Review
                </a>
                <a href="reports.php?status=reviewed" class="btn <?php echo ($statusFilter === 'reviewed') ? 'btn-gold' : 'btn-outline-light'; ?> btn-sm" style="<?php echo ($statusFilter === 'reviewed') ? '' : 'color: var(--text-color); border-color: var(--border-color);'; ?>">
                    Under Review
                </a>
                <a href="reports.php?status=resolved" class="btn <?php echo ($statusFilter === 'resolved') ? 'btn-gold' : 'btn-outline-light'; ?> btn-sm" style="<?php echo ($statusFilter === 'resolved') ? '' : 'color: var(--text-color); border-color: var(--border-color);'; ?>">
                    Resolved
                </a>
                <a href="reports.php?status=dismissed" class="btn <?php echo ($statusFilter === 'dismissed') ? 'btn-gold' : 'btn-outline-light'; ?> btn-sm" style="<?php echo ($statusFilter === 'dismissed') ? '' : 'color: var(--text-color); border-color: var(--border-color);'; ?>">
                    Dismissed
                </a>
            </div>

            <!-- Reports Table -->
            <div class="card" style="padding: 0; overflow-x: auto;">
                <table class="table" style="margin: 0;">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Reported Subject</th>
                            <th>Reason & Description</th>
                            <th>Reported By</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Moderation Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reports)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 3rem; color: var(--text-muted);">
                                    No reports found matching the selected filter.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($reports as $r): ?>
                                <tr>
                                    <td><strong>#<?php echo $r['id']; ?></strong></td>
                                    <td>
                                        <?php if (!empty($r['reported_user_name'])): ?>
                                            <div>
                                                <strong>User: <?php echo htmlspecialchars($r['reported_user_name']); ?></strong>
                                                <div style="font-size: 0.8rem; color: var(--text-muted);"><?php echo htmlspecialchars($r['reported_user_email']); ?></div>
                                                <?php if (($r['reported_user_status'] ?? '') === 'suspended'): ?>
                                                    <span class="badge badge-danger" style="font-size: 0.72rem;">Currently Suspended</span>
                                                <?php endif; ?>
                                            </div>
                                        <?php elseif (!empty($r['event_title'])): ?>
                                            <div><strong>Event:</strong> <?php echo htmlspecialchars($r['event_title']); ?></div>
                                        <?php elseif (!empty($r['job_title'])): ?>
                                            <div><strong>Job:</strong> <?php echo htmlspecialchars($r['job_title']); ?></div>
                                        <?php elseif (!empty($r['message_id'])): ?>
                                            <div><strong>Direct Message #<?php echo $r['message_id']; ?></strong></div>
                                        <?php else: ?>
                                            <span style="color: var(--text-muted);">General Platform Item</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="max-width: 280px;">
                                        <div style="font-weight: 700; color: var(--danger-color); font-size: 0.9rem;">
                                            <?php echo htmlspecialchars($r['reason']); ?>
                                        </div>
                                        <?php if (!empty($r['description'])): ?>
                                            <div style="font-size: 0.84rem; color: var(--text-muted); margin-top: 0.2rem;">
                                                <?php echo htmlspecialchars($r['description']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div><?php echo htmlspecialchars($r['reporter_name'] ?? 'Alumnus'); ?></div>
                                        <div style="font-size: 0.78rem; color: var(--text-muted);"><?php echo htmlspecialchars($r['reporter_email'] ?? ''); ?></div>
                                    </td>
                                    <td style="font-size: 0.85rem; color: var(--text-muted);">
                                        <?php echo date('M d, Y', strtotime($r['created_at'])); ?>
                                    </td>
                                    <td>
                                        <?php if ($r['status'] === 'pending'): ?>
                                            <span class="badge badge-warning">Pending</span>
                                        <?php elseif ($r['status'] === 'reviewed'): ?>
                                            <span class="badge" style="background: var(--info-bg); color: var(--info-color);">Reviewed</span>
                                        <?php elseif ($r['status'] === 'resolved'): ?>
                                            <span class="badge badge-success">Resolved</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Dismissed</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 0.4rem; flex-wrap: wrap;">
                                            
                                            <!-- Status Update Form -->
                                            <?php if ($r['status'] !== 'resolved'): ?>
                                                <form method="POST" action="reports.php" style="display: inline;">
                                                    <?php echo csrf_field(); ?>
                                                    <input type="hidden" name="action" value="set_status">
                                                    <input type="hidden" name="report_id" value="<?php echo $r['id']; ?>">
                                                    <input type="hidden" name="new_status" value="resolved">
                                                    <button type="submit" class="btn btn-outline-gold btn-sm" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;" title="Mark Resolved">
                                                        Resolve
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <?php if ($r['status'] === 'pending'): ?>
                                                <form method="POST" action="reports.php" style="display: inline;">
                                                    <?php echo csrf_field(); ?>
                                                    <input type="hidden" name="action" value="set_status">
                                                    <input type="hidden" name="report_id" value="<?php echo $r['id']; ?>">
                                                    <input type="hidden" name="new_status" value="dismissed">
                                                    <button type="submit" class="btn btn-outline-light btn-sm" style="font-size: 0.75rem; padding: 0.25rem 0.5rem; color: var(--text-muted);" title="Dismiss Report">
                                                        Dismiss
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <!-- Suspend User Action if target is user and not suspended -->
                                            <?php if (!empty($r['reported_user_id']) && ($r['reported_user_status'] ?? '') !== 'suspended'): ?>
                                                <form method="POST" action="reports.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to suspend this user account?');">
                                                    <?php echo csrf_field(); ?>
                                                    <input type="hidden" name="action" value="suspend_user">
                                                    <input type="hidden" name="report_id" value="<?php echo $r['id']; ?>">
                                                    <input type="hidden" name="target_user_id" value="<?php echo $r['reported_user_id']; ?>">
                                                    <button type="submit" class="btn btn-sm" style="background: var(--danger-color); color: #fff; font-size: 0.75rem; padding: 0.25rem 0.5rem;">
                                                        Suspend User
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </main>
    </div>

</body>
</html>
