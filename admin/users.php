<?php
// ============================================================
// University Alumni Network - Admin User Management
// Search, Filter, Activate, Block, Role Changes & Deletion
// ============================================================
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

require_admin();

$currentAdminPage = 'users';
$baseUrl = get_base_url();
$msg = '';
$msgType = 'success';

// Handle State-Changing POST Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $targetUserId = intval($_POST['user_id'] ?? 0);
    $currentAdminId = current_user_id();

        if ($targetUserId > 0 && $db_connected && $pdo) {
            try {
                // Safeguard: Protect primary admin ID 1
                if ($targetUserId === 1 && in_array($action, ['delete_user', 'change_role', 'change_status'])) {
                    $msg = "Primary system administrator cannot be modified or deleted.";
                    $msgType = 'danger';
                }
                // Safeguard: Prevent admin from suspending/deleting themselves
                elseif ($targetUserId === $currentAdminId && in_array($action, ['delete_user', 'change_status'])) {
                    $msg = "You cannot suspend or delete your own active administrator account.";
                    $msgType = 'danger';
                } else {
                    if ($action === 'change_status') {
                        $newStatus = ($_POST['status'] === 'suspended') ? 'suspended' : 'active';
                        $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
                        $stmt->execute([$newStatus, $targetUserId]);
                        $msg = "User #{$targetUserId} status updated to " . ucfirst($newStatus) . ".";
                        $msgType = ($newStatus === 'active') ? 'success' : 'warning';
                    } elseif ($action === 'change_role') {
                        $newRole = ($_POST['role'] === 'admin') ? 'admin' : 'alumni';
                        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
                        $stmt->execute([$newRole, $targetUserId]);
                        $msg = "User #{$targetUserId} role updated to " . ucfirst($newRole) . ".";
                        $msgType = 'success';
                    } elseif ($action === 'delete_user') {
                        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                        $stmt->execute([$targetUserId]);
                        $msg = "User account #{$targetUserId} permanently deleted.";
                        $msgType = 'warning';
                    }
                }
            } catch (Exception $e) {
                $msg = "Operation error: " . $e->getMessage();
                $msgType = 'danger';
            }
        }
}

