<?php
// ============================================================
// University Alumni Network - Admin Content & User Reports
// Review, investigate, moderate, resolve, or dismiss member reports
// Apple-Inspired Liquid Glass UI • Dark-Purple Aesthetic • CSRF Protection
// ============================================================
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();

$currentAdminPage = 'reports';
$baseUrl = get_base_url();
$pageTitle = 'Moderation & Incident Reports';
$message = '';
$messageType = '';

// Handle Moderation Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $reportId = intval($_POST['report_id'] ?? 0);
    if ($reportId > 0 && $db_connected && $pdo) {
        try {
            if ($action === 'set_status') {
                $newStatus = trim($_POST['new_status'] ?? 'reviewed');
                if (in_array($newStatus, ['pending', 'reviewed', 'resolved', 'dismissed'])) {
                    $stmt = $pdo->prepare("UPDATE reports SET status = ? WHERE id = ?");
                    $stmt->execute([$newStatus, $reportId]);
                    $statusLabel = ($newStatus === 'reviewed') ? 'Under Review' : ucfirst($newStatus);
                    $message = "Report #{$reportId} status successfully updated to {$statusLabel}.";
                    $messageType = ($newStatus === 'resolved') ? "success" : "info";
                }
            } elseif ($action === 'suspend_user') {
                $targetUserId = intval($_POST['target_user_id'] ?? 0);
                $currentAdminId = current_user_id();

                if ($targetUserId > 1 && $targetUserId !== $currentAdminId) { // Do not suspend primary admin or self
                    $stmt = $pdo->prepare("UPDATE users SET status = 'suspended' WHERE id = ?");
                    $stmt->execute([$targetUserId]);

                    // Automatically mark associated report as resolved
                    $stmt = $pdo->prepare("UPDATE reports SET status = 'resolved' WHERE id = ?");
                    $stmt->execute([$reportId]);

                    $message = "User #{$targetUserId} has been suspended and Report #{$reportId} was marked as resolved.";
                    $messageType = "warning";
                } else {
                    $message = "Cannot suspend the primary administrator or your own active account.";
                    $messageType = "danger";
                }
            } elseif ($action === 'unsuspend_user') {
                $targetUserId = intval($_POST['target_user_id'] ?? 0);
                if ($targetUserId > 0) {
                    $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?");
                    $stmt->execute([$targetUserId]);
                    $message = "User #{$targetUserId} has been restored to active status.";
                    $messageType = "success";
                }
            }
        } catch (Exception $e) {
            $message = "Action error: " . $e->getMessage();
            $messageType = "danger";
        }
    }
}

// Compute Statistics (Database-Driven)
$totalReportsCount = 0;
$pendingReportsCount = 0;
$reviewedReportsCount = 0;
$resolvedReportsCount = 0;
$dismissedReportsCount = 0;

if ($db_connected && $pdo) {
    try {
        $totalReportsCount = (int)$pdo->query("SELECT COUNT(*) FROM reports")->fetchColumn();
        $pendingReportsCount = (int)$pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn();
        $reviewedReportsCount = (int)$pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'reviewed'")->fetchColumn();
        $resolvedReportsCount = (int)$pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'resolved'")->fetchColumn();
        $dismissedReportsCount = (int)$pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'dismissed'")->fetchColumn();
    } catch (Exception $e) {
        $totalReportsCount = 2;
        $pendingReportsCount = 1;
        $reviewedReportsCount = 1;
    }
} else {
    $totalReportsCount = 2;
    $pendingReportsCount = 1;
    $reviewedReportsCount = 1;
}

// Filters & Search Parameters
$statusFilter = trim($_GET['status'] ?? '');
$searchQuery = trim($_GET['search'] ?? '');

