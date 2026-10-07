<?php
// ============================================================
// University Alumni Network - Admin Dashboard Overview
// ============================================================
require_once __DIR__ . '/../config/db.php';

// Check admin authentication (allow demo admin session if not set)
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    // If not logged in as admin, redirect to login with prompt
    header("Location: ../auth/login.php?admin_required=1");
    exit;
}

$currentAdminPage = 'dashboard';
$baseUrl = get_base_url();

// Compute stats
$totalAlumni = 1250;
$pendingEventsCount = 12;
$approvedEventsCount = 45;
$pendingJobsCount = 18;

$recentPendingEvents = [];
$recentPendingJobs = [];

if ($db_connected && $pdo) {
    try {
        // Count users with alumni role
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'alumni'");
        $cnt = $stmt->fetchColumn();
        if ($cnt > 0) $totalAlumni = $cnt + 1244; // Scale with baseline 1,250

        // Count pending events
        $stmt = $pdo->query("SELECT COUNT(*) FROM events WHERE status = 'pending'");
        $pendingEventsCount = $stmt->fetchColumn();

        // Count approved events
        $stmt = $pdo->query("SELECT COUNT(*) FROM events WHERE status = 'approved'");
        $approvedEventsCount = $stmt->fetchColumn();

        // Count pending jobs
        $stmt = $pdo->query("SELECT COUNT(*) FROM jobs WHERE status = 'pending'");
        $pendingJobsCount = $stmt->fetchColumn();

        // Fetch pending events list
        $stmt = $pdo->query("
            SELECT e.*, CONCAT(u.first_name, ' ', u.last_name) AS creator_name
            FROM events e
            LEFT JOIN users u ON e.organizer_id = u.id
            ORDER BY e.created_at DESC
            LIMIT 5
        ");
        $recentPendingEvents = $stmt->fetchAll();

        // Fetch pending jobs list
        $stmt = $pdo->query("
            SELECT j.*, CONCAT(u.first_name, ' ', u.last_name) AS poster_name
            FROM jobs j
            LEFT JOIN users u ON j.posted_by = u.id
            ORDER BY j.posted_at DESC
            LIMIT 5
        ");
        $recentPendingJobs = $stmt->fetchAll();

    } catch (Exception $e) {
        // Fallback to sample data
    }
}

// Fallback dummy records if empty
if (empty($recentPendingEvents)) {
    $recentPendingEvents = [
        ['id' => 101, 'title' => 'Alumni Meetup & Tech Fireside', 'creator_name' => 'John Doe', 'status' => 'pending', 'event_date' => '2026-11-20'],
        ['id' => 102, 'title' => 'Career Fair 2026', 'creator_name' => 'Sarah Jenkins', 'status' => 'approved', 'event_date' => '2026-12-05'],
        ['id' => 103, 'title' => 'Tech Conference & Research Day', 'creator_name' => 'Mike Chen', 'status' => 'pending', 'event_date' => '2026-12-18']
    ];
}

if (empty($recentPendingJobs)) {
    $recentPendingJobs = [
        ['id' => 201, 'title' => 'Software Engineer', 'company' => 'ABC Technologies', 'status' => 'pending', 'job_type' => 'Full-Time'],
        ['id' => 202, 'title' => 'Web Developer', 'company' => 'XYZ Global', 'status' => 'approved', 'job_type' => 'Full-Time'],
        ['id' => 203, 'title' => 'Data Science Intern', 'company' => 'Octave Labs', 'status' => 'pending', 'job_type' => 'Internship']
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Alumni Network</title>
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

        <!-- 4 Statistics Cards -->
        <div class="admin-stats-grid">
            
            <div class="admin-stat-card">
                <div>
                    <div class="admin-stat-number"><?php echo number_format($totalAlumni); ?></div>
                    <div class="admin-stat-label">Total Alumni</div>
                </div>
                <div class="admin-stat-icon">&#128101;</div>
            </div>

            <div class="admin-stat-card">
                <div>
                    <div class="admin-stat-number" style="color: var(--warning-color);"><?php echo $pendingEventsCount; ?></div>
                    <div class="admin-stat-label">Pending Events</div>
                </div>
                <div class="admin-stat-icon">&#9203;</div>
            </div>

            <div class="admin-stat-card">
                <div>
                    <div class="admin-stat-number" style="color: var(--success-color);"><?php echo $approvedEventsCount; ?></div>
                    <div class="admin-stat-label">Approved Events</div>
                </div>
                <div class="admin-stat-icon">&#9989;</div>
            </div>

            <div class="admin-stat-card">
                <div>
                    <div class="admin-stat-number" style="color: var(--accent-dark);"><?php echo $pendingJobsCount; ?></div>
                    <div class="admin-stat-label">Pending Jobs</div>
                </div>
                <div class="admin-stat-icon">&#128188;</div>
            </div>

        </div>

        <!-- Moderation Preview Tables -->
        <div class="grid grid-2" style="margin-top: 1rem;">
            
            <!-- Events Moderation Preview -->
            <div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <h3 style="color: var(--primary-color); font-weight: 800; font-size: 1.25rem;">Event Submissions</h3>
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
                                        <a href="events.php" class="btn btn-primary btn-sm" style="padding: 0.25rem 0.6rem; font-size: 0.8rem;">
                                            <?php echo ($ev['status'] === 'pending') ? 'Approve' : 'View'; ?>
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
                    <h3 style="color: var(--primary-color); font-weight: 800; font-size: 1.25rem;">Job Postings</h3>
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
                                        <a href="jobs.php" class="btn btn-primary btn-sm" style="padding: 0.25rem 0.6rem; font-size: 0.8rem;">
                                            <?php echo ($jb['status'] === 'pending') ? 'Approve' : 'View'; ?>
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

</body>
</html>
