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
            <?php $pendingRepCount = ($db_connected && $pdo) ? get_pending_reports_count($pdo) : 0; ?>
            <a href="<?php echo $baseUrl; ?>admin/reports.php" class="admin-nav-link <?php echo ($currentAdminPage === 'reports') ? 'active' : ''; ?>">
                <span>&#9873;</span> Reports
                <?php if ($pendingRepCount > 0): ?>
                    <span class="badge" style="background: var(--accent-color); color: #FFFFFF; font-size: 0.72rem; font-weight: 700; padding: 0.15rem 0.45rem; border-radius: 10px; margin-left: auto;">
                        <?php echo $pendingRepCount; ?>
                    </span>
                <?php endif; ?>
            </a>
        </li>
        <li class="admin-nav-item">
            <a href="<?php echo $baseUrl; ?>admin/settings.php" class="admin-nav-link <?php echo ($currentAdminPage === 'settings') ? 'active' : ''; ?>">
                <span>&#9881;</span> Settings
            </a>
        </li>
    </ul>

    <div class="admin-sidebar-footer">
        <div style="margin-bottom: 0.8rem; display: flex; justify-content: center;">
            <div class="theme-switcher" role="radiogroup" aria-label="Select appearance theme">
                <button type="button" class="theme-option" data-theme-value="dark" role="radio" aria-checked="false" title="Dark theme (always dark)">
                    <svg class="theme-icon" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
                    <span class="theme-label">Dark</span>
                </button>
                <button type="button" class="theme-option" data-theme-value="light" role="radio" aria-checked="false" title="Light theme (always light)">
                    <svg class="theme-icon" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"></circle><line x1="12" y1="2" x2="12" y2="4"></line><line x1="12" y1="20" x2="12" y2="22"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
                    <span class="theme-label">Light</span>
                </button>
                <button type="button" class="theme-option" data-theme-value="system" role="radio" aria-checked="false" title="System theme (syncs with OS)">
                    <svg class="theme-icon" viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>
                    <span class="theme-label">Auto</span>
                </button>
            </div>
        </div>
        <div style="font-size: 0.82rem; color: var(--text-secondary); margin-bottom: 0.5rem;">
            Signed in as <strong><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Administrator'); ?></strong>
        </div>
        <div style="display: flex; gap: 0.5rem; flex-direction: column;">
            <a href="<?php echo $baseUrl; ?>index.php" class="btn btn-return-home btn-outline-light btn-sm" style="width: 100%; justify-content: center;">&larr; Return to Homepage</a>
            <a href="<?php echo $baseUrl; ?>auth/logout.php" class="btn btn-gold btn-sm" style="width: 100%; justify-content: center;">Logout</a>
        </div>
    </div>
    <script src="<?php echo $baseUrl; ?>assets/js/theme.js"></script>
</aside>