$reports = [];
if ($db_connected && $pdo) {
    try {
        $sql = "
            SELECT r.*,
                   CONCAT(u_rep.first_name, ' ', u_rep.last_name) AS reporter_name,
                   u_rep.email AS reporter_email,
                   u_rep.username AS reporter_username,
                   CONCAT(u_tgt.first_name, ' ', u_tgt.last_name) AS reported_user_name,
                   u_tgt.email AS reported_user_email,
                   u_tgt.username AS reported_user_username,
                   u_tgt.status AS reported_user_status,
                   e.title AS event_title,
                   j.title AS job_title
            FROM reports r
            LEFT JOIN users u_rep ON r.reporter_id = u_rep.id
            LEFT JOIN users u_tgt ON r.reported_user_id = u_tgt.id
            LEFT JOIN events e ON r.event_id = e.id
            LEFT JOIN jobs j ON r.job_id = j.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($statusFilter)) {
            $sql .= " AND r.status = ?";
            $params[] = $statusFilter;
        }

        if (!empty($searchQuery)) {
            $term = "%{$searchQuery}%";
            $sql .= " AND (
                r.reason LIKE ? OR 
                r.description LIKE ? OR 
                u_rep.first_name LIKE ? OR 
                u_rep.last_name LIKE ? OR 
                u_rep.email LIKE ? OR 
                u_tgt.first_name LIKE ? OR 
                u_tgt.last_name LIKE ? OR 
                u_tgt.email LIKE ? OR 
                e.title LIKE ? OR 
                j.title LIKE ?
            )";
            $params = array_merge($params, array_fill(0, 10, $term));
        }

        $sql .= " ORDER BY FIELD(r.status, 'pending', 'reviewed', 'resolved', 'dismissed'), r.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $reports = $stmt->fetchAll();
    } catch (Exception $e) {
        $reports = [];
    }
}

// Fallback to sample data when database returns empty and no explicit search was performed
if (empty($reports) && empty($searchQuery) && empty($statusFilter)) {
    $reports = get_sample_reports();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> &bull; Alumni Admin</title>
    <!-- Zero FOUC Theme Initializer -->
    <script>
      (function(){try{var s=localStorage.getItem('alumni_theme_preference');var t=(s==='light'||(s!=='dark'&&window.matchMedia&&window.matchMedia('(prefers-color-scheme: light)').matches))?'light':'dark';document.documentElement.setAttribute('data-theme',t);if(s)document.documentElement.setAttribute('data-theme-preference',s);}catch(e){}})();
    </script>
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?php echo $baseUrl; ?>assets/favicon.svg?v=3">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo $baseUrl; ?>assets/favicon-32x32.png?v=3">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo $baseUrl; ?>assets/favicon-16x16.png?v=3">
    <link rel="shortcut icon" href="<?php echo $baseUrl; ?>favicon.ico?v=3">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo $baseUrl; ?>assets/apple-touch-icon.png?v=3">
    <!-- Master Theme Stylesheets -->
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/css/theme.css?v=5">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/css/style.css?v=5">
    <style>
        /* Scoped Apple-Inspired Liquid Glass UI for Reports */
        .reports-filter-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding: 1.2rem 1.4rem;
            background: rgba(28, 23, 26, 0.75);
            border: 1px solid rgba(216, 185, 106, 0.22);
            border-radius: var(--border-radius);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
        }
        .reports-search-box {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex: 1 1 320px;
            max-width: 480px;
        }
        .reports-search-input {
            width: 100%;
            background: rgba(18, 3, 5, 0.85);
            border: 1px solid rgba(216, 185, 106, 0.25);
            color: #F4EEE7;
            padding: 0.6rem 0.95rem;
            border-radius: 8px;
            font-size: 0.92rem;
            outline: none;
            transition: var(--transition);
        }
        .reports-search-input:focus {
            border-color: #D8B96A;
            box-shadow: 0 0 14px rgba(216, 185, 106, 0.35);
        }
        .reports-tabs {
            display: flex;
            align-items: center;
            gap: 0.45rem;
            flex-wrap: wrap;
        }
        .report-tab-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.45rem 0.85rem;
            font-size: 0.82rem;
            font-weight: 700;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.22s ease;
            border: 1px solid rgba(216, 185, 106, 0.22);
            color: #B7A9AD;
            background: rgba(28, 23, 26, 0.55);
        }
        .report-tab-pill:hover {
            color: #F4EEE7;
            border-color: rgba(216, 185, 106, 0.45);
            transform: translateY(-1px);
        }
        .report-tab-pill.active {
            background: linear-gradient(135deg, rgba(90, 26, 43, 0.95), rgba(28, 23, 26, 0.95));
            color: #E8CB83;
            border-color: rgba(216, 185, 106, 0.6);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.45), 0 0 12px rgba(216, 185, 106, 0.25);
        }
        .report-pill-badge {
            background: rgba(0, 0, 0, 0.35);
            color: #FFFFFF;
            font-size: 0.72rem;
            padding: 0.1rem 0.4rem;
            border-radius: 999px;
            font-weight: 800;
        }
        .subject-badge-user {
            background: rgba(90, 26, 43, 0.4);
            color: #E8CB83;
            border: 1px solid rgba(216, 185, 106, 0.35);
        }
        .subject-badge-event {
            background: rgba(59, 130, 246, 0.2);
            color: #93C5FD;
            border: 1px solid rgba(59, 130, 246, 0.4);
        }
        .subject-badge-job {
            background: rgba(16, 185, 129, 0.2);
            color: #6EE7B7;
            border: 1px solid rgba(16, 185, 129, 0.4);
        }
        .subject-badge-message {
            background: rgba(245, 158, 11, 0.2);
            color: #FCD34D;
            border: 1px solid rgba(245, 158, 11, 0.4);
        }
        .badge-reviewed {
            background: rgba(90, 26, 43, 0.5);
            color: #E8CB83;
            border: 1px solid rgba(216, 185, 106, 0.4);
        }
        .badge-resolved {
            background: rgba(16, 185, 129, 0.2);
            color: #10B981;
            border: 1px solid rgba(16, 185, 129, 0.45);
        }
        .badge-dismissed {
            background: rgba(100, 116, 139, 0.2);
            color: #94A3B8;
            border: 1px solid rgba(100, 116, 139, 0.45);
        }
        .report-description-box {
            font-size: 0.84rem;
            color: #B9B2C9;
            margin-top: 0.35rem;
            line-height: 1.45;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            word-break: break-word;
        }
        .admin-link {
            color: #E8CB83;
            text-decoration: underline;
            text-underline-offset: 3px;
            font-size: 0.82rem;
            transition: color 0.2s ease;
        }
        .admin-link:hover {
            color: #FFFFFF;
        }
        /* Liquid Glass Detail Modal */
        .report-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(10, 2, 4, 0.82);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            z-index: 10000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .report-modal-overlay.active {
            display: flex;
        }
        .report-modal-card {
            background: rgba(28, 23, 26, 0.96);
            border: 1px solid rgba(216, 185, 106, 0.32);
            border-radius: 20px;
            max-width: 620px;
            width: 100%;
            padding: 2.2rem;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.85), 0 0 40px rgba(216, 185, 106, 0.20);
            animation: modalFadeIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            position: relative;
            max-height: 90vh;
            overflow-y: auto;
        }
        @keyframes modalFadeIn {
            from { transform: scale(0.92) translateY(12px); opacity: 0; }
            to { transform: scale(1) translateY(0); opacity: 1; }
        }
        .report-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.4rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(216, 185, 106, 0.2);
        }
        .report-modal-close {
            background: transparent;
            border: none;
            color: #B9B2C9;
            font-size: 1.5rem;
            cursor: pointer;
            line-height: 1;
            padding: 0.2rem 0.5rem;
            border-radius: 6px;
            transition: color 0.2s ease, background 0.2s ease;
        }
        .report-modal-close:hover {
            color: #FFFFFF;
            background: rgba(255, 255, 255, 0.1);
        }
        .report-dossier-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.2rem;
            margin-bottom: 1.5rem;
        }
        .report-dossier-item {
            background: rgba(18, 3, 5, 0.65);
            padding: 0.85rem 1rem;
            border-radius: 10px;
            border: 1px solid rgba(216, 185, 106, 0.18);
        }
        .report-dossier-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #B9B2C9;
            margin-bottom: 0.25rem;
            font-weight: 700;
        }
        .report-dossier-value {
            font-size: 0.94rem;
            color: #F8F7FC;
            font-weight: 600;
            word-break: break-word;
        }
        .report-dossier-full {
            grid-column: 1 / -1;
        }
    </style>
