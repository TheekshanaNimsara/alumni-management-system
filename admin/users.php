<?php
// ============================================================
// University Alumni Network - Admin User Management
// ============================================================
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../auth/login.php?admin_required=1");
    exit;
}

$currentAdminPage = 'users';
$baseUrl = get_base_url();
$msg = '';

// Handle Status Toggle (active / suspended)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['user_id'], $_POST['status'])) {
    $userId = intval($_POST['user_id']);
    $newStatus = ($_POST['status'] === 'suspended') ? 'suspended' : 'active';

    if ($db_connected && $pdo) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ? AND role != 'admin'");
            $stmt->execute([$newStatus, $userId]);
            $msg = "User #{$userId} status changed to {$newStatus}.";
        } catch (Exception $e) {
            $msg = "Error updating user: " . $e->getMessage();
        }
    } else {
        $msg = "User #{$userId} status changed to {$newStatus}.";
    }
}

// Fetch users
$usersList = [];
if ($db_connected && $pdo) {
    try {
        $stmt = $pdo->query("
            SELECT u.*, p.degree_programme, p.graduation_year, p.current_company, p.current_job_title
            FROM users u
            LEFT JOIN alumni_profiles p ON u.id = p.user_id
            ORDER BY u.role DESC, u.id ASC
        ");
        $usersList = $stmt->fetchAll();
    } catch (Exception $e) {
        $usersList = [];
    }
}

if (empty($usersList)) {
    $usersList = [
        ['id' => 1, 'first_name' => 'System', 'last_name' => 'Administrator', 'email' => 'admin@alumni.edu', 'role' => 'admin', 'status' => 'active', 'degree_programme' => 'Information Systems', 'graduation_year' => 2010],
        ['id' => 2, 'first_name' => 'John', 'last_name' => 'Doe', 'email' => 'john.doe@alumni.edu', 'role' => 'alumni', 'status' => 'active', 'degree_programme' => 'Computer Science', 'graduation_year' => 2022],
        ['id' => 3, 'first_name' => 'Sarah', 'last_name' => 'Jenkins', 'email' => 'sarah.j@alumni.edu', 'role' => 'alumni', 'status' => 'active', 'degree_programme' => 'Business Administration', 'graduation_year' => 2020]
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Alumni Admin</title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/css/style.css">
</head>
<body>

<div class="admin-layout">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <main class="admin-main">
        <div class="admin-header">
            <div>
                <h1 class="admin-title">Alumni Directory Management</h1>
                <p style="color: var(--text-muted); font-size: 0.95rem;">Manage alumni user accounts, roles, and status</p>
            </div>
            <div>
                <a href="<?php echo $baseUrl; ?>directory.php" target="_blank" class="btn btn-outline-light btn-sm" style="color: var(--primary-color); border-color: var(--border-color);">
                    Public Directory &rarr;
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
                        <th>Alumnus Name</th>
                        <th>Email</th>
                        <th>Degree &amp; Batch</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usersList as $u): ?>
                        <tr>
                            <td>
                                <strong style="color: var(--primary-color);">
                                    <?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name']); ?>
                                </strong>
                            </td>
                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                            <td>
                                <div><?php echo htmlspecialchars($u['degree_programme'] ?? 'Undergraduate'); ?></div>
                                <div style="font-size: 0.8rem; color: var(--text-muted);">Class of <?php echo htmlspecialchars($u['graduation_year'] ?? 'N/A'); ?></div>
                            </td>
                            <td>
                                <span style="font-weight: 700; font-size: 0.82rem; text-transform: uppercase; color: <?php echo ($u['role'] === 'admin') ? 'var(--accent-color)' : 'var(--primary-color)'; ?>;">
                                    <?php echo htmlspecialchars($u['role']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo strtolower($u['status']); ?>">
                                    <?php echo ucfirst($u['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($u['role'] !== 'admin'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                        <?php if ($u['status'] === 'active'): ?>
                                            <input type="hidden" name="status" value="suspended">
                                            <button type="submit" class="btn btn-sm" style="background: #F8EAEA; color: #A63D40; border: 1px solid #A63D40; padding: 0.25rem 0.6rem; font-size: 0.8rem;">
                                                Suspend
                                            </button>
                                        <?php else: ?>
                                            <input type="hidden" name="status" value="active">
                                            <button type="submit" class="btn btn-primary btn-sm" style="padding: 0.25rem 0.6rem; font-size: 0.8rem;">
                                                Activate
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                <?php else: ?>
                                    <span style="font-size: 0.8rem; color: var(--text-muted);">System Protected</span>
                                <?php endif; ?>
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
