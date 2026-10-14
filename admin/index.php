<?php
// ============================================================
// University Alumni Network - Admin Dashboard Overview
// ============================================================
require_once __DIR__ . '/../config/db.php';

require_admin();

$currentAdminPage = 'dashboard';
$baseUrl = get_base_url();

// Compute stats
$totalUsersCount = 0;
$activeUsersCount = 0;
$suspendedUsersCount = 0;
$pendingEventsCount = 0;
$approvedEventsCount = 0;
$pendingJobsCount = 0;
$approvedJobsCount = 0;
$pendingReportsCount = 0;

$recentPendingEvents = [];
$recentPendingJobs = [];

if ($db_connected && $pdo) {
    try {
        // Users stats
        $totalUsersCount = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $activeUsersCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();
        $suspendedUsersCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status = 'suspended'")->fetchColumn();

        // Events stats
        $pendingEventsCount = (int)$pdo->query("SELECT COUNT(*) FROM events WHERE status = 'pending'")->fetchColumn();
        $approvedEventsCount = (int)$pdo->query("SELECT COUNT(*) FROM events WHERE status = 'approved'")->fetchColumn();

        // Jobs stats
        $pendingJobsCount = (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE status = 'pending'")->fetchColumn();
        $approvedJobsCount = (int)$pdo->query("SELECT COUNT(*) FROM jobs WHERE status = 'approved'")->fetchColumn();

        // Reports stats
        $pendingReportsCount = (int)$pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn();

        // Fetch moderation events list prioritizing pending
        $stmt = $pdo->query("
            SELECT e.*, CONCAT(u.first_name, ' ', u.last_name) AS creator_name
            FROM events e
            LEFT JOIN users u ON e.organizer_id = u.id
            ORDER BY FIELD(e.status, 'pending', 'approved', 'rejected'), e.created_at DESC
            LIMIT 2
        ");
        $recentPendingEvents = $stmt->fetchAll();

        // Fetch moderation jobs list prioritizing pending
        $stmt = $pdo->query("
            SELECT j.*, CONCAT(u.first_name, ' ', u.last_name) AS poster_name
            FROM jobs j
            LEFT JOIN users u ON j.posted_by = u.id
            ORDER BY FIELD(j.status, 'pending', 'approved', 'rejected'), j.posted_at DESC
            LIMIT 2
        ");
        $recentPendingJobs = $stmt->fetchAll();

    } catch (Exception $e) {
        // Fallback to sample data
    }
}

// Fallback dummy records if empty (reduced to two examples per section)
if (empty($recentPendingEvents)) {
    $recentPendingEvents = [
        ['id' => 101, 'title' => 'Alumni Meetup & Tech Fireside', 'creator_name' => 'John Doe', 'status' => 'pending', 'event_date' => '2026-11-20'],
        ['id' => 102, 'title' => 'Career Fair 2026', 'creator_name' => 'Sarah Jenkins', 'status' => 'approved', 'event_date' => '2026-12-05']
    ];
}

if (empty($recentPendingJobs)) {
    $recentPendingJobs = [
        ['id' => 201, 'title' => 'Software Engineer', 'company' => 'ABC Technologies', 'status' => 'pending', 'job_type' => 'Full-Time'],
        ['id' => 202, 'title' => 'Web Developer', 'company' => 'XYZ Global', 'status' => 'approved', 'job_type' => 'Full-Time']
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Alumni Network</title>
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?php echo $baseUrl; ?>assets/favicon.svg?v=3">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo $baseUrl; ?>assets/favicon-32x32.png?v=3">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo $baseUrl; ?>assets/favicon-16x16.png?v=3">
    <link rel="shortcut icon" href="<?php echo $baseUrl; ?>favicon.ico?v=3">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo $baseUrl; ?>assets/apple-touch-icon.png?v=3">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/css/style.css">
</head>
<body>

<div class="admin-layout">
    
    <!-- Dark Navy Sidebar -->
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <!-- Main Content Area (Light Gray) -->
    <main class="admin-main">
        <div class="admin-header">
            <div>
                <h1 class="admin-title">Executive Dashboard</h1>
                <p style="color: var(--text-muted); font-size: 0.95rem;">System overview, moderation queues, and network metrics</p>
            </div>
            <div>
                <a href="<?php echo $baseUrl; ?>config/setup.php" class="btn btn-primary btn-sm">&#9881; Database Setup</a>
            </div>
        </div>

        <!-- Comprehensive Statistics Cards (Clickable Navigation) -->
        <div class="admin-stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
            
            <a href="users.php" class="admin-stat-card" title="View & manage all registered users" aria-label="Total Users: <?php echo number_format($totalUsersCount); ?>">
                <div>
                    <div class="admin-stat-number"><?php echo number_format($totalUsersCount); ?></div>
                    <div class="admin-stat-label">Total Users</div>
                </div>
                <div class="admin-stat-icon">&#128101;</div>
            </a>

            <a href="users.php?status=active" class="admin-stat-card" title="Filter active alumni accounts" aria-label="Active Users: <?php echo number_format($activeUsersCount); ?>">
                <div>
                    <div class="admin-stat-number" style="color: var(--success-color);"><?php echo number_format($activeUsersCount); ?></div>
                    <div class="admin-stat-label">Active Users</div>
                </div>
                <div class="admin-stat-icon">&#9989;</div>
            </a>

            <a href="users.php?status=suspended" class="admin-stat-card" title="Filter blocked and suspended accounts" aria-label="Blocked or Suspended Users: <?php echo number_format($suspendedUsersCount); ?>">
                <div>
                    <div class="admin-stat-number" style="color: var(--danger-color);"><?php echo number_format($suspendedUsersCount); ?></div>
                    <div class="admin-stat-label">Blocked / Suspended</div>
                </div>
                <div class="admin-stat-icon">&#128683;</div>
            </a>

            <a href="events.php?status=pending" class="admin-stat-card" title="Review pending community events" aria-label="Pending Events: <?php echo $pendingEventsCount; ?>">
                <div>
                    <div class="admin-stat-number" style="color: var(--warning-color);"><?php echo $pendingEventsCount; ?></div>
                    <div class="admin-stat-label">Pending Events</div>
                </div>
                <div class="admin-stat-icon">&#9203;</div>
            </a>

            <a href="events.php?status=approved" class="admin-stat-card" title="View approved events list" aria-label="Approved Events: <?php echo $approvedEventsCount; ?>">
                <div>
                    <div class="admin-stat-number" style="color: var(--success-color);"><?php echo $approvedEventsCount; ?></div>
                    <div class="admin-stat-label">Approved Events</div>
                </div>
                <div class="admin-stat-icon">&#128197;</div>
            </a>

            <a href="jobs.php?status=pending" class="admin-stat-card" title="Review pending job vacancies" aria-label="Pending Jobs: <?php echo $pendingJobsCount; ?>">
                <div>
                    <div class="admin-stat-number" style="color: var(--warning-color);"><?php echo $pendingJobsCount; ?></div>
                    <div class="admin-stat-label">Pending Jobs</div>
                </div>
                <div class="admin-stat-icon">&#9203;</div>
            </a>

            <a href="jobs.php?status=approved" class="admin-stat-card" title="View approved career postings" aria-label="Approved Jobs: <?php echo $approvedJobsCount; ?>">
                <div>
                    <div class="admin-stat-number" style="color: var(--success-color);"><?php echo $approvedJobsCount; ?></div>
                    <div class="admin-stat-label">Approved Jobs</div>
                </div>
                <div class="admin-stat-icon">&#128188;</div>
            </a>

            <a href="reports.php?status=pending" class="admin-stat-card" title="Review moderation reports queue" aria-label="Reports Queue: <?php echo $pendingReportsCount; ?>">
                <div>
                    <div class="admin-stat-number" style="color: <?php echo ($pendingReportsCount > 0) ? 'var(--danger-color)' : 'var(--text-muted)'; ?>;"><?php echo $pendingReportsCount; ?></div>
                    <div class="admin-stat-label">Reports Queue</div>
                </div>
                <div class="admin-stat-icon">&#9873;</div>
            </a>

        </div>

        <!-- Moderation Preview Tables -->
        <div class="grid grid-2" style="margin-top: 1rem;">
            
            <!-- Events Moderation Preview -->
            <div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h3 style="color: var(--text-light); font-weight: 800; font-size: 1.25rem;">Event Submissions</h3>
                    <a href="events.php" style="color: var(--accent-color); font-weight: 700; font-size: 0.88rem;">Manage All &rarr;</a>
                </div>

                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Event</th>
                                <th>Creator</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentPendingEvents as $ev): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($ev['title']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($ev['creator_name'] ?? 'Alumnus'); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo strtolower($ev['status']); ?>">
                                            <?php echo ucfirst($ev['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="events.php<?php echo ($ev['status'] === 'pending') ? '?status=pending' : ''; ?>" class="btn btn-primary btn-sm" style="padding: 0.25rem 0.6rem; font-size: 0.8rem;">
                                            <?php echo ($ev['status'] === 'pending') ? 'Review' : 'View'; ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Jobs Moderation Preview -->
            <div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h3 style="color: var(--text-light); font-weight: 800; font-size: 1.25rem;">Job Postings</h3>
                    <a href="jobs.php" style="color: var(--accent-color); font-weight: 700; font-size: 0.88rem;">Manage All &rarr;</a>
                </div>

                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Job</th>
                                <th>Company</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentPendingJobs as $jb): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($jb['title']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($jb['company']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo strtolower($jb['status']); ?>">
                                            <?php echo ucfirst($jb['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="jobs.php<?php echo ($jb['status'] === 'pending') ? '?status=pending' : ''; ?>" class="btn btn-primary btn-sm" style="padding: 0.25rem 0.6rem; font-size: 0.8rem;">
                                            <?php echo ($jb['status'] === 'pending') ? 'Review' : 'View'; ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </main>

</div>

<script src="<?php echo $baseUrl; ?>assets/js/main.js"></script>
</body>
</html>