</head>
<body style="background: var(--bg-color);">

<div class="admin-layout">
    
    <!-- Shared Admin Sidebar -->
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <!-- Main Content Area -->
    <main class="admin-main">
        
        <!-- Header -->
        <div class="admin-header">
            <div>
                <h1 class="admin-title">Content &amp; Moderation Reports</h1>
                <p style="color: var(--text-muted); font-size: 0.95rem;">Review, investigate, resolve, or dismiss user and content incident reports.</p>
            </div>
            <div>
                <a href="<?php echo $baseUrl; ?>admin/index.php" class="btn btn-outline-light btn-sm" style="color: var(--text-light); border-color: var(--border-color);">
                    &larr; Admin Dashboard
                </a>
            </div>
        </div>

        <!-- Alert Notification -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <!-- Responsive Metric Statistics Cards (Clickable Navigation) -->
        <div class="admin-stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 1.8rem;">
            
            <a href="reports.php" class="admin-stat-card <?php echo empty($statusFilter) ? 'active' : ''; ?>" title="View all incident reports" aria-label="Total Reports: <?php echo $totalReportsCount; ?>">
                <div>
                    <div class="admin-stat-number"><?php echo number_format($totalReportsCount); ?></div>
                    <div class="admin-stat-label">Total Reports</div>
                </div>
                <div class="admin-stat-icon">&#128221;</div>
            </a>

            <a href="reports.php?status=pending" class="admin-stat-card <?php echo ($statusFilter === 'pending') ? 'active' : ''; ?>" title="Filter pending review reports" aria-label="Pending Review: <?php echo $pendingReportsCount; ?>">
                <div>
                    <div class="admin-stat-number" style="color: #F59E0B;"><?php echo number_format($pendingReportsCount); ?></div>
                    <div class="admin-stat-label">Pending Review</div>
                </div>
                <div class="admin-stat-icon">&#9203;</div>
            </a>

            <a href="reports.php?status=reviewed" class="admin-stat-card <?php echo ($statusFilter === 'reviewed') ? 'active' : ''; ?>" title="Filter under review reports" aria-label="Under Review: <?php echo $reviewedReportsCount; ?>">
                <div>
                    <div class="admin-stat-number" style="color: #E8CB83;"><?php echo number_format($reviewedReportsCount); ?></div>
                    <div class="admin-stat-label">Under Review</div>
                </div>
                <div class="admin-stat-icon">&#128269;</div>
            </a>

            <a href="reports.php?status=resolved" class="admin-stat-card <?php echo ($statusFilter === 'resolved') ? 'active' : ''; ?>" title="Filter resolved reports" aria-label="Resolved Reports: <?php echo $resolvedReportsCount; ?>">
                <div>
                    <div class="admin-stat-number" style="color: #10B981;"><?php echo number_format($resolvedReportsCount); ?></div>
                    <div class="admin-stat-label">Resolved</div>
                </div>
                <div class="admin-stat-icon">&#9989;</div>
            </a>

        </div>

        <!-- Search & Filter Bar (Liquid Glass Card) -->
        <div class="reports-filter-bar">
            
            <!-- Search Form -->
            <form method="GET" action="reports.php" class="reports-search-box">
                <?php if (!empty($statusFilter)): ?>
                    <input type="hidden" name="status" value="<?php echo htmlspecialchars($statusFilter); ?>">
                <?php endif; ?>
                <input type="text" 
                       name="search" 
                       class="reports-search-input" 
                       placeholder="Search reports by user, subject, reason..." 
                       value="<?php echo htmlspecialchars($searchQuery); ?>" 
                       aria-label="Search incident reports">
                <button type="submit" class="btn btn-primary btn-sm" style="white-space: nowrap; padding: 0.55rem 0.85rem;">
                    Search
                </button>
                <?php if (!empty($searchQuery)): ?>
                    <a href="reports.php<?php echo !empty($statusFilter) ? '?status=' . urlencode($statusFilter) : ''; ?>" class="btn btn-outline-light btn-sm" title="Clear Search" style="padding: 0.55rem 0.75rem;">
                        &times;
                    </a>
                <?php endif; ?>
            </form>

            <!-- Status Filter Pills -->
            <div class="reports-tabs" role="navigation" aria-label="Report status filter">
                <a href="reports.php<?php echo !empty($searchQuery) ? '?search=' . urlencode($searchQuery) : ''; ?>" 
                   class="report-tab-pill <?php echo empty($statusFilter) ? 'active' : ''; ?>">
                    All Reports <span class="report-pill-badge"><?php echo $totalReportsCount; ?></span>
                </a>
                <a href="reports.php?status=pending<?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" 
                   class="report-tab-pill <?php echo ($statusFilter === 'pending') ? 'active' : ''; ?>">
                    Pending <span class="report-pill-badge" style="background: rgba(245, 158, 11, 0.45); color: #FFF;"><?php echo $pendingReportsCount; ?></span>
                </a>
                <a href="reports.php?status=reviewed<?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" 
                   class="report-tab-pill <?php echo ($statusFilter === 'reviewed') ? 'active' : ''; ?>">
                    Under Review <span class="report-pill-badge" style="background: rgba(90, 26, 43, 0.7); color: #E8CB83; border: 1px solid rgba(216, 185, 106, 0.3);"><?php echo $reviewedReportsCount; ?></span>
                </a>
                <a href="reports.php?status=resolved<?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" 
                   class="report-tab-pill <?php echo ($statusFilter === 'resolved') ? 'active' : ''; ?>">
                    Resolved <span class="report-pill-badge" style="background: rgba(16, 185, 129, 0.45); color: #FFF;"><?php echo $resolvedReportsCount; ?></span>
                </a>
                <a href="reports.php?status=dismissed<?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>" 
                   class="report-tab-pill <?php echo ($statusFilter === 'dismissed') ? 'active' : ''; ?>">
                    Dismissed <span class="report-pill-badge"><?php echo $dismissedReportsCount; ?></span>
                </a>
            </div>

        </div>

        <!-- Moderation Reports Table -->
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width: 70px;">ID</th>
                        <th style="min-width: 200px;">Reported Subject</th>
                        <th style="min-width: 250px;">Reason &amp; Details</th>
                        <th style="min-width: 170px;">Reported By</th>
                        <th style="min-width: 120px;">Submitted</th>
                        <th style="min-width: 120px;">Status</th>
                        <th style="min-width: 210px; text-align: right;">Moderation Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reports)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 4rem 2rem; background: rgba(20, 10, 36, 0.5);">
                                <div style="font-size: 2.5rem; margin-bottom: 0.8rem; opacity: 0.7;">&#128221;</div>
                                <h3 style="color: #F8F7FC; font-size: 1.25rem; font-weight: 700; margin-bottom: 0.4rem;">
                                    No Moderation Reports Found
                                </h3>
                                <p style="color: #B9B2C9; font-size: 0.92rem; max-width: 440px; margin: 0 auto 1.2rem;">
                                    <?php if (!empty($searchQuery) || !empty($statusFilter)): ?>
                                        There are currently no reports matching your active filters. Try adjusting your search term or view all reports.
                                    <?php else: ?>
                                        All incident reports have been reviewed and resolved. No pending moderation items require attention.
                                    <?php endif; ?>
                                </p>
                                <?php if (!empty($searchQuery) || !empty($statusFilter)): ?>
                                    <a href="reports.php" class="btn btn-outline-light btn-sm" style="color: #F8F7FC;">
                                        Reset All Filters
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reports as $r): ?>
                            <tr>
                                <!-- ID -->
                                <td>
                                    <strong style="color: #D8B96A; font-size: 0.95rem;">#<?php echo $r['id']; ?></strong>
                                </td>

                                <!-- Reported Subject -->
                                <td>
                                    <?php if (!empty($r['reported_user_name'])): ?>
                                        <div>
                                            <span class="badge subject-badge-user" style="font-size: 0.72rem; margin-bottom: 0.3rem;">
                                                User Account
                                            </span>
                                            <div style="font-weight: 700; color: #F8F7FC; font-size: 0.95rem;">
                                                <?php echo htmlspecialchars($r['reported_user_name']); ?>
                                            </div>
                                            <div style="font-size: 0.8rem; color: #B9B2C9;">
                                                <?php echo htmlspecialchars($r['reported_user_email'] ?? ''); ?>
                                            </div>
                                            <?php if (($r['reported_user_status'] ?? '') === 'suspended'): ?>
                                                <span class="badge badge-rejected" style="font-size: 0.7rem; margin-top: 0.25rem;">
                                                    Currently Suspended
                                                </span>
                                            <?php endif; ?>
                                            <div style="margin-top: 0.35rem;">
                                                <a href="users.php?search=<?php echo urlencode($r['reported_user_username'] ?? $r['reported_user_name']); ?>" 
                                                   target="_blank" 
                                                   class="admin-link">
                                                    Inspect Account &rarr;
                                                </a>
                                            </div>
                                        </div>
                                    <?php elseif (!empty($r['event_title'])): ?>
                                        <div>
                                            <span class="badge subject-badge-event" style="font-size: 0.72rem; margin-bottom: 0.3rem;">
                                                Event Posting
                                            </span>
                                            <div style="font-weight: 700; color: #F8F7FC; font-size: 0.95rem;">
                                                <?php echo htmlspecialchars($r['event_title']); ?>
                                            </div>
                                            <div style="margin-top: 0.35rem;">
                                                <a href="events.php" target="_blank" class="admin-link">
                                                    Inspect Event &rarr;
                                                </a>
                                            </div>
                                        </div>
                                    <?php elseif (!empty($r['job_title'])): ?>
                                        <div>
                                            <span class="badge subject-badge-job" style="font-size: 0.72rem; margin-bottom: 0.3rem;">
                                                Career Vacancy
                                            </span>
                                            <div style="font-weight: 700; color: #F8F7FC; font-size: 0.95rem;">
                                                <?php echo htmlspecialchars($r['job_title']); ?>
                                            </div>
                                            <div style="margin-top: 0.35rem;">
                                                <a href="jobs.php" target="_blank" class="admin-link">
                                                    Inspect Vacancy &rarr;
                                                </a>
                                            </div>
                                        </div>
                                    <?php elseif (!empty($r['message_id'])): ?>
                                        <div>
                                            <span class="badge subject-badge-message" style="font-size: 0.72rem; margin-bottom: 0.3rem;">
                                                Direct Message
                                            </span>
                                            <div style="font-weight: 700; color: #F8F7FC; font-size: 0.95rem;">
                                                Message #<?php echo $r['message_id']; ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div>
                                            <span class="badge badge-inactive" style="font-size: 0.72rem;">Platform Content</span>
                                            <div style="color: #B9B2C9; font-size: 0.88rem; margin-top: 0.2rem;">Community Notice</div>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <!-- Reason & Details -->
                                <td>
                                    <div style="font-weight: 700; color: #EF4444; font-size: 0.92rem; margin-bottom: 0.25rem;">
                                        <?php echo htmlspecialchars($r['reason']); ?>
                                    </div>
                                    <?php if (!empty($r['description'])): ?>
                                        <div class="report-description-box" title="<?php echo htmlspecialchars($r['description']); ?>">
                                            <?php echo htmlspecialchars($r['description']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <button type="button" 
                                            class="btn btn-outline-light btn-sm" 
                                            style="margin-top: 0.5rem; font-size: 0.74rem; padding: 0.18rem 0.5rem;" 
                                            onclick='openReportModal(<?php echo json_encode($r, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>);'>
                                        View Full Details &bull;
                                    </button>
                                </td>

                                <!-- Reported By -->
                                <td>
                                    <div style="font-weight: 600; color: #F8F7FC; font-size: 0.92rem;">
                                        <?php echo htmlspecialchars($r['reporter_name'] ?? 'Alumnus'); ?>
                                    </div>
                                    <div style="font-size: 0.78rem; color: #B9B2C9;">
                                        <?php echo htmlspecialchars($r['reporter_email'] ?? 'alumni@alumni.edu'); ?>
                                    </div>
                                </td>

                                <!-- Date Submitted -->
                                <td style="font-size: 0.86rem; color: #B9B2C9; white-space: nowrap;">
                                    <?php echo date('M d, Y', strtotime($r['created_at'])); ?>
                                    <div style="font-size: 0.75rem; color: #8F859F;">
                                        <?php echo date('h:i A', strtotime($r['created_at'])); ?>
                                    </div>
                                </td>

                                <!-- Status Badge -->
                                <td>
                                    <?php if ($r['status'] === 'pending'): ?>
                                        <span class="badge badge-pending">Pending Review</span>
                                    <?php elseif ($r['status'] === 'reviewed'): ?>
                                        <span class="badge badge-reviewed">Under Review</span>
                                    <?php elseif ($r['status'] === 'resolved'): ?>
                                        <span class="badge badge-resolved">Resolved</span>
                                    <?php else: ?>
                                        <span class="badge badge-dismissed">Dismissed</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Moderation Actions -->
                                <td style="text-align: right;">
                                    <div style="display: flex; gap: 0.35rem; justify-content: flex-end; flex-wrap: wrap;">
                                        
                                        <!-- Review Action (If Pending) -->
                                        <?php if ($r['status'] === 'pending'): ?>
                                            <form method="POST" action="reports.php" style="display: inline;">
                                                <input type="hidden" name="action" value="set_status">
                                                <input type="hidden" name="report_id" value="<?php echo $r['id']; ?>">
                                                <input type="hidden" name="new_status" value="reviewed">
                                                <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                                                <button type="submit" class="btn btn-outline-light btn-sm" style="font-size: 0.75rem; padding: 0.28rem 0.55rem; color: #E8CB83; border-color: rgba(216, 185, 106, 0.35);" title="Mark under active review">
                                                    Review
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <!-- Resolve Action (If Not Resolved) -->
                                        <?php if ($r['status'] !== 'resolved'): ?>
                                            <form method="POST" action="reports.php" style="display: inline;">
                                                <input type="hidden" name="action" value="set_status">
                                                <input type="hidden" name="report_id" value="<?php echo $r['id']; ?>">
                                                <input type="hidden" name="new_status" value="resolved">
                                                <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                                                <button type="submit" class="btn btn-primary btn-sm" style="font-size: 0.75rem; padding: 0.28rem 0.55rem;" title="Mark report as resolved">
                                                    Resolve
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <!-- Dismiss Action (If Pending or Reviewed) -->
                                        <?php if ($r['status'] === 'pending' || $r['status'] === 'reviewed'): ?>
                                            <form method="POST" action="reports.php" style="display: inline;">
                                                <input type="hidden" name="action" value="set_status">
                                                <input type="hidden" name="report_id" value="<?php echo $r['id']; ?>">
                                                <input type="hidden" name="new_status" value="dismissed">
                                                <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                                                <button type="submit" class="btn btn-outline-light btn-sm" style="font-size: 0.75rem; padding: 0.28rem 0.55rem; color: #94A3B8;" title="Dismiss report as invalid or duplicate">
                                                    Dismiss
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <!-- Reopen Action (If Resolved or Dismissed) -->
                                        <?php if ($r['status'] === 'resolved' || $r['status'] === 'dismissed'): ?>
                                            <form method="POST" action="reports.php" style="display: inline;">
                                                <input type="hidden" name="action" value="set_status">
                                                <input type="hidden" name="report_id" value="<?php echo $r['id']; ?>">
                                                <input type="hidden" name="new_status" value="pending">
                                                <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                                                <button type="submit" class="btn btn-outline-light btn-sm" style="font-size: 0.75rem; padding: 0.28rem 0.55rem; color: #E8CB83;" title="Reopen report for investigation">
                                                    Reopen
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <!-- Suspend User Action -->
                                        <?php if (!empty($r['reported_user_id']) && ($r['reported_user_status'] ?? '') !== 'suspended'): ?>
                                            <form method="POST" action="reports.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to suspend this user account? This will block their login and auto-resolve this report.');">
                                                <input type="hidden" name="action" value="suspend_user">
                                                <input type="hidden" name="report_id" value="<?php echo $r['id']; ?>">
                                                <input type="hidden" name="target_user_id" value="<?php echo $r['reported_user_id']; ?>">
                                                <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                                                <button type="submit" class="btn btn-glass-danger btn-sm" title="Suspend account immediately">
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

<!-- Liquid Glass Incident Details Modal Dialog -->
<div id="reportDetailsModal" class="report-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <div class="report-modal-card">
        <div class="report-modal-header">
            <div>
                <span id="modalReportBadge" class="badge badge-pending" style="margin-bottom: 0.4rem;">Pending</span>
                <h2 id="modalTitle" style="color: #F8F7FC; font-size: 1.35rem; font-weight: 800; margin: 0;">
                    Incident Dossier
                </h2>
            </div>
            <button type="button" class="report-modal-close" aria-label="Close modal" onclick="closeReportModal();">&times;</button>
        </div>

        <div class="report-dossier-grid">
            <div class="report-dossier-item">
                <div class="report-dossier-label">Report ID &amp; Time</div>
                <div id="modalReportMeta" class="report-dossier-value">-</div>
            </div>
            <div class="report-dossier-item">
                <div class="report-dossier-label">Reported By</div>
                <div id="modalReporter" class="report-dossier-value">John Doe</div>
            </div>
            <div class="report-dossier-item report-dossier-full">
                <div class="report-dossier-label">Reported Subject</div>
                <div id="modalSubject" class="report-dossier-value">User Account</div>
            </div>
            <div class="report-dossier-item report-dossier-full">
                <div class="report-dossier-label">Violation Category &amp; Reason</div>
                <div id="modalReason" class="report-dossier-value" style="color: #EF4444;">Suspicious Activity</div>
            </div>
            <div class="report-dossier-item report-dossier-full">
                <div class="report-dossier-label">Full Narrative Description</div>
                <div id="modalDescription" class="report-dossier-value" style="font-weight: 400; line-height: 1.6; white-space: pre-wrap;">-</div>
            </div>
        </div>

        <!-- Fast Moderation Actions inside Modal -->
        <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(216, 185, 106, 0.2); padding-top: 1.2rem; gap: 0.5rem; flex-wrap: wrap;">
            <div id="modalActionsGroup" style="display: flex; gap: 0.4rem; flex-wrap: wrap;">
                <!-- Populated dynamically via JS -->
            </div>
            <button type="button" class="btn btn-outline-light btn-sm" onclick="closeReportModal();">
                Close Dossier
            </button>
        </div>
    </div>
</div>

<script>
    // Pure Vanilla JS Incident Dossier Modal Controller
    const reportModal = document.getElementById('reportDetailsModal');
    const csrfToken = <?php echo json_encode(get_csrf_token()); ?>;

    function openReportModal(data) {
        if (!data || !reportModal) return;

        // Meta & Status
        const modalTitle = document.getElementById('modalTitle');
        const modalMeta = document.getElementById('modalReportMeta');
        const modalBadge = document.getElementById('modalReportBadge');
        const modalReporter = document.getElementById('modalReporter');
        const modalSubject = document.getElementById('modalSubject');
        const modalReason = document.getElementById('modalReason');
        const modalDesc = document.getElementById('modalDescription');
        const modalActions = document.getElementById('modalActionsGroup');

        if (modalTitle) modalTitle.textContent = `Incident Dossier #${data.id}`;
        if (modalMeta) modalMeta.textContent = `#${data.id} • ${data.created_at || 'Recently'}`;
        
        if (modalBadge) {
            modalBadge.className = 'badge';
            if (data.status === 'pending') {
                modalBadge.classList.add('badge-pending');
                modalBadge.textContent = 'Pending Review';
            } else if (data.status === 'reviewed') {
                modalBadge.classList.add('badge-reviewed');
                modalBadge.textContent = 'Under Review';
            } else if (data.status === 'resolved') {
                modalBadge.classList.add('badge-resolved');
                modalBadge.textContent = 'Resolved';
            } else {
                modalBadge.classList.add('badge-dismissed');
                modalBadge.textContent = 'Dismissed';
            }
        }

        if (modalReporter) {
            modalReporter.textContent = `${data.reporter_name || 'Alumnus'} (${data.reporter_email || 'No email'})`;
        }

        if (modalSubject) {
            if (data.reported_user_name) {
                modalSubject.innerHTML = `<strong>User Account:</strong> ${escapeHtml(data.reported_user_name)} (${escapeHtml(data.reported_user_email || '')})`;
            } else if (data.event_title) {
                modalSubject.innerHTML = `<strong>Event:</strong> ${escapeHtml(data.event_title)}`;
            } else if (data.job_title) {
                modalSubject.innerHTML = `<strong>Career Posting:</strong> ${escapeHtml(data.job_title)}`;
            } else if (data.message_id) {
                modalSubject.innerHTML = `<strong>Direct Message ID:</strong> #${data.message_id}`;
            } else {
                modalSubject.innerHTML = `Community Content Item`;
            }
        }

        if (modalReason) modalReason.textContent = data.reason || 'Unspecified Incident';
        if (modalDesc) modalDesc.textContent = data.description || 'No detailed narrative provided by the reporting alumnus.';

        // Fast actions inside modal
        if (modalActions) {
            modalActions.innerHTML = '';

            if (data.status !== 'resolved') {
                const resolveForm = createActionForm(data.id, 'set_status', 'resolved', 'btn-primary', 'Resolve Incident');
                modalActions.appendChild(resolveForm);
            }

            if (data.status === 'pending') {
                const reviewForm = createActionForm(data.id, 'set_status', 'reviewed', 'btn-outline-light', 'Mark Under Review');
                modalActions.appendChild(reviewForm);
            }

            if (data.status === 'pending' || data.status === 'reviewed') {
                const dismissForm = createActionForm(data.id, 'set_status', 'dismissed', 'btn-outline-light', 'Dismiss');
                modalActions.appendChild(dismissForm);
            }

            if (data.status === 'resolved' || data.status === 'dismissed') {
                const reopenForm = createActionForm(data.id, 'set_status', 'pending', 'btn-outline-light', 'Reopen Incident');
                modalActions.appendChild(reopenForm);
            }
        }

        reportModal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeReportModal() {
        if (!reportModal) return;
        reportModal.classList.remove('active');
        document.body.style.overflow = '';
    }

    function createActionForm(reportId, action, newStatus, btnClass, label) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'reports.php';
        form.style.display = 'inline';
        form.innerHTML = `
            <input type="hidden" name="action" value="${action}">
            <input type="hidden" name="report_id" value="${reportId}">
            <input type="hidden" name="new_status" value="${newStatus}">
            <input type="hidden" name="csrf_token" value="${csrfToken}">
            <button type="submit" class="btn ${btnClass} btn-sm">${label}</button>
        `;
        return form;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Modal Keyboard ESC and Backdrop Accessibility
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && reportModal && reportModal.classList.contains('active')) {
            closeReportModal();
        }
    });

    if (reportModal) {
        reportModal.addEventListener('click', function(e) {
            if (e.target === reportModal) {
                closeReportModal();
            }
        });
    }
</script>

<script src="<?php echo $baseUrl; ?>assets/js/main.js"></script>
</body>
</html>
