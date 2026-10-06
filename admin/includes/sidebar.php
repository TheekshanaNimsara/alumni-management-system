<?php
// ============================================================
// University Alumni Network - Admin Sidebar Component
// ============================================================
$currentAdminPage = isset($currentAdminPage) ? $currentAdminPage : 'dashboard';
$baseUrl = get_base_url();
?>
<aside class="admin-sidebar">
    <div class="admin-sidebar-header">
        <a href="<?php echo $baseUrl; ?>index.php" class="logo" style="font-size: 1.1rem;">
            <span class="logo-badge">AN</span>
            <span>ALUMNI <span class="logo-gold">ADMIN</span></span>
        </a>
    </div>

    <ul class="admin-nav">
        <li class="admin-nav-item">
            <a href="<?php echo $baseUrl; ?>admin/index.php" class="admin-nav-link <?php echo ($currentAdminPage === 'dashboard') ? 'active' : ''; ?>">
                <span>&#128202;</span> Dashboard
            </a>
        </li>
        <li class="admin-nav-item">
            <a href="<?php echo $baseUrl; ?>admin/users.php" class="admin-nav-link <?php echo ($currentAdminPage === 'users') ? 'active' : ''; ?>">
                <span>&#128101;</span> Users
            </a>
        </li>
        <li class="admin-nav-item">
            <a href="<?php echo $baseUrl; ?>admin/events.php" class="admin-nav-link <?php echo ($currentAdminPage === 'events') ? 'active' : ''; ?>">
                <span>&#128197;</span> Events
            </a>
        </li>
        <li class="admin-nav-item">
            <a href="<?php echo $baseUrl; ?>admin/jobs.php" class="admin-nav-link <?php echo ($currentAdminPage === 'jobs') ? 'active' : ''; ?>">
                <span>&#128188;</span> Jobs
            </a>
        </li>
        <li class="admin-nav-item">
            <a href="<?php echo $baseUrl; ?>admin/settings.php" class="admin-nav-link <?php echo ($currentAdminPage === 'settings') ? 'active' : ''; ?>">
                <span>&#9881;</span> Settings
            </a>
        </li>
    </ul>

    <div class="admin-sidebar-footer">
        <div style="font-size: 0.82rem; color: #C8C0B5; margin-bottom: 0.5rem;">
            Signed in as <strong><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Administrator'); ?></strong>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <a href="<?php echo $baseUrl; ?>index.php" class="btn btn-outline-light btn-sm" style="flex: 1;">View Site</a>
            <a href="<?php echo $baseUrl; ?>auth/logout.php" class="btn btn-gold btn-sm" style="flex: 1;">Logout</a>
        </div>
    </div>
</aside>