// Search & Filter parameters
$searchQuery = trim($_GET['search'] ?? '');
$roleFilter = trim($_GET['role'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$usersList = [];
if ($db_connected && $pdo) {
    try {
        $sql = "
            SELECT u.*, p.degree_programme, p.graduation_year, p.current_company, p.current_job_title, p.profile_picture
            FROM users u
            LEFT JOIN alumni_profiles p ON u.id = p.user_id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($searchQuery)) {
            $sql .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR u.username LIKE ? OR p.current_company LIKE ?)";
            $term = "%{$searchQuery}%";
            $params = array_merge($params, [$term, $term, $term, $term, $term]);
        }

        if (!empty($roleFilter)) {
            $sql .= " AND u.role = ?";
            $params[] = $roleFilter;
        }

        if (!empty($statusFilter)) {
            $sql .= " AND u.status = ?";
            $params[] = $statusFilter;
        }

        $sql .= " ORDER BY (u.id = 1) DESC, u.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $usersList = $stmt->fetchAll();
    } catch (Exception $e) {
        $usersList = [];
    }
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
<body style="background: var(--bg-color);">

<div class="admin-layout">
    <?php require_once __DIR__ . '/includes/sidebar.php'; ?>

    <main class="admin-main">
        <div class="admin-header">
            <div>
                <h1 class="admin-title">Member & User Management</h1>
                <p style="color: var(--text-muted); font-size: 0.95rem;">Search, manage status, adjust roles, and audit alumni accounts</p>
            </div>
            <div>
                <a href="<?php echo $baseUrl; ?>directory.php" target="_blank" class="btn btn-outline-light btn-sm" style="color: var(--primary-color); border-color: var(--border-color);">
                    Public Directory &rarr;
                </a>
            </div>
        </div>

        <?php if (!empty($msg)): ?>
            <div class="alert alert-<?php echo $msgType; ?>"><?php echo htmlspecialchars($msg); ?></div>
        <?php endif; ?>

        <!-- Search & Filter Controls -->
        <div class="card" style="margin-bottom: 1.5rem; padding: 1.2rem;">
            <form method="GET" action="users.php" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end;">
                <div style="flex: 2; min-width: 220px;">
                    <label style="font-size: 0.85rem; font-weight: 700; color: var(--text-color); margin-bottom: 0.3rem;">Live Search</label>
                    <input type="text" id="userLiveSearch" name="search" class="form-control" 
                           placeholder="Type name, email, username or company..." 
                           value="<?php echo htmlspecialchars($searchQuery); ?>" onkeyup="liveFilterUsers();">
                </div>

                <div style="flex: 1; min-width: 140px;">
                    <label style="font-size: 0.85rem; font-weight: 700; color: var(--text-color); margin-bottom: 0.3rem;">Role</label>
                    <select name="role" class="form-control" onchange="this.form.submit();">
                        <option value="">All Roles</option>
                        <option value="alumni" <?php echo ($roleFilter === 'alumni') ? 'selected' : ''; ?>>Alumni</option>
                        <option value="admin" <?php echo ($roleFilter === 'admin') ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>

                <div style="flex: 1; min-width: 140px;">
                    <label style="font-size: 0.85rem; font-weight: 700; color: var(--text-color); margin-bottom: 0.3rem;">Account Status</label>
                    <select name="status" class="form-control" onchange="this.form.submit();">
                        <option value="">All Statuses</option>
                        <option value="active" <?php echo ($statusFilter === 'active') ? 'selected' : ''; ?>>Active</option>
                        <option value="suspended" <?php echo ($statusFilter === 'suspended') ? 'selected' : ''; ?>>Suspended</option>
                    </select>
                </div>

                <div style="display: flex; gap: 0.5rem;">
                    <button type="submit" class="btn btn-gold btn-sm">Filter</button>
                    <?php if (!empty($searchQuery) || !empty($roleFilter) || !empty($statusFilter)): ?>
                        <a href="users.php" class="btn btn-outline-light btn-sm" style="color: var(--text-color);">Clear</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Users Table -->
        <div class="card" style="padding: 0; overflow-x: auto;">
            <table class="table admin-table" id="usersTable" style="margin: 0;">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Academic Programme</th>
                        <th>Current Position</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($usersList)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 3rem; color: var(--text-muted);">
                                No users found matching query.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($usersList as $u): ?>
                            <tr class="user-row">
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.8rem;">
                                        <img src="<?php echo get_user_avatar_url($u['profile_picture'] ?? 'default-avatar.svg'); ?>" 
                                             alt="Avatar" style="width: 38px; height: 38px; border-radius: 50%; object-fit: cover; border: 2px solid var(--accent-color);">
                                        <div>
                                            <strong class="user-fullname" style="color: var(--primary-color);">
                                                <?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name']); ?>
                                            </strong>
                                            <div class="user-email" style="font-size: 0.8rem; color: var(--text-muted);">
                                                <?php echo htmlspecialchars($u['email']); ?> &bull; @<?php echo htmlspecialchars($u['username']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-size: 0.88rem; font-weight: 600;">
                                        <?php echo htmlspecialchars($u['degree_programme'] ?? 'Alumnus'); ?>
                                    </div>
                                    <div style="font-size: 0.8rem; color: var(--text-muted);">
                                        Class of <?php echo htmlspecialchars($u['graduation_year'] ?? 'N/A'); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="user-company" style="font-size: 0.88rem;">
                                        <?php echo htmlspecialchars($u['current_job_title'] ?? 'Professional'); ?>
                                    </div>
                                    <div style="font-size: 0.8rem; color: var(--text-muted);">
                                        <?php echo htmlspecialchars($u['current_company'] ?? 'University Member'); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($u['role'] === 'admin'): ?>
                                        <span class="badge" style="background: rgba(212, 175, 55, 0.2); color: var(--accent-dark); border: 1px solid var(--accent-color);">Admin</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Alumni</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($u['status'] === 'active'): ?>
                                        <span class="badge badge-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Suspended</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 0.35rem; align-items: center; flex-wrap: wrap;">
                                        
                                        <!-- View Profile -->
                                        <a href="../profile.php?id=<?php echo $u['id']; ?>" target="_blank" class="btn btn-outline-light btn-sm" style="font-size: 0.75rem; padding: 0.25rem 0.5rem; color: var(--primary-color);" title="View Public Profile">
                                            Profile
                                        </a>

                                        <!-- Toggle Status: Activate / Suspend -->
                                        <?php if ($u['id'] !== 1 && $u['id'] !== current_user_id()): ?>
                                            <form method="POST" action="users.php" style="display: inline;">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="action" value="change_status">
                                                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                                <input type="hidden" name="status" value="<?php echo ($u['status'] === 'active') ? 'suspended' : 'active'; ?>">
                                                <?php if ($u['status'] === 'active'): ?>
                                                    <button type="submit" class="btn btn-sm" style="background: #FFF4D6; color: var(--warning-color); border: 1px solid var(--warning-color); font-size: 0.75rem; padding: 0.25rem 0.5rem;" onclick="return confirm('Suspend this user account?');">
                                                        Suspend
                                                    </button>
                                                <?php else: ?>
                                                    <button type="submit" class="btn btn-sm btn-outline-gold" style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">
                                                        Activate
                                                    </button>
                                                <?php endif; ?>
                                            </form>

                                            <!-- Toggle Role -->
                                            <form method="POST" action="users.php" style="display: inline;">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="action" value="change_role">
                                                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                                <input type="hidden" name="role" value="<?php echo ($u['role'] === 'admin') ? 'alumni' : 'admin'; ?>">
                                                <button type="submit" class="btn btn-outline-light btn-sm" style="font-size: 0.75rem; padding: 0.25rem 0.5rem; color: var(--text-color);" onclick="return confirm('Change user role to <?php echo ($u['role'] === 'admin') ? 'Alumni' : 'Admin'; ?>?');">
                                                    <?php echo ($u['role'] === 'admin') ? 'Demote' : 'Make Admin'; ?>
                                                </button>
                                            </form>

                                            <!-- Delete User -->
                                            <form method="POST" action="users.php" style="display: inline;">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="action" value="delete_user">
                                                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                                <button type="submit" class="btn btn-sm" style="background: var(--danger-color); color: #fff; font-size: 0.75rem; padding: 0.25rem 0.5rem;" onclick="return confirm('PERMANENT DELETION: Are you sure you want to completely delete this user and all associated records?');">
                                                    Delete
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

<script>
// Requirement 25: Client-side live search for users table (Vanilla JS)
function liveFilterUsers() {
    const input = document.getElementById('userLiveSearch').value.toLowerCase();
    const rows = document.querySelectorAll('.user-row');
    rows.forEach(function(row) {
        const text = row.innerText.toLowerCase();
        if (text.includes(input)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
</script>

</body>
</html>
